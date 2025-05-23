<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BuildingPermit;
use League\Csv\Reader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CambridgeBuildingPermitsSeeder extends Seeder
{
    private const BATCH_SIZE = 500;
    protected $output;

    // Column mapping from Cambridge CSV headers (new 125-field structure) to database fields
    private const COLUMN_MAP = [
        'permitnumber'        => 'id', // CSV 'id' maps to DB 'permitnumber'
        'worktype'            => 'building_use',
        'permittypedescr'     => 'permit_type',
        'description'         => 'detailed_description_of_work',
        // 'comments' will be constructed from other fields
        'applicant'           => 'firm_name', // Assuming firm_name is the applicant
        'declared_valuation'  => 'total_cost_of_construction',
        // 'total_fees'       => null, // No obvious direct map from the 125 fields
        'issued_date'         => 'issue_date',
        // 'expiration_date'  => null, // No obvious direct map from the 125 fields
        'status'              => 'status',
        'occupancytype'       => 'proposed_property_use',
        'sq_feet'             => 'gross_square_footage',
        'address'             => 'address', // The CSV has a single 'address' field
        // 'city' will be hardcoded to 'Cambridge'
        // 'state' will be hardcoded to 'MA'
        // 'zip' => null, // No direct 'zip' or 'zip_code' in the 125 fields.
        'property_id'         => 'maplot_number',
        'parcel_id'           => 'maplot_number', // Using maplot_number for both, adjust if a better field exists
        'gpsy'                => 'latitude',    // For DB gpsy decimal(15,8)
        'gpsx'                => 'longitude',   // For DB gpsx decimal(15,8)
        'y_latitude'          => 'latitude',    // For DB y_latitude decimal(15,13)
        'x_longitude'         => 'longitude',   // For DB x_longitude decimal(15,13)
        // 'language_code' will be hardcoded 'en-US'
    ];

    // ADDRESS_PARTS_MAP is no longer needed as 'address' is a single field in the new CSV.

    public function __construct()
    {
        $this->output = new OutputStyle(new ArgvInput(), new ConsoleOutput());
    }

    private function makeHumanReadable(string $headerName): string
    {
        return ucwords(str_replace('_', ' ', $headerName));
    }

    public function run()
    {
        $datasetName = 'cambridge-building-permits';
        $citySubdirectory = 'cambridge'; // Matches 'city' in datasets.php config

        // Get all files from the specific city's dataset folder in Storage
        $pathPattern = "datasets/{$citySubdirectory}/{$datasetName}_*.csv";
        $files = Storage::disk('local')->files("datasets/{$citySubdirectory}");

        // Filter files to only include those matching the dataset name pattern
        $datasetFiles = array_filter($files, function ($file) use ($datasetName) {
            return strpos(basename($file), $datasetName) === 0 && pathinfo($file, PATHINFO_EXTENSION) === 'csv';
        });

        if (!empty($datasetFiles)) {
            // Sort files by name to get the most recent (assuming timestamp in filename makes it last)
            sort($datasetFiles);
            $fileToProcess = end($datasetFiles);
            $this->output->writeln("<info>Processing Cambridge building permits file: " . $fileToProcess . "</info>");
            $this->processFile(Storage::path($fileToProcess));
        } else {
            $this->output->writeln("<comment>No files found for Cambridge building permits matching pattern: {$pathPattern}</comment>");
        }
    }

    private function processFile($filePath)
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // CSV header is on the first row
            $csv->setEscape('');

            $records = $csv->getRecords(); // Yields associative arrays
            $permitModel = new BuildingPermit();
            $fillableFields = $permitModel->getFillable();
            
            // Get the list of CSV headers that are directly mapped
            $mappedCsvHeaders = array_values(self::COLUMN_MAP);

            $dataBatch = [];
            $recordCount = 0;
            
            // Recount total records for progress bar as original iterator might be consumed by header processing
            $totalRecords = iterator_count(Reader::createFromPath($filePath, 'r')->setHeaderOffset(0)->setEscape('')->getRecords());
            $this->output->writeln("Starting processing of " . basename($filePath) . " (New 125-column structure)");
            $progressBar = $this->output->createProgressBar($totalRecords);
            $progressBar->start();

            foreach ($records as $csvRecord) { // $csvRecord is an associative array
                $recordCount++;
                $progressBar->advance();
                $permitData = [];

                // Initialize all fillable fields to null
                foreach ($fillableFields as $dbField) {
                    $permitData[$dbField] = null;
                }

                // Map CSV data to model fields using COLUMN_MAP
                foreach (self::COLUMN_MAP as $dbField => $csvHeader) {
                    if (isset($csvRecord[$csvHeader])) {
                        $permitData[$dbField] = $csvRecord[$csvHeader] === '' ? null : $csvRecord[$csvHeader];
                    }
                }
                
                // Aggregate unmapped fields into comments
                $commentsString = "";
                foreach ($csvRecord as $csvHeaderKey => $csvValue) {
                    // If the header is not in our mapped list and the value is not empty
                    if (!in_array($csvHeaderKey, $mappedCsvHeaders) && !empty($csvValue)) {
                        $humanReadableHeader = $this->makeHumanReadable($csvHeaderKey);
                        $commentsString .= $humanReadableHeader . ": " . $csvValue . "\n";
                    }
                }
                $permitData['comments'] = !empty($commentsString) ? trim($commentsString) : null;

                // Set city, state, language_code
                $permitData['city'] = 'Cambridge';
                $permitData['state'] = 'MA';
                $permitData['language_code'] = 'en-US';

                // Data cleaning and formatting
                $permitData['issued_date'] = $this->formatDate($permitData['issued_date']);
                $permitData['expiration_date'] = $this->formatDate($permitData['expiration_date']); // Will be null if not in COLUMN_MAP

                $sqfeet = is_numeric($permitData['sq_feet']) ? (int)$permitData['sq_feet'] : 0;
                $permitData['sq_feet'] = min($sqfeet, 1000000000);

                // Handle latitude/longitude, ensuring they are numeric or null
                $permitData['gpsy'] = is_numeric($permitData['gpsy']) ? (float)$permitData['gpsy'] : null;
                $permitData['gpsx'] = is_numeric($permitData['gpsx']) ? (float)$permitData['gpsx'] : null;
                $permitData['y_latitude'] = is_numeric($permitData['y_latitude']) ? (float)$permitData['y_latitude'] : null;
                $permitData['x_longitude'] = is_numeric($permitData['x_longitude']) ? (float)$permitData['x_longitude'] : null;


                // Ensure permitnumber (from CSV 'id') is present for upsert
                if (empty($permitData['permitnumber'])) {
                    $this->output->writeln("<warning>Skipping record (index {$recordCount}) due to missing permit number (CSV 'id' field) in file: " . basename($filePath) . "</warning>");
                    continue;
                }
                
                // Convert any remaining empty strings in $permitData to null (already done by initial mapping for mapped fields)
                // This ensures that if a fillable field was not in COLUMN_MAP and not set otherwise, it remains null.

                $dataBatch[] = $permitData;

                if (count($dataBatch) >= self::BATCH_SIZE) {
                    $this->insertOrUpdateBatch($dataBatch);
                    $dataBatch = [];
                }
            }
            $progressBar->finish();
            $this->output->newLine();

            if (!empty($dataBatch)) {
                $this->insertOrUpdateBatch($dataBatch);
            }

            $this->output->writeln("<info>Successfully processed Cambridge building permits file: " . basename($filePath) . ". Total records: {$recordCount}</info>");

        } catch (\League\Csv\Exception $e) {
            $this->output->writeln("<error>CSV Processing Error for file: " . basename($filePath) . " - " . $e->getMessage() . "</error>");
        } catch (\Exception $e) {
            $this->output->writeln("<error>Error processing Cambridge file: " . basename($filePath) . " - " . $e->getMessage() . "\n" . $e->getTraceAsString() . "</error>");
        }
    }

    private function formatDate($date)
    {
        if (empty($date) || strtolower($date) === 'nan') {
            return null;
        }
        if ($date == '1970-01-01 00:00:00') {
            return null;
        }
        $timestamp = strtotime($date);
        if ($timestamp === false || $timestamp < 0) {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date); // Try to parse YYYY-MM-DD
            if ($dateTime && $dateTime->format('Y-m-d') === $date) {
                 return $dateTime->format('Y-m-d H:i:s');
            }
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function insertOrUpdateBatch(array $dataBatch): void
    {
        $validBatch = array_filter($dataBatch, fn($item) => !empty($item['permitnumber']));
        if (empty($validBatch)) {
            return;
        }
        
        $updateColumns = [
            'worktype', 'permittypedescr', 'description', 'comments', 'applicant',
            'declared_valuation', 'total_fees', 'issued_date', 'expiration_date',
            'status', 'occupancytype', 'sq_feet', 'address', 'city', 'state', 'zip',
            'property_id', 'parcel_id', 'gpsy', 'gpsx', 'y_latitude', 'x_longitude',
            'language_code', 'updated_at'
        ];
        
        DB::table((new BuildingPermit)->getTable())->upsert(
            $validBatch,
            ['permitnumber'],
            $updateColumns
        );
    }
}
