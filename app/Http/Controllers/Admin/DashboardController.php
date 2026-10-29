<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ContentType;
use App\Models\Discussion;
use App\Models\StudyGroup;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the GmatGroup administrative dashboard with platform metrics.
     */
    public function index(Request $request): View
    {
        $metrics = [
            'total_articles' => Article::count(),
            'published_articles' => Article::where('status', 'published')->count(),
            'draft_articles' => Article::where('status', 'draft')->count(),
            'total_topics' => Topic::count(),
            'total_content_types' => ContentType::count(),
            'total_users' => User::count(),
            'total_views' => Article::sum('views_count'),
            'total_discussions' => Discussion::count(),
            'total_study_groups' => StudyGroup::count(),
        ];

        // Pillar breakdown
        $pillars = Topic::whereNull('parent_id')
            ->withCount('articles')
            ->with(['children' => function ($q) {
                $q->withCount('articles');
            }])
            ->orderBy('position')
            ->get();

        // Content type distribution
        $contentTypes = ContentType::withCount('articles')->get();

        // Recent articles
        $recentArticles = Article::with(['topic', 'contentType'])
            ->latest('created_at')
            ->take(10)
            ->get();

        // Recent users
        $recentUsers = User::latest('created_at')->take(5)->get();

        return view('admin.dashboard', compact(
            'metrics',
            'pillars',
            'contentTypes',
            'recentArticles',
            'recentUsers'
        ));
    }
}
