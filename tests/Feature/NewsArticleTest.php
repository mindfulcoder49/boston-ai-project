<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\YearlyCountComparison;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsArticleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::statement('PRAGMA foreign_keys=ON');

        Schema::create('yearly_count_comparisons', function (Blueprint $table): void {
            $table->id();
            $table->string('model_class');
            $table->string('group_by_col');
            $table->integer('baseline_year');
            $table->string('job_id');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('manual_subscription_tier')->nullable();
            $table->timestamps();
        });

        Schema::create('saved_maps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('h3_location_names', function (Blueprint $table): void {
            $table->id();
            $table->string('h3_index')->unique();
            $table->string('location_name')->nullable();
            $table->timestamps();
        });

        Schema::create('news_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->unique();
            $table->string('headline')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('source_model_class')->nullable();
            $table->unsignedBigInteger('source_report_id')->nullable();
            $table->string('status')->nullable();
            $table->string('job_id')->nullable();
            $table->string('completion_job_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('h3_location_names');
        Schema::dropIfExists('saved_maps');
        Schema::dropIfExists('users');
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('yearly_count_comparisons');

        parent::tearDown();
    }

    public function test_show_uses_job_id_when_linking_yearly_comparison_source_reports(): void
    {
        $report = YearlyCountComparison::query()->create([
            'model_class' => \App\Models\ThreeOneOneCase::class,
            'group_by_col' => 'type',
            'baseline_year' => 2025,
            'job_id' => 'laravel-three-one-one-case-type-yearly-2025-1772947506',
        ]);

        $article = NewsArticle::query()->create([
            'title' => 'Snow story',
            'slug' => 'snow-story',
            'headline' => 'Snow story',
            'summary' => 'Snow summary',
            'content' => 'Snow content',
            'source_model_class' => YearlyCountComparison::class,
            'source_report_id' => $report->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('news.show', $article));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('News/Show')
            ->where('article.source_report_url', route('reports.yearly-comparison.show', ['jobId' => $report->job_id]))
            ->where('article.headline', 'Snow story')
        );
    }
}
