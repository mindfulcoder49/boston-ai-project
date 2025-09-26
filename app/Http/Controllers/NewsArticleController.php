<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

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
        if ($newsArticle->status !== 'published') {
            abort(404);
        }

        $sourceReportUrl = $this->getSourceReportUrl($newsArticle);

        return Inertia::render('News/Show', [
            'article' => [
                'id' => $newsArticle->id,
                'headline' => $newsArticle->headline,
                'content' => $newsArticle->content,
                'published_at' => $newsArticle->published_at->format('F j, Y, g:i A'),
                'source_model_name' => $this->getSourceModelName($newsArticle->source_model_class),
                'source_report_url' => $sourceReportUrl,
            ],
        ]);
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
