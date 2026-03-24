<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    private const BASE_URL = 'https://publicdatawatch.com';

    public function index(): Response
    {
        $staticUrls = collect([
            ['loc' => self::BASE_URL . '/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => self::BASE_URL . '/about-us', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => self::BASE_URL . '/help', 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => self::BASE_URL . '/help/users', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => self::BASE_URL . '/help/municipalities', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => self::BASE_URL . '/help/researchers', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => self::BASE_URL . '/help/investors', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => self::BASE_URL . '/help-contact', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => self::BASE_URL . '/subscription', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => self::BASE_URL . '/map', 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => self::BASE_URL . '/combined-map', 'changefreq' => 'weekly', 'priority' => '0.7'],
            ['loc' => self::BASE_URL . '/hotspots', 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => self::BASE_URL . '/data-metrics', 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => self::BASE_URL . '/news', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => self::BASE_URL . '/privacy-policy', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => self::BASE_URL . '/terms-of-use', 'changefreq' => 'yearly', 'priority' => '0.3'],
        ]);

        $cityUrls = collect(array_keys(config('cities.cities', [])))
            ->map(fn (string $cityKey) => [
                'loc' => self::BASE_URL . '/' . str_replace('_', '-', $cityKey),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ]);

        $newsUrls = NewsArticle::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at', 'published_at'])
            ->map(fn (NewsArticle $article) => [
                'loc' => self::BASE_URL . '/news/' . $article->slug,
                'lastmod' => optional($article->updated_at ?? $article->published_at)?->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ]);

        $urls = $staticUrls
            ->concat($cityUrls)
            ->concat($newsUrls)
            ->values();

        return response()
            ->view('sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
