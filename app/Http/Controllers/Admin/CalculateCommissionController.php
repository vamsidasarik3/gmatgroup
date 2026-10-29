<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalculationRun;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionModel;
use App\Models\Member;
use App\Models\MemberRelationship;
use App\Models\SalesTransaction;
use App\Services\LevelOverrideCommissionService;
use App\Services\WeakestLinkCommissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CalculateCommissionController extends Controller
{
    public function __construct(
        protected LevelOverrideCommissionService $overrideService,
        protected WeakestLinkCommissionService $weakestLinkService
    ) {}

    /**
     * Display calculation launchpad and configuration form.
     */
    public function index(): View
    {
        $models = CommissionModel::where('status', 'active')->get();
        $recentRuns = CalculationRun::with('commissionModel')->latest()->take(5)->get();
        $completedSalesCount = SalesTransaction::where('status', 'completed')->count();
        $totalSalesVolume = SalesTransaction::where('status', 'completed')->sum('sale_amount');

        return view('admin.calculate.index', [
            'models' => $models,
            'recentRuns' => $recentRuns,
            'completedSalesCount' => $completedSalesCount,
            'totalSalesVolume' => $totalSalesVolume,
        ]);
    }

    /**
     * Execute server-side commission calculation run within database transaction.
     */
    public function run(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'commission_model_id' => ['required', 'exists:commission_models,id'],
            'generation_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
        ]);

        $model = CommissionModel::findOrFail($validated['commission_model_id']);
        $genLimit = (int) $validated['generation_limit'];
        $periodStart = $validated['period_start'] ?? now()->startOfMonth()->toDateString();
        $periodEnd = $validated['period_end'] ?? now()->endOfMonth()->toDateString();

        // 1. Concurrency control: prevent concurrent executions for the same model
        $activeRun = CalculationRun::where('commission_model_id', $model->id)
            ->where('status', 'processing')
            ->where('started_at', '>=', now()->subMinutes(15))
            ->first();

        if ($activeRun) {
            return back()->with('error', "Calculation run {$activeRun->run_number} is already in progress for this model. Concurrent executions are locked to prevent race conditions and duplicate ledger entries.");
        }

        $runNumber = 'RUN-' . date('Ym') . '-' . strtoupper(Str::random(5));
        $runUuid = (string) Str::uuid();

        // Fetch eligible completed sales transactions
        $sales = SalesTransaction::where('status', 'completed')->get();
        if ($sales->isEmpty()) {
            return back()->with('error', 'No completed sales transactions available for calculation.');
        }

        // Fetch all active member relationships
        $relationships = MemberRelationship::where('status', 'active')->get();
        $parentLookup = [];
        $childrenLookup = [];
        foreach ($relationships as $rel) {
            $parentLookup[$rel->child_member_id] = [
                'parent_id' => $rel->parent_member_id,
                'rate' => (float) $rel->override_rate,
            ];
            $childrenLookup[$rel->parent_member_id][] = [
                'child_id' => $rel->child_member_id,
                'rate' => (float) $rel->override_rate,
            ];
        }

        $totalSalesAmount = (float) $sales->sum('sale_amount');
        $totalCommissionPayable = 0.00;
        $beneficiaries = [];
        $entriesCreated = 0;
        $run = null;

        try {
            DB::transaction(function () use (
                $model, $runUuid, $runNumber, $periodStart, $periodEnd, $genLimit,
                $totalSalesAmount, $sales, $parentLookup, $childrenLookup,
                &$totalCommissionPayable, &$beneficiaries, &$entriesCreated, &$run
            ) {
                // 1. Create immutable Run Header with processing status
                $run = CalculationRun::create([
                    'run_uuid' => $runUuid,
                    'run_number' => $runNumber,
                    'commission_model_id' => $model->id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'status' => 'processing',
                    'generation_limit' => $genLimit,
                    'total_sales_amount' => $totalSalesAmount,
                    'total_commission_payable' => 0.00,
                    'total_beneficiaries_count' => 0,
                    'settings_snapshot' => $model->settings ?? [],
                    'started_at' => now(),
                ]);

                $isWeakestLink = ($model->model_type === 'weakest_link');

                if ($isWeakestLink) {
                    // Model 1: Weakest Link multi-branch calculation
                    $memberSales = [];
                    foreach ($sales as $s) {
                        $memberSales[$s->member_id] = ($memberSales[$s->member_id] ?? 0.0) + (float) $s->sale_amount;
                    }

                    foreach ($childrenLookup as $leaderId => $childBranches) {
                        $branchCommissions = [];
                        foreach ($childBranches as $cb) {
                            $childId = $cb['child_id'];
                            $branchVolume = $memberSales[$childId] ?? 0.0;
                            $rate = $cb['rate'] > 0 ? $cb['rate'] : (float) ($model->commission_rate ?? 5.0);
                            $comm = $this->weakestLinkService->calculateIndividualCommission($branchVolume, $rate);
                            $branchCommissions[$childId] = $comm;
                        }

                        $minCommission = count($branchCommissions) > 0 ? min($branchCommissions) : 0.00;
                        $weakestChildId = array_search($minCommission, $branchCommissions, true);
                        $firstSale = $sales->first();

                        CommissionLedgerEntry::create([
                            'calculation_run_id' => $run->id,
                            'commission_model_id' => $model->id,
                            'source_sale_id' => $firstSale ? $firstSale->id : null,
                            'seller_member_id' => $weakestChildId ?: $leaderId,
                            'beneficiary_member_id' => $leaderId,
                            'generation' => 1,
                            'sale_amount' => $memberSales[$weakestChildId] ?? 0.00,
                            'rate_applied' => (float) ($model->commission_rate ?? 5.0),
                            'commission_amount' => $minCommission,
                            'is_eligible' => ($minCommission > 0),
                            'payout_status' => ($minCommission > 0) ? 'approved' : 'voided',
                            'ineligibility_reason' => ($minCommission > 0) ? null : 'Weakest branch generated zero commission',
                        ]);

                        $entriesCreated++;
                        if ($minCommission > 0) {
                            $totalCommissionPayable += $minCommission;
                            $beneficiaries[$leaderId] = true;
                        }
                    }
                } else {
                    // Model 2: Level / Generation Override upline traversal
                    foreach ($sales as $sale) {
                        $saleAmount = (float) $sale->sale_amount;
                        $curr = $sale->member_id;
                        $gen = 1;

                        while (isset($parentLookup[$curr])) {
                            $edge = $parentLookup[$curr];
                            $parentId = $edge['parent_id'];
                            $rate = $edge['rate'];

                            $isEligible = ($gen <= $genLimit);
                            $commission = $isEligible
                                ? $this->overrideService->calculateIndividualCommission($saleAmount, $rate)
                                : 0.00;

                            CommissionLedgerEntry::updateOrCreate(
                                [
                                    'calculation_run_id' => $run->id,
                                    'beneficiary_member_id' => $parentId,
                                    'source_sale_id' => $sale->id,
                                ],
                                [
                                    'commission_model_id' => $model->id,
                                    'seller_member_id' => $sale->member_id,
                                    'generation' => $gen,
                                    'sale_amount' => $saleAmount,
                                    'rate_applied' => $rate,
                                    'commission_amount' => $commission,
                                    'is_eligible' => $isEligible,
                                    'payout_status' => $isEligible ? 'approved' : 'voided',
                                    'ineligibility_reason' => $isEligible ? null : "Exceeded maximum generation ceiling ({$genLimit})",
                                ]
                            );

                            $entriesCreated++;

                            if ($isEligible) {
                                $totalCommissionPayable += $commission;
                                $beneficiaries[$parentId] = true;
                            }

                            $curr = $parentId;
                            $gen++;
                        }
                    }
                }

                // 3. Complete calculation run atomically
                $run->update([
                    'status' => 'completed',
                    'total_commission_payable' => round($totalCommissionPayable, 2),
                    'total_beneficiaries_count' => count($beneficiaries),
                    'summary_results' => [
                        'model_type' => $model->model_type,
                        'sales_evaluated_count' => $sales->count(),
                        'total_sales_amount' => $totalSalesAmount,
                        'total_commission_payable' => round($totalCommissionPayable, 2),
                        'total_line_items' => $entriesCreated,
                        'beneficiary_ids' => array_keys($beneficiaries),
                    ],
                    'completed_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Commission calculation execution failed: ' . $e->getMessage(), [
                'model_id' => $model->id,
                'run_number' => $runNumber,
            ]);

            return back()->with('error', 'Calculation run failed to execute safely: ' . $e->getMessage());
        }

        return redirect()->route('admin.history.show', $run->id)
            ->with('success', "Calculation {$runNumber} completed successfully: $" . number_format($totalCommissionPayable, 2) . " payable across " . count($beneficiaries) . " beneficiaries.");
    }
}
