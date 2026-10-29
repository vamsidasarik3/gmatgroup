<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TopicController extends Controller
{
    /**
     * Display topic syllabus / curriculum map.
     */
    public function index(): View
    {
        $rootTopics = Topic::whereNull('parent_id')
            ->with(['children.children', 'children.articles'])
            ->withCount(['articles'])
            ->orderBy('position')
            ->get();

        return view('topics.index', compact('rootTopics'));
    }

    /**
     * Show a topic and its articles.
     */
    public function show(string $slug, Request $request): View
    {
        $topic = Topic::where('slug', $slug)
            ->with(['parent', 'children'])
            ->firstOrFail();

        // Include child topics
        $allTopicIds = $topic->children()->pluck('id')->push($topic->id);

        $articles = Article::published()
            ->whereIn('topic_id', $allTopicIds)
            ->with(['contentType'])
            ->latest('published_at')
            ->paginate(15);

        return view('topics.show', compact('topic', 'articles'));
    }
}

