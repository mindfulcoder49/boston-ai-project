<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NewsArticleController extends Controller
{
    public function index()
    {
        $articles = NewsArticle::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->paginate(10)
            ->through(fn ($article) => [
                'id' => $article->id,
                'headline' => $article->headline,
                'summary' => $article->summary,
                'slug' => $article->slug,
                'published_at' => $article->published_at->format('F j, Y'),
                'source_model_name' => $this->getSourceModelName($article->source_model_class),
            ]);

        return Inertia::render('News/Index', [
            'articles' => $articles,
        ]);
    }

    public function show(NewsArticle $newsArticle)
    {
        if ($newsArticle->status !== 'published' && $newsArticle->status !== 'generating') {
            abort(404);
        }

        $sourceReportUrl = $this->getSourceReportUrl($newsArticle);
        $articleContent = $newsArticle->content;

        // If it's a locally generated article, fetch the content from S3.
        if ($newsArticle->completion_job_id) {
            $articleContent = $this->fetchContentFromCompletionJob($newsArticle);
        }

        return Inertia::render('News/Show', [
            'article' => [
                'id' => $newsArticle->id,
                'headline' => $newsArticle->headline,
                'content' => $articleContent,
                'published_at' => $newsArticle->published_at ? $newsArticle->published_at->format('F j, Y, g:i A') : 'Not yet published',
                'source_model_name' => $this->getSourceModelName($newsArticle->source_model_class),
                'source_report_url' => $sourceReportUrl,
            ],
        ]);
    }

    private function fetchContentFromCompletionJob(NewsArticle $article): string
    {
        if (!$article->completion_job_id) {
            return $article->content; // Fallback to DB content
        }

        $s3Path = "{$article->completion_job_id}/completion.json";
        
        try {
            $s3 = Storage::disk('s3');
            if ($s3->exists($s3Path)) {
                $jsonContent = $s3->get($s3Path);
                $data = json_decode($jsonContent, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // The python task saves the AI response inside a 'response' key,
                    // which itself is a JSON string.
                    if (isset($data['response']) && is_string($data['response'])) {
                        $articleData = json_decode($data['response'], true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($articleData['content'])) {
                            // On first successful fetch, update the article in the DB
                            if ($article->status === 'generating') {
                                $baseSlug = Str::slug($articleData['headline']);
                                $slug = $baseSlug;
                                $counter = 1;
                                while (NewsArticle::where('slug', $slug)->where('id', '!=', $article->id)->exists()) {
                                    $slug = $baseSlug . '-' . $counter++;
                                }
                                $article->update([
                                    'title' => $articleData['headline'],
                                    'slug' => $slug,
                                    'headline' => $articleData['headline'],
                                    'summary' => $articleData['summary'],
                                    'content' => $articleData['content'], // Store it now
                                    'status' => 'published',
                                    'published_at' => now(),
                                ]);
                            }
                            return $articleData['content'];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to fetch or process completion job content from S3 for article {$article->id}", [
                'path' => $s3Path,
                'error' => $e->getMessage(),
            ]);
            return "Error fetching generated content. Please check the logs.";
        }

        return $article->content; // Fallback
    }

    private function getSourceModelName(string $modelClass): string
    {
        if (class_exists($modelClass) && method_exists($modelClass, 'getHumanName')) {
            return $modelClass::getHumanName();
        }
        return Str::of(class_basename($modelClass))->snake(' ')->title();
    }

    private function getSourceReportUrl(NewsArticle $article): ?string
    {
        $source = $article->source;
        if (!$source) {
            return null;
        }

        if ($source instanceof \App\Models\Trend) {
            return route('reports.statistical-analysis.show', ['trendId' => $source->id]);
        }

        if ($source instanceof \App\Models\YearlyCountComparison) {
            return route('reports.yearly-comparison.show', ['reportId' => $source->id]);
        }

        return null;
    }
}
