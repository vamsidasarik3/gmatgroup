<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\StudyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudyGroupController extends Controller
{
    /**
     * List all study groups.
     */
    public function index(Request $request): View
    {
        $search = $request->query('q');

        $query = StudyGroup::with(['creator', 'members'])
            ->withCount('members')
            ->latest();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $studyGroups = $query->paginate(12)->withQueryString();

        return view('study_groups.index', compact('studyGroups', 'search'));
    }

    /**
     * Create a new study group.
     */
    public function store(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to create a study group.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:2', 'max:50'],
            'is_private' => ['nullable', 'boolean'],
        ]);

        $group = StudyGroup::create([
            'creator_id' => Auth::id(),
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(5),
            'description' => $data['description'],
            'capacity' => $data['capacity'] ?? 20,
            'is_private' => $request->boolean('is_private'),
        ]);

        // Attach creator as owner
        $group->members()->attach(Auth::id(), [
            'id' => (string) Str::uuid(),
            'role' => 'owner',
        ]);

        return redirect()->route('study-groups.show', $group->slug)
            ->with('success', 'Study Group created successfully!');
    }

    /**
     * Show a study group.
     */
    public function show(string $slug): View
    {
        $group = StudyGroup::where('slug', $slug)
            ->with(['creator', 'members', 'discussions.user'])
            ->withCount('members')
            ->firstOrFail();

        $isMember = Auth::check() && $group->members->contains(Auth::id());

        return view('study_groups.show', compact('group', 'isMember'));
    }

    /**
     * Join a study group.
     */
    public function join(string $slug): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to join study groups.');
        }

        $group = StudyGroup::where('slug', $slug)->firstOrFail();

        if ($group->members()->where('user_id', Auth::id())->exists()) {
            return back()->with('info', 'You are already a member of this study group.');
        }

        if ($group->members()->count() >= $group->capacity) {
            return back()->with('error', 'This study group has reached its maximum capacity.');
        }

        $group->members()->attach(Auth::id(), [
            'id' => (string) Str::uuid(),
            'role' => 'member',
        ]);

        return back()->with('success', 'You have successfully joined ' . $group->name . '!');
    }

    /**
     * Post a discussion in the study group.
     */
    public function postDiscussion(Request $request, string $slug): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in to post messages.');
        }

        $group = StudyGroup::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        Discussion::create([
            'user_id' => Auth::id(),
            'discussable_id' => $group->id,
            'discussable_type' => StudyGroup::class,
            'body' => $data['body'],
            'status' => 'approved',
        ]);

        return back()->with('success', 'Message posted to group discussion.');
    }
}

