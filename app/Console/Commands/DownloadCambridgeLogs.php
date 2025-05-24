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

        foreach ($period as $dateObject) {
            $dateString = $dateObject->format('Y-m-d');
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
        $this->info("Days with no logs/page not found: " . (iterator_count($period) - $daysProcessed - $daysFailed));

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
            if (!$strongTagNode->count()) {
                $this->warn("Log Entry #" . ($i + 1) . " for {$date}: 'strong' tag not found in narrative. Using full narrative text for location if possible.");
                $street = $narrNode->text(); // Fallback, though less ideal
            } else {
                $street = $strongTagNode->text();
            }
            $fullText = $narrNode->text(); // Includes the <strong> text and the rest
            
            // $this->line("Narrative 'strong' tag (street/intersection): {$street}");
            // $this->line("Full narrative text:\n{$fullText}");

            $loc = '';
            if (strpos($street, '&') !== false) {
                $loc = trim($street);
                // $this->line("Location type: Intersection (from strong tag) -> '{$loc}'");
            } elseif (preg_match('/the\s+(\d+)\s+block of\s+/i', $fullText, $matches)) {
                $blockNumber = $matches[1];
                $loc = $blockNumber . ' ' . trim($street);
                // $this->line("Location type: Parsed 'block of' (block: {$blockNumber}, street: {trim($street)}) -> '{$loc}'");
            } else {
                // Fallback: Assume it's a street name, prepend "0" as default block number
                $loc = '0 ' . trim($street);
                // $this->line("Location type: Fallback (default block 0 for street) -> '{$loc}'");
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
