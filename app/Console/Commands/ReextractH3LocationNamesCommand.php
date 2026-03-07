<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\H3LocationName;

class ReextractH3LocationNamesCommand extends Command
{
    protected $signature = 'app:reextract-h3-location-names
                            {--dry-run : Preview changes without saving}';

    protected $description = 'Re-extracts location names from stored raw geocode responses without hitting the Google API.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $records = H3LocationName::whereNotNull('raw_geocode_response')->get();

        if ($records->isEmpty()) {
            $this->warn('No records with stored geocode responses found.');
            return 0;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Processing {$records->count()} records...");

        $changed   = 0;
        $unchanged = 0;
        $failed    = 0;

        foreach ($records as $record) {
            $apiData = $record->raw_geocode_response;

            if (empty($apiData['results'])) {
                $this->warn("  {$record->h3_index} — no results in stored response, skipping.");
                $failed++;
                continue;
            }

            $newName = $this->extractLocationName($apiData, (int) $record->h3_resolution);

            if ($newName === $record->location_name) {
                $unchanged++;
                continue;
            }

            $this->line(
                "  <fg=cyan>{$record->h3_index}</> (res {$record->h3_resolution})\n" .
                "    <fg=red>- {$record->location_name}</>\n" .
                "    <fg=green>+ {$newName}</>"
            );

            if (!$dryRun) {
                $record->update(['location_name' => $newName]);
            }

            $changed++;
        }

        $this->newLine();
        $this->info("Changed: {$changed}  |  Unchanged: {$unchanged}  |  Failed: {$failed}");

        if ($dryRun) {
            $this->warn('Dry run — no changes saved.');
        } else {
            Cache::forget('h3_location_names_map');
            $this->info('Cache invalidated.');
        }

        return 0;
    }

    private function extractLocationName(array $apiData, int $resolution): string
    {
        $radiusLabels = [
            4  => '~10 mi',
            5  => '~5 mi',
            6  => '~2 mi',
            7  => '~0.75 mi',
            8  => '~0.3 mi',
            9  => '~0.1 mi',
            10 => '~0.05 mi',
        ];

        $radius = $radiusLabels[$resolution]
            ?? ($resolution < 4 ? '~20 mi' : '~0.02 mi');

        $firstResult = $apiData['results'][0] ?? null;
        if (!$firstResult) return 'Unknown location';

        $byLong  = [];
        $byShort = [];
        foreach ($firstResult['address_components'] ?? [] as $c) {
            foreach ($c['types'] as $type) {
                $byLong[$type]  ??= $c['long_name'];
                $byShort[$type] ??= $c['short_name'];
            }
        }

        $streetNum    = $byLong['street_number'] ?? null;
        $streetName   = $byShort['route']        ?? null;
        $neighborhood = $byLong['neighborhood']
            ?? $byLong['sublocality_level_1']
            ?? $byLong['sublocality']
            ?? null;
        $locality = $byLong['locality'] ?? $byLong['postal_town'] ?? null;
        $state    = $byShort['administrative_area_level_1'] ?? null;
        $county   = $byLong['administrative_area_level_2']  ?? null;

        $street = ($streetNum && $streetName) ? "{$streetNum} {$streetName}" : null;

        if ($neighborhood && $locality && $state) {
            $neighborhoodLabel = "{$neighborhood} Neighborhood, {$locality}, {$state}";
            $location = $street ? "{$street} in {$neighborhoodLabel}" : $neighborhoodLabel;
            return "{$radius} around {$location}";
        }

        if ($locality && $state) {
            $location = $street ? "{$street}, {$locality}, {$state}" : "{$locality}, {$state}";
            return "{$radius} around {$location}";
        }

        if ($county && $state) return "{$radius} around {$county}, {$state}";

        return "{$radius} around " . ($byLong['administrative_area_level_1'] ?? 'Unknown location');
    }
}
