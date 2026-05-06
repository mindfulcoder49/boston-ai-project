<?php

namespace App\Services;

class EverettMarkdownCompatibilityProbe
{
    public function probeText(string $text, string $kind): array
    {
        return match ($kind) {
            'daily' => $this->probeDailyText($text),
            'arrest' => $this->probeArrestText($text),
            default => [
                'kind' => $kind,
                'compatible' => false,
                'message' => 'Unknown Everett markdown kind.',
            ],
        };
    }

    public function probeDailyText(string $text): array
    {
        $lines = preg_split("/\r\n|\r|\n/", $text) ?: [];

        $fileDatePattern = "/^\s*DAILY LOG\s+(\d{2}\/\d{2}\/\d{4})\s*$/";
        $entryHeaderPattern = "/^\s*\*\*\*\s+[A-Z]{3}\s+(\d{2}\/\d{2}\/\d{4})\s+(.+?)\s+\*{10}\s*$/";
        $timeLocPattern = "/^\s*(\d{2}:\d{2})\s*\*\s*(.+?)(?:\s+EVE)?\s*$/";
        $caseDescPattern = "/^\s*(\d{6})\s*\*\s*(.+)$/";
        $pageFeedPattern = "/^\f\s*$/";

        $fileDateMatches = 0;
        $entryHeaderMatches = 0;
        $timeLocationMatches = 0;
        $caseDescriptionMatches = 0;
        $entries = [];
        $currentCallData = [];
        $fileLogDate = null;
        $state = 'EXPECT_FILE_DATE';

        foreach ($lines as $lineContent) {
            $line = rtrim($lineContent, "\n\r");
            $strippedLine = trim($line);

            if (preg_match($fileDatePattern, $strippedLine, $match)) {
                $fileDateMatches++;
                if ($fileLogDate === null) {
                    $fileLogDate = $match[1];
                }
            }

            if (preg_match($entryHeaderPattern, $strippedLine)) {
                $entryHeaderMatches++;
            }

            if (preg_match($timeLocPattern, $line)) {
                $timeLocationMatches++;
            }

            if (preg_match($caseDescPattern, $line)) {
                $caseDescriptionMatches++;
            }

            if (empty($strippedLine) || preg_match($pageFeedPattern, $line)) {
                continue;
            }

            switch ($state) {
                case 'EXPECT_FILE_DATE':
                    if (preg_match($fileDatePattern, $strippedLine, $match)) {
                        $fileLogDate = $match[1];
                        $state = 'EXPECT_ENTRY_HEADER';
                    }
                    break;

                case 'EXPECT_ENTRY_HEADER':
                    if (preg_match($entryHeaderPattern, $strippedLine, $match)) {
                        $currentCallData = [
                            'log_file_date' => $fileLogDate,
                            'entry_date' => $match[1],
                            'type' => trim($match[2]),
                        ];
                        $state = 'EXPECT_TIME_LOC';
                    }
                    break;

                case 'EXPECT_TIME_LOC':
                    if (preg_match($timeLocPattern, $line, $match)) {
                        $currentCallData['time'] = $match[1];
                        $currentCallData['address'] = trim($match[2]);
                        $state = 'EXPECT_CASE_DESC';
                    } else {
                        $currentCallData = [];
                        $state = 'EXPECT_ENTRY_HEADER';
                    }
                    break;

                case 'EXPECT_CASE_DESC':
                    if (preg_match($caseDescPattern, $line, $match)) {
                        $currentCallData['case_number'] = $match[1];
                        $currentCallData['description'] = trim($match[2]);
                        $entries[] = $currentCallData;
                    }

                    $currentCallData = [];
                    $state = 'EXPECT_ENTRY_HEADER';
                    break;
            }
        }

        return [
            'kind' => 'daily',
            'compatible' => count($entries) > 0,
            'file_date_matches' => $fileDateMatches,
            'entry_header_matches' => $entryHeaderMatches,
            'time_location_matches' => $timeLocationMatches,
            'case_description_matches' => $caseDescriptionMatches,
            'parsed_entries_count' => count($entries),
            'sample_entry' => $entries[0] ?? null,
        ];
    }

    public function probeArrestText(string $text): array
    {
        $lines = array_map('trim', preg_split("/\r\n|\r|\n/", $text) ?: []);

        $namePattern = "/^([A-Z][A-Z\s,'.-]+,\s*[A-Z][A-Z\s,'.-]+(?:\s+[A-Z\.\s-]+)?)$/";
        $ageCasePattern = "/^\s*age:\s*(\d+)\s*arrest date:\s*(\d{2}\/\d{2}\/\d{4})\s*case:\s*(\d{6})\s*$/";
        $skipPatterns = "/ARREST LOG|From:.*To:|^\s*\f\s*$|^$/";

        $nameMatches = 0;
        $ageCaseMatches = 0;
        $entries = [];
        $index = 0;
        $lineCount = count($lines);

        while ($index < $lineCount) {
            $line = $lines[$index];

            if (preg_match($skipPatterns, $line) || $line === '') {
                $index++;
                continue;
            }

            if (preg_match($namePattern, $line, $nameMatch)) {
                $nameMatches++;

                if ($index + 2 < $lineCount) {
                    $potentialName = trim($nameMatch[1]);
                    $potentialAddress = trim($lines[$index + 1]);
                    $ageCaseLine = trim($lines[$index + 2]);

                    if (preg_match($ageCasePattern, $ageCaseLine, $ageCaseMatch)) {
                        $ageCaseMatches++;
                        $currentArrest = [
                            'name' => $potentialName,
                            'address' => $potentialAddress,
                            'age' => $ageCaseMatch[1],
                            'date' => $ageCaseMatch[2],
                            'case_number' => $ageCaseMatch[3],
                            'charges' => [],
                        ];

                        $index += 3;

                        while ($index < $lineCount) {
                            $chargeLine = trim($lines[$index]);

                            if (
                                $chargeLine === ''
                                || preg_match($skipPatterns, $chargeLine)
                                || preg_match($namePattern, $chargeLine)
                            ) {
                                break;
                            }

                            $currentArrest['charges'][] = $chargeLine;
                            $index++;
                        }

                        $entries[] = $currentArrest;
                        continue;
                    }
                }
            }

            $index++;
        }

        return [
            'kind' => 'arrest',
            'compatible' => count($entries) > 0,
            'name_matches' => $nameMatches,
            'age_case_matches' => $ageCaseMatches,
            'parsed_entries_count' => count($entries),
            'sample_entry' => $entries[0] ?? null,
        ];
    }
}
