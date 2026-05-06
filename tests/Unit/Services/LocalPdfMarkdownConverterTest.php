<?php

namespace Tests\Unit\Services;

use App\Services\LocalPdfMarkdownConverter;
use Tests\TestCase;

class LocalPdfMarkdownConverterTest extends TestCase
{
    public function test_it_normalizes_pdftotext_output_without_destroying_line_structure(): void
    {
        $converter = new LocalPdfMarkdownConverter();

        $normalized = $converter->normalizeExtractedText(
            "DAILY LOG           01/01/2025   \r\n\r\n*** WED 01/01/2025 FIREWORKS COMPLAINT **********   \r\n   00:08 * 410 MAIN ST EVE   \r\n  928576 * 911-PARTIES LIGHTING FIREWORKS IN STREET   \f\r\n"
        );

        $this->assertStringContainsString("DAILY LOG           01/01/2025\n\n*** WED 01/01/2025 FIREWORKS COMPLAINT **********", $normalized);
        $this->assertStringContainsString("00:08 * 410 MAIN ST EVE", $normalized);
        $this->assertStringContainsString("928576 * 911-PARTIES LIGHTING FIREWORKS IN STREET", $normalized);
        $this->assertStringEndsWith("\n", $normalized);
    }
}
