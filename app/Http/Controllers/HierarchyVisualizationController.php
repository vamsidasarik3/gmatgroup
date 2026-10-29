<?php

namespace App\Http\Controllers;

use App\Services\HierarchyDataProvider;
use App\Services\LevelOverrideCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HierarchyVisualizationController extends Controller
{
    public function __construct(
        protected HierarchyDataProvider $dataProvider,
        protected LevelOverrideCommissionService $overrideService
    ) {}

    /**
     * Render the interactive hierarchy visualization page via Blade.
     */
    public function index(): View
    {
        $data = $this->dataProvider->getHierarchyData();

        $stats = [
            'total_members' => count($data['members']),
            'total_branches' => 3,
            'max_depth' => 6,
            'total_sales' => array_sum(array_column($data['sales'], 'sale_amount')),
            'total_commissions' => array_sum(array_column($data['members'], 'commission_earned')),
            'sales_count' => count($data['sales']),
        ];

        return view('hierarchy.index', [
            'initialStats' => $stats,
            'sales' => $data['sales'],
        ]);
    }

    /**
     * API endpoint returning validated JSON hierarchy graph data.
     */
    public function getHierarchyData(): JsonResponse
    {
        $data = $this->dataProvider->getHierarchyData();
        $members = $data['members'];
        $sales = $data['sales'];
        $maxGenerations = $data['max_generations'] ?? 5;

        // Build edges and child mappings
        $edges = [];
        $childrenMap = [];
        $memberMap = [];

        foreach ($members as $m) {
            $memberMap[$m['id']] = $m;
            $childrenMap[$m['id']] = [];
        }

        foreach ($members as $m) {
            if ($m['parent_id'] !== null) {
                $parentId = $m['parent_id'];
                $edges[] = [
                    'id' => "edge-{$parentId}-{$m['id']}",
                    'source' => $parentId,
                    'target' => $m['id'],
                    'rate' => $m['override_rate'],
                    'parent_name' => $memberMap[$parentId]['name'] ?? '',
                    'child_name' => $m['name'],
                ];
                $childrenMap[$parentId][] = $m['id'];
            }
        }

        // Attach child count and child IDs to each node
        $nodes = [];
        foreach ($members as $m) {
            $node = $m;
            $node['children_ids'] = $childrenMap[$m['id']];
            $node['has_children'] = ! empty($childrenMap[$m['id']]);
            $nodes[] = $node;
        }

        return response()->json([
            'success' => true,
            'nodes' => $nodes,
            'edges' => $edges,
            'sales' => $sales,
            'max_generations' => $maxGenerations,
            'summary' => [
                'total_members' => count($nodes),
                'total_edges' => count($edges),
                'total_sales_count' => count($sales),
                'total_sales_volume' => array_sum(array_column($sales, 'sale_amount')),
                'total_commissions_earned' => array_sum(array_column($nodes, 'commission_earned')),
            ],
        ]);
    }

    /**
     * API endpoint returning calculation path and line-item breakdown for a selected sale.
     */
    public function calculateSalePath(Request $request): JsonResponse
    {
        $saleId = (int) $request->input('sale_id');
        $data = $this->dataProvider->getHierarchyData();

        $selectedSale = null;
        foreach ($data['sales'] as $s) {
            if ($s['id'] === $saleId) {
                $selectedSale = $s;
                break;
            }
        }

        // If not in mock provider data, check database SalesTransaction
        if (! $selectedSale) {
            $dbSale = \App\Models\SalesTransaction::with('member')->find($saleId);
            if ($dbSale) {
                $selectedSale = [
                    'id' => $dbSale->id,
                    'code' => $dbSale->transaction_code,
                    'seller_id' => $dbSale->member_id,
                    'seller_name' => $dbSale->member->name ?? 'Seller',
                    'sale_amount' => (float) $dbSale->sale_amount,
                    'date' => $dbSale->transaction_date ? \Carbon\Carbon::parse($dbSale->transaction_date)->format('Y-m-d') : now()->toDateString(),
                    'description' => $dbSale->reference_note ?? 'Sales Transaction',
                ];
            }
        }

        if (! $selectedSale) {
            return response()->json([
                'success' => false,
                'message' => 'Selected sale transaction was not found.',
            ], 404);
        }

        $sellerId = $selectedSale['seller_id'];
        $saleAmount = (float) $selectedSale['sale_amount'];
        $maxGenerations = (int) ($data['max_generations'] ?? 5);

        // Map parent lookup (provider + database)
        $parentMap = [];
        $memberMap = [];
        foreach ($data['members'] as $m) {
            $memberMap[$m['id']] = $m;
            if ($m['parent_id'] !== null) {
                $parentMap[$m['id']] = [
                    'parent_id' => $m['parent_id'],
                    'rate' => (float) $m['override_rate'],
                ];
            }
        }

        // Merge active database relationships
        $dbRels = \App\Models\MemberRelationship::where('status', 'active')->get();
        foreach ($dbRels as $rel) {
            $parentMap[$rel->child_member_id] = [
                'parent_id' => $rel->parent_member_id,
                'rate' => (float) $rel->override_rate,
            ];
        }

        // Merge database members
        $dbMembers = \App\Models\Member::all();
        foreach ($dbMembers as $m) {
            if (! isset($memberMap[$m->id])) {
                $memberMap[$m->id] = [
                    'id' => $m->id,
                    'member_code' => $m->member_code,
                    'name' => $m->name,
                    'level' => 1,
                    'parent_id' => $parentMap[$m->id]['parent_id'] ?? null,
                    'override_rate' => $parentMap[$m->id]['rate'] ?? 0.0,
                    'status' => $m->status,
                ];
            }
        }

        // Walk upline chain
        $pathNodes = [$sellerId];
        $pathEdges = [];
        $generations = [];
        $totalCommission = 0.00;

        $curr = $sellerId;
        $gen = 1;

        while (isset($parentMap[$curr])) {
            $upline = $parentMap[$curr];
            $parentId = $upline['parent_id'];
            $rate = $upline['rate'];

            $isEligible = ($gen <= $maxGenerations);
            $commission = $isEligible
                ? $this->overrideService->calculateIndividualCommission($saleAmount, $rate)
                : 0.00;

            if ($isEligible) {
                $totalCommission += $commission;
            }

            $pathNodes[] = $parentId;
            $pathEdges[] = "edge-{$parentId}-{$curr}";

            $parentObj = $memberMap[$parentId] ?? [];

            $generations[] = [
                'generation' => $gen,
                'member_id' => $parentId,
                'member_code' => $parentObj['member_code'] ?? '',
                'member_name' => $parentObj['name'] ?? '',
                'title' => $parentObj['title'] ?? '',
                'level' => $parentObj['level'] ?? 0,
                'rate_applied' => $rate,
                'sale_base' => $saleAmount,
                'commission_amount' => $commission,
                'is_eligible' => $isEligible,
                'status' => $isEligible ? 'approved' : 'ineligible',
                'status_label' => $isEligible ? 'Eligible Payout' : "Cutoff (Limit: {$maxGenerations})",
            ];

            $curr = $parentId;
            $gen++;
        }

        return response()->json([
            'success' => true,
            'sale' => $selectedSale,
            'max_generations' => $maxGenerations,
            'path_nodes' => array_values(array_unique($pathNodes)),
            'path_edges' => $pathEdges,
            'seller' => $memberMap[$sellerId] ?? null,
            'generation_breakdown' => $generations,
            'total_commission_payable' => round($totalCommission, 2),
            'eligible_upline_count' => count(array_filter($generations, fn ($g) => $g['is_eligible'])),
            'ineligible_upline_count' => count(array_filter($generations, fn ($g) => ! $g['is_eligible'])),
        ]);
    }
}
