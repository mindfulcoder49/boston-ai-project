<?php

namespace Tests\Unit\Services;

use App\Services\EverettMarkdownCompatibilityProbe;
use Tests\TestCase;

class EverettMarkdownCompatibilityProbeTest extends TestCase
{
    public function test_it_detects_daily_log_parser_compatibility(): void
    {
        $probe = new EverettMarkdownCompatibilityProbe();

        $text = <<<TEXT
DAILY LOG           01/01/2025

*** WED 01/01/2025 FIREWORKS COMPLAINT                       **********
   00:08 * 410 MAIN ST EVE
  928576 * 911-PARTIES LIGHTING FIREWORKS IN STREET

*** WED 01/01/2025 MEDICAL-GENERAL                           **********
   00:22 * 188 CHELSEA ST EVE
  928577 * 911-PARTY WITH A SORE FOOT
TEXT;

        $result = $probe->probeDailyText($text);

        $this->assertTrue($result['compatible']);
        $this->assertSame(1, $result['file_date_matches']);
        $this->assertSame(2, $result['parsed_entries_count']);
        $this->assertSame('928576', $result['sample_entry']['case_number']);
        $this->assertSame('FIREWORKS COMPLAINT', $result['sample_entry']['type']);
    }

    public function test_it_detects_arrest_log_parser_compatibility(): void
    {
        $probe = new EverettMarkdownCompatibilityProbe();

        $text = <<<TEXT
ARREST LOG

DOE, JOHN
123 MAIN ST
age: 34 arrest date: 01/05/2025 case: 123456
ASSAULT
DISORDERLY CONDUCT
TEXT;

        $result = $probe->probeArrestText($text);

        $this->assertTrue($result['compatible']);
        $this->assertSame(1, $result['name_matches']);
        $this->assertSame(1, $result['age_case_matches']);
        $this->assertSame(1, $result['parsed_entries_count']);
        $this->assertSame('123456', $result['sample_entry']['case_number']);
        $this->assertSame(['ASSAULT', 'DISORDERLY CONDUCT'], $result['sample_entry']['charges']);
    }
}
