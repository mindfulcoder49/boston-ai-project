<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SeoMetadata
{
    private const BRAND_NAME = 'PublicDataWatch';
    private const PRIMARY_HOST = 'https://publicdatawatch.com';

    public static function fromInertiaPage(array $page, Request $request): array
    {
        $component = (string) Arr::get($page, 'component', '');
        $props = Arr::get($page, 'props', []);

        $metadata = [
            'title' => 'PublicDataWatch | Civic Data Intelligence for Your City',
            'description' => 'Explore crime, 311, permits, inspections, and neighborhood trends across multiple cities with interactive maps and AI-powered analysis.',
            'canonical' => self::canonicalUrl($request),
            'robots' => self::robotsForComponent($component, $request),
            'og_type' => 'website',
            'site_name' => self::BRAND_NAME,
            'twitter_card' => 'summary',
        ];

        return array_merge($metadata, self::componentMetadata($component, $props));
    }

    private static function componentMetadata(string $component, array $props): array
    {
        return match ($component) {
            'Home' => [
                'title' => 'PublicDataWatch | Civic Data Intelligence for Your City',
                'description' => 'Explore crime data, 311 requests, permits, inspections, and neighborhood trends across Boston, Everett, Chicago, Seattle, New York, San Francisco, and more.',
            ],
            'Company/AboutUs' => [
                'title' => 'About PublicDataWatch | Civic Data Intelligence for Cities',
                'description' => 'Learn how PublicDataWatch turns public city data into interactive maps, AI summaries, and practical neighborhood insights.',
            ],
            'Help/Index' => [
                'title' => 'PublicDataWatch Help Center | User, City, and Research Guides',
                'description' => 'Find guides for residents, municipalities, researchers, and investors using PublicDataWatch.',
            ],
            'Help/ForUsers' => [
                'title' => 'How To Use PublicDataWatch | User Guide',
                'description' => 'Learn how to search addresses, explore maps, read records, and use AI-powered insights in PublicDataWatch.',
            ],
            'Help/ForMunicipalities' => [
                'title' => 'PublicDataWatch For Municipalities',
                'description' => 'See how PublicDataWatch helps cities communicate local data, map neighborhood conditions, and support civic transparency.',
            ],
            'Help/ForResearchers' => [
                'title' => 'PublicDataWatch For Researchers',
                'description' => 'Review the research and data-analysis capabilities available through PublicDataWatch for public-interest work.',
            ],
            'Help/ForInvestors' => [
                'title' => 'PublicDataWatch For Investors',
                'description' => 'Understand how PublicDataWatch surfaces neighborhood conditions, local development activity, and public-safety signals.',
            ],
            'Support/HelpContact' => [
                'title' => 'Help And Contact | PublicDataWatch',
                'description' => 'Contact PublicDataWatch support, browse FAQs, and submit product feedback or bug reports.',
            ],
            'Subscription' => [
                'title' => 'Pricing And Subscription Plans | PublicDataWatch',
                'description' => 'Compare PublicDataWatch plans for residents, researchers, and professionals who need deeper city-data access.',
            ],
            'CityMapLite' => self::cityLandingMetadata($props),
            'CombinedDataMap' => [
                'title' => 'Interactive Public Data Map | PublicDataWatch',
                'description' => 'Explore multiple city datasets on one map, including crime, 311 cases, inspections, permits, and property violations.',
            ],
            'DataMap' => [
                'title' => self::withBrand(self::singleDataMapTitle($props)),
                'description' => 'Browse a single city dataset on an interactive map with filters for time, place, and record details.',
            ],
            'RadialMap' => [
                'title' => 'Address-Based Public Data Map | PublicDataWatch',
                'description' => 'Search any address and explore nearby public records within a customizable radius.',
            ],
            'GenericMap' => [
                'title' => 'Public Data Map | PublicDataWatch',
                'description' => 'Explore mapped public records with filtering and AI-assisted summaries.',
            ],
            'News/Index' => [
                'title' => 'Data-Driven City News | PublicDataWatch',
                'description' => 'Read data-driven city news and neighborhood trend stories built from public records and analytics.',
            ],
            'News/Show' => self::newsArticleMetadata($props),
            'Legal/PrivacyPolicy' => [
                'title' => 'Privacy Policy | PublicDataWatch',
                'description' => 'Review how PublicDataWatch handles account information, analytics, and public-data interactions.',
            ],
            'Legal/TermsOfUse' => [
                'title' => 'Terms Of Use | PublicDataWatch',
                'description' => 'Read the terms governing use of PublicDataWatch, subscriptions, and platform features.',
            ],
            'Hotspots/Show' => self::hotspotsMetadata($props),
            default => [
                'title' => self::withBrand(self::fallbackTitle($component)),
            ],
        };
    }

    private static function cityLandingMetadata(array $props): array
    {
        $city = trim((string) Arr::get($props, 'city.name', 'Your City'));

        return [
            'title' => "{$city} Crime Map and Public Safety Data | PublicDataWatch",
            'description' => "Explore recent public-safety data in {$city} with an interactive map, address search, and multilingual record summaries.",
        ];
    }

    private static function newsArticleMetadata(array $props): array
    {
        $headline = trim((string) Arr::get($props, 'article.headline', ''));
        $summary = trim((string) Arr::get($props, 'article.summary', ''));

        return [
            'title' => self::withBrand($headline !== '' ? $headline : 'Data-Driven City News'),
            'description' => $summary !== ''
                ? $summary
                : 'Read a data-driven city news article based on public records, trend analysis, and neighborhood activity.',
            'og_type' => 'article',
        ];
    }

    private static function hotspotsMetadata(array $props): array
    {
        $city = trim((string) Arr::get($props, 'city.name', ''));

        return [
            'title' => self::withBrand($city !== '' ? "{$city} Hotspot Map" : 'Hotspot Map'),
            'description' => $city !== ''
                ? "Explore mapped hotspot activity and notable patterns in {$city}."
                : 'Explore mapped hotspot activity and notable patterns across city data.',
        ];
    }

    private static function singleDataMapTitle(array $props): string
    {
        $displayTitle = trim((string) Arr::get($props, 'dataTypeConfig.displayTitle', ''));
        $humanTitle = trim((string) Arr::get($props, 'dataTypeConfig.modelNameForHumans', ''));
        $baseTitle = $displayTitle !== '' ? $displayTitle : $humanTitle;

        return $baseTitle !== '' ? "{$baseTitle} Data Map" : 'City Dataset Map';
    }

    private static function fallbackTitle(string $component): string
    {
        if ($component === '') {
            return self::BRAND_NAME;
        }

        $segment = Str::of($component)->afterLast('/')->headline()->trim();
        return $segment !== '' ? (string) $segment : self::BRAND_NAME;
    }

    private static function withBrand(string $title): string
    {
        return Str::contains($title, self::BRAND_NAME) ? $title : "{$title} | " . self::BRAND_NAME;
    }

    private static function robotsForComponent(string $component, Request $request): string
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return 'noindex, nofollow';
        }

        if (Str::startsWith($component, ['Admin/', 'Auth/', 'Profile/'])) {
            return 'noindex, nofollow';
        }

        return 'index, follow';
    }

    private static function canonicalUrl(Request $request): string
    {
        $host = Str::lower($request->getHost());
        $baseUrl = in_array($host, ['publicdatawatch.com', 'www.publicdatawatch.com', 'bostonscope.com', 'www.bostonscope.com'], true)
            ? self::PRIMARY_HOST
            : $request->getSchemeAndHttpHost();

        $path = $request->getPathInfo() ?: '/';
        if ($path === '') {
            $path = '/';
        }

        return rtrim($baseUrl, '/') . $path;
    }
}
