<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AnalysisReportSnapshot extends Model
{
    protected $fillable = ['job_id', 'artifact_name', 'payload', 's3_last_modified', 'pulled_at'];

    protected $casts = [
        'payload'   => 'array',
        'pulled_at' => 'datetime',
    ];

    /**
     * Resolve artifact data: DB snapshot first, S3 fallback.
     * Returns the decoded payload array, or null if not found anywhere.
     */
    public static function resolve(string $jobId, string $artifact): ?array
    {
        $snapshot = static::where('job_id', $jobId)->where('artifact_name', $artifact)->first();
        if ($snapshot) {
            return $snapshot->payload;
        }

        // S3 fallback
        $path = "{$jobId}/{$artifact}";

        try {
            $s3 = Storage::disk('s3');
            if (!$s3->exists($path)) {
                return null;
            }
            $data = json_decode($s3->get($path), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("AnalysisReportSnapshot::resolve JSON error for {$path}: " . json_last_error_msg());
                return null;
            }
            return $data;
        } catch (\Exception $e) {
            Log::warning("AnalysisReportSnapshot::resolve S3 failure for {$path}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Return all job IDs that have the given artifact in the snapshot table.
     */
    public static function jobIdsForArtifact(string $artifact): array
    {
        return static::where('artifact_name', $artifact)->pluck('job_id')->all();
    }

    /**
     * Return all snapshots whose artifact_name starts with one of the given prefixes.
     */
    public static function allForArtifactPrefixes(array $prefixes): \Illuminate\Database\Eloquent\Collection
    {
        return static::where(function ($q) use ($prefixes) {
            foreach ($prefixes as $prefix) {
                $q->orWhere('artifact_name', 'LIKE', $prefix . '%');
            }
        })->get();
    }
}
