<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class CambridgeIntersectionsSeeder extends Seeder
{
    private const BATCH_SIZE = 500;

    public function run()
    {
        $datasetName = 'cambridge-master-intersections-list';
        $citySubdirectory = 'cambridge';
        $files = Storage::disk('local')->files("datasets/{$citySubdirectory}");
        
        $datasetFiles = array_filter($files, function ($file) use ($datasetName) {
            return strpos(basename($file), $datasetName) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });
        
        if (!empty($datasetFiles)) {
            sort($datasetFiles);
            $fileToProcess = end($datasetFiles);
            $this->command->info("Processing Cambridge intersections file: " . $fileToProcess);
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->command->warn("No file found for Cambridge intersections.");
        }
    }

    private function processFile($filePath)
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $csv->setEscape('');
            $records = $csv->getRecords();
            $dataBatch = [];
            $progress = 0;
            
            foreach ($records as $record) {
                $progress++;
                $dataBatch[] = [
                    'nodenumber'                 => $record['nodenumber'] ?? null,
                    'intersection'               => $record['intersection'] ?? null,
                    'intersectingstreetcount'    => isset($record['intersectingstreetcount']) ? (int)$record['intersectingstreetcount'] : null,
                    'zip_code'                   => $record['zip_code'] ?? null,
                    'longitude'                  => is_numeric($record['longitude'] ?? null) ? (float)$record['longitude'] : null,
                    'latitude'                   => is_numeric($record['latitude'] ?? null) ? (float)$record['latitude'] : null,
                    'neighborhood'               => $record['neighborhood'] ?? null,
                    'election_ward'              => $record['election_ward'] ?? null,
                    'election_precinct'          => $record['election_precinct'] ?? null,
                    'election_polling_address'   => $record['election_polling_address'] ?? null,
                    'representation_district'    => $record['representation_district'] ?? null,
                    'senate_district'            => $record['senate_district'] ?? null,
                    'cad_reporting_district'     => $record['cad_reporting_district'] ?? null,
                    'police_sector'              => $record['police_sector'] ?? null,
                    'police_car_route'           => $record['police_car_route'] ?? null,
                    'police_walking_route'       => $record['police_walking_route'] ?? null,
                    'police_neighborhood'        => $record['police_neighborhood'] ?? null,
                    'police_business_district'   => $record['police_business_district'] ?? null,
                    'street_sweeping_district'   => $record['street_sweeping_district'] ?? null,
                    'census_tract_2010'          => $record['census_tract_2010'] ?? null,
                    'census_block_group_2010'    => $record['census_block_group_2010'] ?? null,
                    'census_block_2010'          => $record['census_block_2010'] ?? null,
                    'census_block_id_2010'       => $record['census_block_id_2010'] ?? null,
                    'commercial_district'        => $record['commercial_district'] ?? null,
                    'census_tract_2020'          => $record['census_tract_2020'] ?? null,
                    'census_block_group_2020'    => $record['census_block_group_2020'] ?? null,
                    'census_block_2020'          => $record['census_block_2020'] ?? null,
                    'census_block_id_2020'       => $record['census_block_id_2020'] ?? null,
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ];
                if ($progress % self::BATCH_SIZE === 0) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                    $this->command->info("Processed {$progress} records...");
                }
            }
            
            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }
            $this->command->info("File processed: " . basename($filePath));
        } catch (\Exception $e) {
            $this->command->error("Error processing file (intersections): " . basename($filePath) . " - " . $e->getMessage());
        }
    }
    
    private function insertOrUpdateBatch(array $dataBatch): void
    {
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['nodenumber']));
        if (empty($validBatch)) {
            return;
        }
        DB::table('cambridge_intersections')->upsert(
            $validBatch,
            ['nodenumber'],
            array_keys($validBatch[0])
        );
    }
}
