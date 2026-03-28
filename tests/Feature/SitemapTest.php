<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('news_articles');
        Schema::create('news_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_sitemap_includes_redesign_priority_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee('https://publicdatawatch.com/');
        $response->assertSee('https://publicdatawatch.com/crime-address');
        $response->assertSee('https://publicdatawatch.com/trends');
        $response->assertSee('https://publicdatawatch.com/yearly-comparisons');
        $response->assertSee('https://publicdatawatch.com/scoring-reports');
    }
}
