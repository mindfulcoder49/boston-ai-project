<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

class PdfLinkExtractorService
{
    public function extractFromHtml(string $html, string $baseUrl): array
    {
        $pdfLinks = [];
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $anchors = $xpath->query("//a[@href]");
        $baseUri = new Uri($baseUrl);

        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            $cleanedHref = trim(preg_replace('/^[\\\\\'"]+|[\\\\\'"]+$/', '', $href));
            if (str_ends_with(strtolower($cleanedHref), '.pdf')) {
                try {
                    $relativeUri = new Uri($cleanedHref);
                    $resolvedUri = UriResolver::resolve($baseUri, $relativeUri);
                    $pdfLinks[] = (string)$resolvedUri;
                } catch (\InvalidArgumentException $e) {
                    // Skip invalid URIs.
                }
            }
        }
        return array_unique($pdfLinks);
    }
}
