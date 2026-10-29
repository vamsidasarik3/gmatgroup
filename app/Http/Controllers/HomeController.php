<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ContentType;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Render the public GmatGroup learning portal home.
     */
    public function index(Request $request): View
    {
        $search = $request->query('q');

        // Root 4 pillars
        $rootTopics = Topic::whereNull('parent_id')
            ->with(['children.articles'])
            ->withCount(['articles'])
            ->orderBy('position')
            ->get();

        // Content types
        $contentTypes = ContentType::withCount('articles')->get();

        // Articles (Featured or Search query or Latest)
        $articlesQuery = Article::published()
            ->with(['topic.parent', 'contentType'])
            ->search($search)
            ->latest('published_at');

        $articles = $articlesQuery->paginate(12)->withQueryString();

        // Latest articles and news updates for the dedicated homepage showcase
        $latestArticles = Article::published()
            ->with(['topic', 'contentType'])
            ->latest('published_at')
            ->take(4)
            ->get();

        // Featured highlighted guides
        $featuredArticles = Article::published()
            ->with(['topic', 'contentType'])
            ->featured()
            ->latest('published_at')
            ->take(6)
            ->get();

        if ($featuredArticles->isEmpty()) {
            $featuredArticles = $latestArticles;
        }

        // Stats
        $stats = [
            'total_articles' => Article::count(),
            'total_topics' => Topic::count(),
            'total_content_types' => ContentType::count(),
            'total_reads' => Article::sum('views_count'),
        ];

        return view('home', compact(
            'rootTopics',
            'contentTypes',
            'articles',
            'featuredArticles',
            'latestArticles',
            'stats',
            'search'
        ));
    }
}

