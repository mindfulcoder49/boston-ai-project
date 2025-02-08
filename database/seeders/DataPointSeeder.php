<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DataPointSeeder extends Seeder
{
    private const DAYS_TO_KEEP = 14; // Change this to adjust the timeframe

    private const MODELS = [
        'crime_data' => ['lat' => 'lat', 'lng' => 'long', 'id' => 'id', 'date_field' => 'occurred_on_date', 'foreign_key' => 'crime_data_id'],
        'three_one_one_cases' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'open_dt', 'foreign_key' => 'three_one_one_case_id'],
        'property_violations' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'status_dttm', 'foreign_key' => 'property_violation_id'],
        'construction_off_hours' => ['lat' => 'latitude', 'lng' => 'longitude', 'id' => 'id', 'date_field' => 'start_datetime', 'foreign_key' => 'construction_off_hour_id'],
        'building_permits' => ['lat' => 'y_latitude', 'lng' => 'x_longitude', 'id' => 'id', 'date_field' => 'issued_date', 'foreign_key' => 'building_permit_id'],
    ];

    public function run()
    {
        $cutoffDate = Carbon::now()->subDays(self::DAYS_TO_KEEP)->toDateTimeString();

        // Delete old records from `data_points`
        DB::table('data_points')->where('created_at', '<', $cutoffDate)->delete();
        $this->command->info("Deleted old data points older than " . self::DAYS_TO_KEEP . " days.");

        foreach (self::MODELS as $table => $fields) {
            $this->syncDataPoints($table, $fields, $cutoffDate);
        }
    }

    private function syncDataPoints(string $table, array $fields, string $cutoffDate)
    {
        $newData = DB::table($table)
            ->where($fields['date_field'], '>=', $cutoffDate)
            ->whereNotNull($fields['lat'])
            ->whereNotNull($fields['lng'])
            ->get();

        if ($newData->isEmpty()) {
            $this->command->warn("No new records found for {$table}.");
            return;
        }

        $batchInsert = [];
        foreach ($newData as $row) {
            $batchInsert[] = [
                'type' => $table,
                'location' => DB::raw("ST_GeomFromText('POINT({$row->{$fields['lng']}} {$row->{$fields['lat']}})')"),
                $fields['foreign_key'] => $row->{$fields['id']},
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($batchInsert)) {
            DB::table('data_points')->upsert($batchInsert, [$fields['foreign_key']], ['location', 'updated_at']);
            $this->command->info("Updated " . count($batchInsert) . " records for {$table}.");
        }
    }
}
