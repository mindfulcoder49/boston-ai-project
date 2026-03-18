<?php

namespace App\Jobs;

use App\Http\Controllers\AiAssistantController;
use App\Models\HotspotFinding;
use App\Models\NewsArticle;
use App\Models\NewsArticleGenerationConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GenerateHotspotArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout     = 300;
    public $failOnTimeout = true;

    public function __construct(
        protected NewsArticle $article,
        protected string      $h3Index,
        protected string      $locationName,
        protected array       $hotspotContext,
        protected ?NewsArticleGenerationConfig $config = null,
    ) {}

    public function handle(): void
    {
        $this->article->update(['status' => 'generating', 'content' => 'AI generation in progress...']);

        $articleData = AiAssistantController::generateNewsArticleFromHexagon(
            $this->h3Index,
            $this->locationName,
            $this->hotspotContext,
            $this->config?->intro_prompt
        );

        if (!$articleData) {
            throw new \Exception("AI generation returned null for hotspot {$this->h3Index}.");
        }

        $baseSlug = $articleData['slug'] ?? Str::slug($articleData['headline']);
        $slug     = $baseSlug;
        $counter  = 1;
        while (NewsArticle::where('slug', $slug)->where('id', '!=', $this->article->id)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $this->article->update([
            'title'        => $articleData['title'],
            'slug'         => $slug,
            'headline'     => $articleData['headline'],
            'summary'      => $articleData['summary'],
            'content'      => $articleData['content'],
            'status'       => 'published',
            'published_at' => now(),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error("GenerateHotspotArticleJob failed for {$this->h3Index}: " . $exception->getMessage());
        $this->article->update([
            'status'  => 'error',
            'content' => 'AI generation failed: ' . $exception->getMessage(),
        ]);
    }
}
