<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use Carbon\Carbon;
use DatePeriod;
use DateInterval;
// Illuminate\Support\Facades\Storage; // Not strictly needed for this command's file operations

class DownloadCambridgeLogs extends Command
{
    protected $signature = 'app:download-cambridge-logs';
    protected $description = 'Scrapes Cambridge daily police logs for the last two months and outputs CSV files for seeding.';

    public function handle()
    {
        $this->line("<fg=cyan>Starting to download Cambridge police logs for the last two months.</>");

        $endDate = Carbon::today();
        $startDate = Carbon::today()->subMonths(2)->startOfDay();
        
        // Ensure the period includes the end date
        $period = new DatePeriod(
            $startDate,
            new DateInterval('P1D'),
            $endDate->copy()->addDay() // Add a day to include the endDate in the period
        );

        $overallStatus = 0;
        $daysProcessed = 0;
        $daysFailed = 0;
        $daysSkipped = 0;

        foreach ($period as $dateObject) {
            $dateString = $dateObject->format('Y-m-d');
            $this->line("<fg=magenta>--- Checking date: {$dateString} ---</>");

            // Construct the expected output file path
            [$Y, $m, $d] = explode('-', $dateString);
            $outFileName = "cambridge_{$Y}{$m}{$d}.csv";
            $outputDir = storage_path("app/datasets/cambridge/logs");
            $expectedFilePath = "{$outputDir}/{$outFileName}";

            if (file_exists($expectedFilePath)) {
                $this->line("<fg=yellow>Logs for {$dateString} already exist at {$expectedFilePath}. Skipping.</>");
                $daysSkipped++;
                $this->line("<fg=magenta>--- Finished checking for date: {$dateString} ---</>");
                $this->line(""); // Add a blank line for readability
                continue;
            }
            
            $this->line("<fg=magenta>--- Processing date: {$dateString} ---</>");
            
            $result = $this->processDate($dateString);
            
            if ($result === 0) {
                $this->line("<fg=green>Successfully processed logs for {$dateString}.</>");
                $daysProcessed++;
            } elseif ($result === 1) { // Specific error code for processing failure
                $this->error("Failed to process logs for {$dateString}.");
                $daysFailed++;
                $overallStatus = 1; // Mark that at least one day failed
            } elseif ($result === 2) { // Specific code for no logs found/page not exist
                $this->warn("No logs found or page did not exist for {$dateString}. Skipping.");
                // Not necessarily an error, so overallStatus might not be set to 1
            }
            $this->line("<fg=magenta>--- Finished processing for date: {$dateString} ---</>");
            $this->line(""); // Add a blank line for readability between dates
        }

        $this->info("Log download process completed.");
        $this->info("Days processed successfully: {$daysProcessed}");
        $this->info("Days failed: {$daysFailed}");
        $this->info("Days skipped (already downloaded): {$daysSkipped}");
        $this->info("Days with no logs/page not found: " . (iterator_count($period) - $daysProcessed - $daysFailed - $daysSkipped));

        return $overallStatus;
    }

    private function processDate(string $date): int
    {
        $this->line("<fg=cyan>Processing date: {$date}</>");

        [$Y, $m, $d] = explode('-', $date);
        $mmddyyyy = sprintf('%02d%02d%s', $m, $d, $Y);
        $url = "https://www.cambridgema.gov/Departments/cambridgepolice/News/{$Y}/{$m}/{$mmddyyyy}";

        $this->info("Fetching logs from URL: {$url}");
        $html = @file_get_contents($url);
        if (!$html) {
            $this->warn("Failed to fetch HTML content from {$url}. The page might not exist or there was a network issue. This day will be skipped.");
            return 2; // Special return code for page not found/no content
        }
        $this->line("<fg=green>Successfully fetched HTML content.</>");

        $crawler = new Crawler($html);
        $rows = [];
        $this->line("Starting to process log entries for {$date}...");

        $crawler->filter('ul.logEntries > li')->each(function(Crawler $li, $i) use (&$rows, $date) {
            $this->line("<fg=yellow>--- Processing Log Entry #" . ($i + 1) . " for {$date} ---</>");

            $detailsNode = $li->filter('div.details');
            if (!$detailsNode->count()) {
                $this->warn("Log Entry #" . ($i + 1) . " for {$date}: Skipping entry, 'div.details' not found.");
                return;
            }
            // Get HTML content of the div.details
            $detailsHtml = $detailsNode->html();
            // Replace <br> tags (and variants like <br />) with newlines
            $detailsHtmlProcessed = preg_replace('/<br\s*\/?>/i', "\n", $detailsHtml);
            // Strip other HTML tags (like <span>) but keep their content
            $detailsTextContent = strip_tags($detailsHtmlProcessed);
            
            // $this->line("Processed 'details' text (after br to newline & strip_tags):\n{$detailsTextContent}"); // Verbose, can be commented out

            $parts = array_values(array_filter(
                array_map('trim', explode("\n", $detailsTextContent)),
                fn($v) => $v !== ''
            ));

            if (count($parts) < 3) {
                $this->warn("Log Entry #" . ($i + 1) . " for {$date}: Malformed 'details' section. Expected at least 3 parts, got " . count($parts) . ". Parts: " . implode(' | ', $parts) . ". Skipping entry.");
                return;
            }
            [$when, $incLine, $crimeDesc] = $parts;
            // $this->line("Parsed 'when': {$when}");
            // $this->line("Parsed 'incLine': {$incLine}");
            // $this->line("Parsed 'crimeDesc': {$crimeDesc}");

            $fileNumParts = preg_split('/\s+/', $incLine, 2);
            if (count($fileNumParts) < 2) {
                $this->warn("Log Entry #" . ($i + 1) . " for {$date}: Could not parse file number from '{$incLine}'. Skipping entry.");
                return;
            }
            $fileNum = $fileNumParts[1];
            // $this->line("Extracted 'fileNum': {$fileNum}");

            $narrNode = $li->filter('div.narrative');
            if (!$narrNode->count()) {
                $this->warn("Log Entry #" . ($i + 1) . " for {$date}: Skipping entry, 'div.narrative' not found.");
                return;
            }
            
            $strongTagNode = $narrNode->filter('strong');
            $fullText = $narrNode->text(); // Includes the <strong> text and the rest
            $loc = '';
            $streetFromStrong = '';

            if ($strongTagNode->count()) {
                $streetFromStrong = trim($strongTagNode->text());
                // Normalize multiple spaces, especially if present in malformed entries
                $streetFromStrong = preg_replace('/\s+/', ' ', $streetFromStrong);
            } else {
                $this->warn("Log Entry #" . ($i + 1) . " for {$date}: 'strong' tag not found in narrative. Attempting to parse location from full narrative.");
            }

            // Define common street suffixes (case-insensitive matching will be used)
            $suffixes = ['ST', 'STREET', 'AVE', 'AVENUE', 'RD', 'ROAD', 'DR', 'DRIVE', 'LN', 'LANE', 'CT', 'COURT', 'PL', 'PLACE', 'TER', 'TERRACE', 'WAY', 'BLVD', 'BOULEVARD', 'CIR', 'CIRCLE', 'SQ', 'SQUARE', 'PKWY', 'PARKWAY', 'HWY', 'HIGHWAY', 'ALY', 'ALLEY', 'TRL', 'TRAIL', 'XING', 'CROSSING'];
            $streetSuffixPattern = '(?:' . implode('|', array_map('preg_quote', $suffixes, ['/'])) . ')';
            $streetNameChars = '[A-Z0-9\.\'\-]+'; // Characters allowed in street names
            $streetNamePattern = $streetNameChars . '(?:\s+' . $streetNameChars . ')*'; // One or more "words" for a street name
            $directionPattern = '(?:[NSEW]|NORTH|SOUTH|EAST|WEST|NORTHEAST|NORTHWEST|SOUTHEAST|SOUTHWEST)';

            // Regex for a full street: [Block Number] Street Name SUFFIX [Direction]
            // Captures: 1=Full Street, 2=Remainder of string after street
            $fullStreetRegex = '/^((?:\d+\s+)?' . $streetNamePattern . '\s+' . $streetSuffixPattern . '(?:\s+' . $directionPattern . ')?)(\s+.*)?/i';
            // Regex for an intersection: Street1 & Street2
            // Captures: 1=Full Intersection, 2=Remainder of string
            $intersectionRegex = '/^(' . $streetNamePattern . '\s+' . $streetSuffixPattern . '(?:\s+' . $directionPattern . ')?\s*&\s*' . $streetNamePattern . '\s+' . $streetSuffixPattern . '(?:\s+' . $directionPattern . ')?)(\s+.*)?/i';
            // Regex for "the X block of Y"
            // Captures: 1=Block Number, 2=Street Name with Suffix and optional Direction
            $blockOfRegex = '/the\s+(\d+)\s+block of\s+(' . $streetNamePattern . '\s+' . $streetSuffixPattern . '(?:\s+' . $directionPattern . ')?)/i';

            // Attempt 1: Check if streetFromStrong is an intersection
            if (preg_match($intersectionRegex, $streetFromStrong, $matchesIntersection)) {
                $potentialLoc = trim($matchesIntersection[1]);
                $remainder = isset($matchesIntersection[2]) ? trim($matchesIntersection[2]) : '';
                if (empty($remainder) || !preg_match('/^[a-zA-Z]/', $remainder) || strlen($potentialLoc) > strlen($remainder) * 1.5) {
                    $loc = $potentialLoc;
                }
            }

            // Attempt 2: Check if streetFromStrong starts with a full street address
            if (empty($loc) && preg_match($fullStreetRegex, $streetFromStrong, $matchesStreet)) {
                $potentialLoc = trim($matchesStreet[1]);
                $remainder = isset($matchesStreet[2]) ? trim($matchesStreet[2]) : '';

                if (strlen($potentialLoc) < 80) { // Plausible length for a location string
                    if (empty($remainder) || (preg_match('/^[A-Z]/', $remainder) && strlen($potentialLoc) < strlen($remainder)) || (strlen($potentialLoc) > 0 && strlen($remainder) < 5)) {
                        $loc = $potentialLoc;
                        if (!preg_match('/^\d/', $loc) && strpos($loc, '&') === false) {
                            $loc = '0 ' . $loc;
                        }
                    }
                }
            }

            // Attempt 3: If loc is still empty, try to parse "the X block of Y" from the fullText
            if (empty($loc) && preg_match($blockOfRegex, $fullText, $matchesBlock)) {
                $blockNumber = $matchesBlock[1];
                $streetNameAndSuffix = trim($matchesBlock[2]);
                $loc = $blockNumber . ' ' . $streetNameAndSuffix;
            }

            // Fallback logic
            if (empty($loc)) {
                if (!empty($streetFromStrong)) {
                    if (strlen($streetFromStrong) < 70 && !preg_match('/\b(Police|Officer|Arrested|Suspect|Victim|Reported|Responded|Found|Unit|Male|Female)\b/i', $streetFromStrong) && !preg_match('/[.,;]$/', $streetFromStrong) && !preg_match('/^\d{2,}\s+OF\s+/i', $streetFromStrong) /* Avoid "36 OF CAMBRIDGE" */) {
                        $loc = $streetFromStrong;
                        if (!preg_match('/^\d/', $loc) && strpos($loc, '&') === false && preg_match('/\s(?:' . $streetSuffixPattern . ')/i', $loc)) {
                            $loc = '0 ' . $loc;
                        }
                    } else {
                        $this->warn("Log Entry #" . ($i + 1) . " for {$date}: Could not reliably determine location from strong tag. Strong: '{$streetFromStrong}'.");
                        $loc = 'UNKNOWN';
                    }
                } else {
                    $this->warn("Log Entry #" . ($i + 1) . " for {$date}: No strong tag content, location determination relies on full text or is unknown.");
                    $loc = 'UNKNOWN';
                }

                if ($loc === 'UNKNOWN' || empty($loc)) {
                    // Prepositional phrase match from full text
                    $prepositionalLocationRegex = '/(?:on|at|near|in the area of|from|to)\s+((?:\d+\s+)?' . $streetNamePattern . '\s+' . $streetSuffixPattern . '(?:\s+' . $directionPattern . ')?)/i';
                    if (preg_match($prepositionalLocationRegex, $fullText, $matchesPrep)) {
                        $loc = trim($matchesPrep[1]);
                        if (!preg_match('/^\d/', $loc) && strpos($loc, '&') === false) {
                            $loc = '0 ' . $loc;
                        }
                    } elseif ($loc === 'UNKNOWN') { // Ensure UNKNOWN if no better match found
                         // $this->line("Location type: Truly unknown or unparsable -> '{$loc}'");
                    }
                }
            }
            
            if (empty($loc)) { // Final safety net if all parsing fails
                $loc = 'UNKNOWN';
                $this->warn("Log Entry #" . ($i + 1) . " for {$date}: Location parsing resulted in empty, setting to UNKNOWN. Strong: '{$streetFromStrong}', FullText: '{$fullText}'");
            }


            $reportDate = '';
            $whenParts = explode(' ', $when, 2);
            if (count($whenParts) > 0) {
                $reportDate = $whenParts[0];
            } else {
                $this->warn("Log Entry #" . ($i + 1) . " for {$date}: Could not parse date from 'when' field: {$when}");
            }
            // $this->line("Extracted 'reportDate': {$reportDate}");


            $rowData = [
                'date_of_report'   => $reportDate,
                'crime_date_time'  => $when,
                'file_number'      => $fileNum,
                'crime'            => $crimeDesc,
                'location'         => $loc,
                'crime_details'    => $fullText,
            ];
            $rows[] = $rowData;
            // $this->line("Row added to batch: " . json_encode($rowData));
            $this->line("<fg=yellow>--- Finished Log Entry #" . ($i + 1) . " for {$date} ---</>");
        });

        if (empty($rows)) {
            $this->warn("No log entries were successfully processed for {$date}. CSV file will not be created.");
            return 0; // Not an error, but no data to write.
        }

        $dir = storage_path("app/datasets/cambridge/logs");
        $this->line("Ensuring output directory exists: {$dir}");
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                $this->line("<fg=green>Created directory: {$dir}</>");
            } else {
                $this->error("Failed to create directory: {$dir}");
                return 1; // Error creating directory
            }
        } else {
            $this->line("Output directory already exists.");
        }

        $outFileName = "cambridge_{$Y}{$m}{$d}.csv";
        $out = "{$dir}/{$outFileName}";
        $this->line("Preparing to write CSV to: {$out}");

        $fh = fopen($out, 'w');
        if ($fh === false) {
            $this->error("Failed to open file for writing: {$out}");
            return 1; // Error opening file
        }

        $headers = ['date_of_report','crime_date_time','file_number','crime','location','crime_details'];
        $this->line("Writing CSV headers for {$date}: " . implode(',', $headers));
        fputcsv($fh, $headers);

        $this->line("Writing " . count($rows) . " data rows to CSV for {$date}...");
        foreach ($rows as $r) {
            fputcsv($fh, $r);
        }
        fclose($fh);

        $numEntries = count($rows);
        $message = "Wrote " . $out . " (" . $numEntries . " entries)";
        $this->info("<fg=green>{$message}</>");
        return 0; // Success for this date
    }
}
