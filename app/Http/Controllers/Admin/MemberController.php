<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionModel;
use App\Models\Member;
use App\Models\MemberRelationship;
use App\Models\SalesTransaction;
use App\Services\HierarchyDataProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberController extends Controller
{
    /**
     * Display members list with search, status filters and statistics.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Member::with(['parentRelationships.parentMember', 'childRelationships'])
            ->withCount('salesTransactions')
            ->withSum('salesTransactions', 'sale_amount');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $members = $query->orderBy('id', 'asc')->paginate(15)->withQueryString();

        return view('admin.members.index', [
            'members' => $members,
            'search' => $search,
            'status' => $status,
            'totalCount' => Member::count(),
            'activeCount' => Member::where('status', 'active')->count(),
            'inactiveCount' => Member::where('status', 'inactive')->count(),
        ]);
    }

    /**
     * Show form to create a new member.
     */
    public function create(): View
    {
        $potentialParents = Member::where('status', 'active')->orderBy('name')->get();
        $models = CommissionModel::where('status', 'active')->get();

        return view('admin.members.create', [
            'potentialParents' => $potentialParents,
            'models' => $models,
        ]);
    }

    /**
     * Store newly created member in database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_code' => ['required', 'string', 'max:50', 'unique:members,member_code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:members,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'parent_id' => ['nullable', 'exists:members,id'],
            'override_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_model_id' => ['nullable', 'exists:commission_models,id'],
        ]);

        DB::transaction(function () use ($validated) {
            $member = Member::create([
                'member_code' => strtoupper($validated['member_code']),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
                'joined_at' => now(),
            ]);

            // If a parent was selected, create the relationship edge
            if (! empty($validated['parent_id'])) {
                MemberRelationship::create([
                    'parent_member_id' => $validated['parent_id'],
                    'child_member_id' => $member->id,
                    'override_rate' => $validated['override_rate'] ?? 4.0000,
                    'commission_model_id' => $validated['commission_model_id'] ?? null,
                    'status' => 'active',
                    'effective_from' => now()->toDateString(),
                ]);
            }
        });

        return redirect()->route('admin.members.index')
            ->with('success', 'Member ' . $validated['name'] . ' successfully registered.');
    }

    /**
     * Show form to edit member.
     */
    public function edit(Member $member): View
    {
        $potentialParents = Member::where('id', '!=', $member->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $currentParentRel = MemberRelationship::where('child_member_id', $member->id)->first();
        $models = CommissionModel::all();

        return view('admin.members.edit', [
            'member' => $member,
            'potentialParents' => $potentialParents,
            'currentParentRel' => $currentParentRel,
            'models' => $models,
        ]);
    }

    /**
     * Update member in database.
     */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'member_code' => ['required', 'string', 'max:50', 'unique:members,member_code,' . $member->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:members,email,' . $member->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'parent_id' => ['nullable', 'exists:members,id', 'different:id'],
            'override_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'commission_model_id' => ['nullable', 'exists:commission_models,id'],
        ]);

        if (! empty($validated['parent_id'])) {
            if ($this->hasCycle((int) $validated['parent_id'], (int) $member->id)) {
                return back()->with('error', 'Cycle detected: Setting this parent would create an invalid loop in the hierarchy tree.')->withInput();
            }
        }

        DB::transaction(function () use ($member, $validated) {
            $member->update([
                'member_code' => strtoupper($validated['member_code']),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
            ]);

            // Update or assign direct parent relationship
            if (! empty($validated['parent_id'])) {
                MemberRelationship::updateOrCreate(
                    [
                        'child_member_id' => $member->id,
                    ],
                    [
                        'parent_member_id' => $validated['parent_id'],
                        'override_rate' => $validated['override_rate'] ?? 4.0000,
                        'commission_model_id' => $validated['commission_model_id'] ?? null,
                        'status' => 'active',
                    ]
                );
            } else {
                // If parent cleared, remove relationship
                MemberRelationship::where('child_member_id', $member->id)->delete();
            }
        });

        return redirect()->route('admin.members.index')
            ->with('success', 'Member ' . $member->name . ' updated successfully.');
    }

    /**
     * Manage relationships and override rates for a specific member.
     */
    public function relationships(Member $member): View
    {
        $parentRelationships = MemberRelationship::with('parentMember', 'commissionModel')
            ->where('child_member_id', $member->id)
            ->get();

        $childRelationships = MemberRelationship::with('childMember', 'commissionModel')
            ->where('parent_member_id', $member->id)
            ->get();

        $allOtherMembers = Member::where('id', '!=', $member->id)->orderBy('name')->get();
        $models = CommissionModel::all();

        return view('admin.members.relationships', [
            'member' => $member,
            'parentRelationships' => $parentRelationships,
            'childRelationships' => $childRelationships,
            'allOtherMembers' => $allOtherMembers,
            'models' => $models,
        ]);
    }

    /**
     * Store a new relationship edge with override rate.
     */
    public function storeRelationship(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'relation_type' => ['required', 'in:parent,child'],
            'target_member_id' => ['required', 'exists:members,id', 'different:id'],
            'override_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'commission_model_id' => ['nullable', 'exists:commission_models,id'],
        ]);

        $parentId = $validated['relation_type'] === 'parent' ? $validated['target_member_id'] : $member->id;
        $childId = $validated['relation_type'] === 'parent' ? $member->id : $validated['target_member_id'];

        // Prevent cycle (if child is already ancestor of parent)
        if ($this->hasCycle($parentId, $childId)) {
            return back()->with('error', 'Cycle detected: This relationship would create an invalid loop in the hierarchy tree.');
        }

        MemberRelationship::updateOrCreate(
            [
                'parent_member_id' => $parentId,
                'child_member_id' => $childId,
            ],
            [
                'override_rate' => $validated['override_rate'],
                'commission_model_id' => $validated['commission_model_id'] ?? null,
                'status' => 'active',
                'effective_from' => now()->toDateString(),
            ]
        );

        return back()->with('success', 'Relationship and override rate assigned successfully.');
    }

    /**
     * Remove a relationship edge.
     */
    public function deleteRelationship(MemberRelationship $relationship): RedirectResponse
    {
        $relationship->delete();

        return back()->with('success', 'Relationship removed successfully.');
    }

    /**
     * Embedded hierarchy inspection view within the administrative dashboard.
     */
    public function hierarchy(): View
    {
        $provider = new HierarchyDataProvider();
        $data = $provider->getHierarchyData();

        return view('admin.members.hierarchy', [
            'sales' => $data['sales'],
        ]);
    }

    /**
     * Helper to detect cycle when connecting parent and child.
     */
    protected function hasCycle(int $parentId, int $childId): bool
    {
        if ($parentId === $childId) {
            return true;
        }

        // Trace upwards from proposed parent: if we encounter childId, it is a cycle
        $curr = $parentId;
        $visited = [];

        while ($curr) {
            if ($curr === $childId) {
                return true;
            }
            if (in_array($curr, $visited)) {
                break;
            }
            $visited[] = $curr;
            $parentEdge = MemberRelationship::where('child_member_id', $curr)->first();
            $curr = $parentEdge ? $parentEdge->parent_member_id : null;
        }

        return false;
    }
}
