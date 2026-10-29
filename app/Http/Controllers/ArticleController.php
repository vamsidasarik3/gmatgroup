<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ContentType;
use App\Models\Discussion;
use App\Models\Reaction;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /**
     * Display articles library with search & filter.
     */
    public function index(Request $request): View
    {
        $search = $request->query('q');
        $topicSlug = $request->query('topic');
        $typeSlug = $request->query('type');

        $query = Article::published()->with(['topic.parent', 'contentType'])->latest('published_at');

        if ($search) {
            $query->search($search);
        }

        $activeTopic = null;
        if ($topicSlug) {
            $activeTopic = Topic::where('slug', $topicSlug)->first();
            if ($activeTopic) {
                // Include child topics if this is a parent
                $topicIds = $activeTopic->children()->pluck('id')->push($activeTopic->id);
                $query->whereIn('topic_id', $topicIds);
            }
        }

        $activeType = null;
        if ($typeSlug) {
            $activeType = ContentType::where('slug', $typeSlug)->first();
            if ($activeType) {
                $query->where('content_type_id', $activeType->id);
            }
        }

        $articles = $query->paginate(15)->withQueryString();

        $topics = Topic::whereNull('parent_id')->with('children')->orderBy('position')->get();
        $contentTypes = ContentType::withCount('articles')->get();

        return view('articles.index', compact(
            'articles',
            'topics',
            'contentTypes',
            'search',
            'activeTopic',
            'activeType'
        ));
    }

    /**
     * Show a single article guide.
     */
    public function show(string $slug): View
    {
        $article = Article::where('slug', $slug)
            ->with(['topic.parent', 'contentType', 'tags', 'discussions.user', 'discussions.replies.user'])
            ->firstOrFail();

        // Increment views count safely
        $article->increment('views_count');

        // Related articles from same topic
        $relatedArticles = Article::published()
            ->where('topic_id', $article->topic_id)
            ->where('id', '!=', $article->id)
            ->take(4)
            ->get();

        // Reactions count summary
        $reactionCounts = Reaction::where('reactable_id', $article->id)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return view('articles.show', compact('article', 'relatedArticles', 'reactionCounts'));
    }

    /**
     * Post a question or comment on an article.
     */
    public function postDiscussion(Request $request, string $slug): RedirectResponse
    {
        $article = Article::where('slug', $slug)->firstOrFail();

        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to join the discussion.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'parent_id' => ['nullable', 'string'],
        ]);

        Discussion::create([
            'user_id' => Auth::id(),
            'parent_id' => $data['parent_id'] ?? null,
            'discussable_id' => $article->id,
            'discussable_type' => Article::class,
            'body' => $data['body'],
            'status' => 'approved',
        ]);

        return back()->with('success', 'Your contribution has been posted to the discussion!');
    }

    /**
     * React to an article (Helpful, Insightful, etc.).
     */
    public function toggleReaction(Request $request, string $slug): RedirectResponse
    {
        $article = Article::where('slug', $slug)->firstOrFail();

        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to react to articles.');
        }

        $type = $request->input('type', 'helpful');
        if (!in_array($type, ['helpful', 'insightful', 'like', 'confusing'], true)) {
            $type = 'helpful';
        }

        $existing = Reaction::where('user_id', Auth::id())
            ->where('reactable_id', $article->id)
            ->where('reactable_type', Article::class)
            ->where('type', $type)
            ->first();

        if ($existing) {
            $existing->delete();
            $msg = 'Reaction removed.';
        } else {
            Reaction::create([
                'user_id' => Auth::id(),
                'reactable_id' => $article->id,
                'reactable_type' => Article::class,
                'type' => $type,
            ]);
            $msg = 'Thank you for your feedback!';
        }

        return back()->with('success', $msg);
    }
}

