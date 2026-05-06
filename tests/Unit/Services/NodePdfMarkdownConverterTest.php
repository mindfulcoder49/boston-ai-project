<?php

namespace Tests\Unit\Services;

use App\Services\NodePdfMarkdownConverter;
use Tests\TestCase;

class NodePdfMarkdownConverterTest extends TestCase
{
    public function test_it_normalizes_pdf_parse_output_without_destroying_line_structure(): void
    {
        $converter = new class extends NodePdfMarkdownConverter
        {
            public function exposedConvertText(string $text): string
            {
                return $this->normalizeExtractedText($this->cleanExtractedText($text));
            }
        };

        $normalized = $converter->exposedConvertText(
            "DAILY LOG \t05/05/2026\r\n*** TUE 05/05/2026 OUT OF CITY CALL \t**********\r\n00:00 * (OOC) \tEVE\r\n965996 * OUT OF CITY\r\n\r\n-- 1 of 4 --\r\n"
        );

        $this->assertStringContainsString("DAILY LOG \t05/05/2026\n*** TUE 05/05/2026 OUT OF CITY CALL \t**********", $normalized);
        $this->assertStringContainsString("00:00 * (OOC) \tEVE", $normalized);
        $this->assertStringNotContainsString("-- 1 of 4 --", $normalized);
        $this->assertStringEndsWith("\n", $normalized);
    }

    public function test_it_resolves_the_repo_pdf_extractor_script_path(): void
    {
        $converter = new NodePdfMarkdownConverter();

        $this->assertSame(
            base_path('scripts/extract_pdf_text.cjs'),
            $converter->resolveExtractorScriptPath()
        );
    }
}
