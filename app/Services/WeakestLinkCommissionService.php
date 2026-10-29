<?php

namespace App\Services;

use App\Models\CalculationRun;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionModel;
use App\Models\Member;
use App\Models\SalesTransaction;
use App\Services\Contracts\CommissionCalculatorInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * WeakestLinkCommissionService
 *
 * Implements Model 1: Weakest Link Commission Calculation Engine.
 */
class WeakestLinkCommissionService implements CommissionCalculatorInterface
{
    /**
     * Rounding policy configuration.
     * Financial standard: 2 decimal places, half-up.
     */
    public const DECIMAL_SCALE = 2;
    public const RATE_SCALE = 4;
    public const DEFAULT_GENERATION_LIMIT = 100;

    /**
     * Get model type identifier.
     */
    public function getModelType(): string
    {
        return 'weakest_link';
    }

    /**
     * Execute standard calculation through common interface.
     */
    public function execute(array $hierarchyData, array $salesData = [], array $options = []): array
    {
        $defaultRate = $options['commission_rate'] ?? 5.0;
        $topLeader = $options['top_leader'] ?? 'A';

        return $this->calculate($defaultRate, $hierarchyData, $topLeader, $options);
    }

    /**
     * Calculate individual commission from sales amount and percentage rate.
     *
     * @param  float|int|string  $sales  Sale amount (must be >= 0)
     * @param  float|int|string  $rate   Percentage rate e.g. 5.00 for 5% (0 <= rate <= 100)
     * @return float
     */
    public function calculateIndividualCommission(float|int|string $sales, float|int|string $rate): float
    {
        $sales = (float) $sales;
        $rate = (float) $rate;

        if ($sales < 0) {
            throw new InvalidArgumentException('Sales amount cannot be negative.');
        }

        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException('Commission rate must be between 0.00% and 100.00%.');
        }

        // Decimal-safe financial calculation: (sales * rate) / 100 rounded to 2 decimals
        return round(($sales * $rate) / 100, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP);
    }

    /**
     * Compute Weakest Link commission across arbitrary hierarchy levels.
     *
     * @param  float|int|string  $defaultRate  Default percentage fallback rate (e.g. 5.0 for 5%)
     * @param  array  $levels  Ordered list of level definitions
     * @param  string  $topLeader  Top leader identifier (default 'A')
     * @param  array  $options  Configuration options (max_depth, strict_branching, processed_sale_ids)
     * @return array Comprehensive calculation result with intermediate steps & ledger
     */
    public function calculate(
        float|int|string $defaultRate,
        array $levels,
        string $topLeader = 'A',
        array $options = []
    ): array {
        $globalRate = (float) $defaultRate;
        if ($globalRate < 0 || $globalRate > 100) {
            throw new InvalidArgumentException('Default commission rate must be between 0.00% and 100.00%.');
        }

        if (empty($levels)) {
            throw new InvalidArgumentException('Commission model must contain at least 1 level.');
        }

        $maxDepth = (int) ($options['max_depth'] ?? self::DEFAULT_GENERATION_LIMIT);
        $strictBranching = (bool) ($options['strict_branching'] ?? false);
        $processedSales = $options['processed_sales'] ?? [];

        // 1. Validate and normalize incoming levels
        $normalizedLevels = $this->normalizeLevels($levels, $topLeader, $maxDepth);
        $totalLevels = count($normalizedLevels);

        // 2. Track processed sales transactions to prevent duplicates within this run
        $trackedSaleKeys = [];
        $duplicateSalesFound = [];

        // 3. Register all individual salespeople and their direct personal commissions
        $salespeople = [];
        $totalSales = 0.00;
        $totalPotentialCommission = 0.00;

        for ($i = $totalLevels - 1; $i >= 0; $i--) {
            $lvl = $normalizedLevels[$i];
            $levelRate = $lvl['commission_rate'] ?? $globalRate;
            $sideRate = $lvl['side_rate'] ?? $levelRate;
            $mainRate = $lvl['main_rate'] ?? $levelRate;

            $sidePerson = $lvl['side_person'];
            $sideSales = (float) ($lvl['side_sales'] ?? 0.00);
            $sideSaleKey = $lvl['side_sale_id'] ?? ("level_{$lvl['level']}_side_{$sidePerson}");

            // Duplicate sale check for side salesperson
            if (isset($trackedSaleKeys[$sideSaleKey])) {
                $duplicateSalesFound[] = $sideSaleKey;
            }
            $trackedSaleKeys[$sideSaleKey] = true;

            if (! isset($salespeople[$sidePerson])) {
                $sideCommission = $this->calculateIndividualCommission($sideSales, $sideRate);
                $salespeople[$sidePerson] = [
                    'name' => $sidePerson,
                    'sales' => $sideSales,
                    'rate' => $sideRate,
                    'commission' => $sideCommission,
                    'role' => 'side_salesperson',
                    'level' => $lvl['level'],
                ];
                $totalSales += $sideSales;
                $totalPotentialCommission += $sideCommission;
            }

            // Main salesperson (at bottom level, or intermediate level if personal sales reported)
            $mainPerson = $lvl['main_person'];
            $mainSales = (float) ($lvl['main_sales'] ?? 0.00);
            $mainSaleKey = $lvl['main_sale_id'] ?? ("level_{$lvl['level']}_main_{$mainPerson}");

            if ($i === $totalLevels - 1 || $mainSales > 0) {
                if (isset($trackedSaleKeys[$mainSaleKey])) {
                    $duplicateSalesFound[] = $mainSaleKey;
                }
                $trackedSaleKeys[$mainSaleKey] = true;

                if (! isset($salespeople[$mainPerson])) {
                    $mainCommission = $this->calculateIndividualCommission($mainSales, $mainRate);
                    $salespeople[$mainPerson] = [
                        'name' => $mainPerson,
                        'sales' => $mainSales,
                        'rate' => $mainRate,
                        'commission' => $mainCommission,
                        'role' => ($i === $totalLevels - 1) ? 'leaf_salesperson' : 'intermediate_salesperson',
                        'level' => $lvl['level'],
                    ];
                    $totalSales += $mainSales;
                    $totalPotentialCommission += $mainCommission;
                }
            }
            // Additional custom branches
            if (! empty($lvl['additional_branches']) && is_array($lvl['additional_branches'])) {
                foreach ($lvl['additional_branches'] as $extraBranch) {
                    $bName = $extraBranch['person'] ?? 'Branch_' . uniqid();
                    $bSales = (float) ($extraBranch['sales'] ?? 0.00);
                    $bRate = (float) ($extraBranch['rate'] ?? $levelRate);
                    $bSaleKey = $extraBranch['sale_id'] ?? ("level_{$lvl['level']}_extra_{$bName}");

                    if (isset($trackedSaleKeys[$bSaleKey])) {
                        $duplicateSalesFound[] = $bSaleKey;
                    }
                    $trackedSaleKeys[$bSaleKey] = true;

                    if (! isset($salespeople[$bName])) {
                        $bComm = $this->calculateIndividualCommission($bSales, $bRate);
                        $salespeople[$bName] = [
                            'name' => $bName,
                            'sales' => $bSales,
                            'rate' => $bRate,
                            'commission' => $bComm,
                            'role' => 'additional_branch_salesperson',
                            'level' => $lvl['level'],
                        ];
                        $totalSales += $bSales;
                        $totalPotentialCommission += $bComm;
                    }
                }
            }
        }

        if (! empty($duplicateSalesFound)) {
            throw new InvalidArgumentException(
                'Duplicate sales detected in calculation run: ' . implode(', ', array_unique($duplicateSalesFound))
            );
        }

        // 4. Identify overall Weakest Person among direct salespeople
        $weakestPerson = null;
        $weakestSales = null;
        $weakestCommission = null;

        foreach ($salespeople as $p) {
            if ($weakestCommission === null || $p['commission'] < $weakestCommission) {
                $weakestPerson = $p['name'];
                $weakestSales = $p['sales'];
                $weakestCommission = $p['commission'];
            }
        }

        // 5. Bottom-Up Propagation & Weakest Link Evaluation
        // At every level, evaluate child branches and select the MINIMUM.
        $calculatedLevels = [];
        $ledgerEntries = [];
        $nextMainCommission = null;

        for ($i = $totalLevels - 1; $i >= 0; $i--) {
            $lvl = $normalizedLevels[$i];
            $levelNumber = $lvl['level'];
            $levelRate = $lvl['commission_rate'] ?? $globalRate;
            $sideRate = $lvl['side_rate'] ?? $levelRate;
            $mainRate = $lvl['main_rate'] ?? $levelRate;

            $leader = $lvl['leader'];
            $mainChild = $lvl['main_person'];
            $mainSales = (float) ($lvl['main_sales'] ?? 0.00);
            $sidePerson = $lvl['side_person'];
            $sideSales = (float) ($lvl['side_sales'] ?? 0.00);

            $sideCommission = $sidePerson !== null
                ? $this->calculateIndividualCommission($sideSales, $sideRate)
                : null;

            if ($i === $totalLevels - 1) {
                // Bottom-most level: main branch commission is its direct personal commission
                $mainCommission = $this->calculateIndividualCommission($mainSales, $mainRate);
            } else {
                // Intermediate level: incoming commission from level below
                $childBranchCommission = $nextMainCommission;
                if ($mainSales > 0) {
                    $directPersonalCommission = $this->calculateIndividualCommission($mainSales, $mainRate);
                    $mainCommission = min($childBranchCommission, $directPersonalCommission);
                } else {
                    $mainCommission = $childBranchCommission;
                }
            }

            // Handle multi-branch evaluation (collect all eligible branches)
            $branchValues = [];
            if ($mainCommission !== null) {
                $branchValues[$mainChild] = $mainCommission;
            }
            if ($sidePerson !== null && $sideCommission !== null) {
                $branchValues[$sidePerson] = $sideCommission;
            }

            // Additional custom branches if defined on the level
            if (! empty($lvl['additional_branches']) && is_array($lvl['additional_branches'])) {
                foreach ($lvl['additional_branches'] as $extraBranch) {
                    $bName = $extraBranch['person'] ?? 'Branch_' . uniqid();
                    $bSales = (float) ($extraBranch['sales'] ?? 0.00);
                    $bRate = (float) ($extraBranch['rate'] ?? $levelRate);
                    $bComm = $this->calculateIndividualCommission($bSales, $bRate);
                    $branchValues[$bName] = $bComm;
                }
            }

            // Missing branch handling rule:
            // If strict branching requires at least 2 branches and only 1 exists, evaluate to 0.00.
            if ($strictBranching && count($branchValues) < 2) {
                $selectedMinimum = 0.00;
                $weakestAtLevel = 'missing_branch';
            } elseif (empty($branchValues)) {
                $selectedMinimum = 0.00;
                $weakestAtLevel = 'none';
            } else {
                $selectedMinimum = min($branchValues);
                // Find branch corresponding to minimum
                $weakestAtLevel = array_search($selectedMinimum, $branchValues, true);
            }

            $resultingLeaderCommission = $selectedMinimum;

            // Intermediate calculation record
            $calculatedLevels[$i] = [
                'level' => $levelNumber,
                'leader' => $leader,
                'commission_rate' => $levelRate,
                'side_rate' => $sideRate,
                'main_rate' => $mainRate,
                'main_person' => $mainChild,
                'main_sales' => $mainSales,
                'main_commission' => $mainCommission,
                'side_person' => $sidePerson,
                'side_sales' => $sideSales,
                'side_commission' => $sideCommission,
                'evaluated_branches' => $branchValues,
                'selected_minimum' => $selectedMinimum,
                'selected_commission' => $selectedMinimum,
                'resulting_leader_commission' => $resultingLeaderCommission,
                'leader_commission' => $resultingLeaderCommission,
                'weakest_at_level' => (string) $weakestAtLevel,
            ];

            // Build granular ledger lines for this level
            $ledgerEntries[] = [
                'level' => $levelNumber,
                'beneficiary' => $leader,
                'rule' => 'Weakest Link MIN() Evaluation',
                'evaluated_branches' => $branchValues,
                'bottleneck_branch' => (string) $weakestAtLevel,
                'calculated_commission' => $resultingLeaderCommission,
                'rate_applied' => $levelRate,
            ];

            // Propagate resulting leader commission upward to become main child commission for parent
            $nextMainCommission = $resultingLeaderCommission;
        }

        // Sort levels back to Level 1 (top) -> Level N (bottom)
        ksort($calculatedLevels);
        $levelByLevel = array_values($calculatedLevels);

        // Final commission for top leader A is Level 1 resulting leader commission
        $finalCommission = $levelByLevel[0]['resulting_leader_commission'] ?? 0.00;

        return [
            'commission_rate' => $globalRate,
            'number_of_levels' => $totalLevels,
            'top_leader' => $topLeader,
            'total_sales' => round($totalSales, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP),
            'total_potential_commission' => round($totalPotentialCommission, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP),
            'weakest_person' => $weakestPerson,
            'weakest_sales' => round((float) $weakestSales, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP),
            'weakest_commission' => round((float) $weakestCommission, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP),
            'final_commission' => round($finalCommission, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP),
            'levels' => $levelByLevel,
            'level_by_level_calculations' => $levelByLevel,
            'each_salesperson_commission' => $salespeople,
            'ledger_entries' => $ledgerEntries,
            'selected_minimums' => array_column($levelByLevel, 'selected_minimum'),
        ];
    }

    /**
     * Calculate and persist execution atomically into CommissionModel,
     * CalculationRun, and CommissionLedgerEntry records.
     */
    public function calculateAndPersistRun(
        CommissionModel $model,
        array $levelsData,
        string $topLeader = 'A',
        array $options = []
    ): CalculationRun {
        return DB::transaction(function () use ($model, $levelsData, $topLeader, $options) {
            // Execute calculation
            $results = $this->calculate(
                $model->commission_rate ?? 5.0,
                $levelsData,
                $topLeader,
                $options
            );

            // Update model summary metrics
            $model->update([
                'number_of_levels' => $results['number_of_levels'],
                'total_sales' => $results['total_sales'],
                'total_potential_commission' => $results['total_potential_commission'],
                'final_commission' => $results['final_commission'],
                'weakest_person' => $results['weakest_person'],
                'weakest_sales' => $results['weakest_sales'],
                'weakest_commission' => $results['weakest_commission'],
                'calculation_results' => $results,
            ]);

            // Create historical calculation run header
            $run = CalculationRun::create([
                'run_uuid' => (string) Str::uuid(),
                'run_number' => 'RUN-M1-' . date('Ymd-His'),
                'commission_model_id' => $model->id,
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'status' => 'completed',
                'generation_limit' => $results['number_of_levels'],
                'total_sales_amount' => $results['total_sales'],
                'total_commission_payable' => $results['final_commission'],
                'total_beneficiaries_count' => count($results['each_salesperson_commission']),
                'settings_snapshot' => array_merge($model->settings ?? [], [
                    'rate' => $results['commission_rate'],
                    'rounding_policy' => 'PHP_ROUND_HALF_UP',
                    'scale' => self::DECIMAL_SCALE,
                ]),
                'summary_results' => [
                    'weakest_person' => $results['weakest_person'],
                    'weakest_commission' => $results['weakest_commission'],
                    'final_leader_commission' => $results['final_commission'],
                ],
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            return $run;
        });
    }

    /**
     * Helper to build a chain of arbitrary depth (e.g. 1..100 levels).
     */
    public function buildChain(
        int $numberOfLevels,
        float|array $sideSales,
        float $bottomMainSales,
        string $topLeader = 'A'
    ): array {
        if ($numberOfLevels < 1) {
            throw new InvalidArgumentException('Number of levels must be at least 1.');
        }

        $levels = [];
        $letters = range('A', 'Z');

        for ($i = 1; $i <= $numberOfLevels; $i++) {
            $leaderIndex = $i - 1;
            $mainIndex = $i;

            $leaderName = $leaderIndex < count($letters) ? $letters[$leaderIndex] : "Leader_{$i}";
            $mainName = $mainIndex < count($letters) ? $letters[$mainIndex] : "Main_{$i}";
            $sideName = "S{$i}";

            $currentSideSales = is_array($sideSales)
                ? ($sideSales[$i - 1] ?? ($sideSales[$sideName] ?? 0.0))
                : (float) $sideSales;

            $levels[] = [
                'level' => $i,
                'leader' => $leaderName,
                'main_person' => $mainName,
                'main_sales' => ($i === $numberOfLevels) ? $bottomMainSales : 0.0,
                'side_person' => $sideName,
                'side_sales' => (float) $currentSideSales,
            ];
        }

        return $levels;
    }

    /**
     * Normalize incoming levels and prune to maxDepth if specified.
     */
    protected function normalizeLevels(array $levels, string $topLeader, int $maxDepth): array
    {
        // Sort levels by level index ascending if level key is present
        $isAssociative = isset(reset($levels)['level']);
        if ($isAssociative) {
            usort($levels, fn ($a, $b) => ($a['level'] ?? 0) <=> ($b['level'] ?? 0));
        }

        // Enforce max depth constraint
        if (count($levels) > $maxDepth) {
            $levels = array_slice($levels, 0, $maxDepth);
        }

        $letters = range('A', 'Z');
        $normalized = [];

        foreach (array_values($levels) as $idx => $lvl) {
            $levelNumber = (int) ($lvl['level'] ?? ($idx + 1));
            $defaultLeader = $idx === 0 ? $topLeader : ($idx < count($letters) ? $letters[$idx] : "Leader_{$levelNumber}");
            $defaultMain = ($idx + 1 < count($letters)) ? $letters[$idx + 1] : "Main_{$levelNumber}";
            $defaultSide = "S{$levelNumber}";

            $sidePersonVal = array_key_exists('side_person', $lvl) ? $lvl['side_person'] : $defaultSide;

            $normalized[] = [
                'level' => $levelNumber,
                'commission_rate' => isset($lvl['commission_rate']) && $lvl['commission_rate'] !== null && $lvl['commission_rate'] !== '' ? (float) $lvl['commission_rate'] : null,
                'side_rate' => isset($lvl['side_rate']) && $lvl['side_rate'] !== null && $lvl['side_rate'] !== '' ? (float) $lvl['side_rate'] : null,
                'main_rate' => isset($lvl['main_rate']) && $lvl['main_rate'] !== null && $lvl['main_rate'] !== '' ? (float) $lvl['main_rate'] : null,
                'leader' => $lvl['leader'] ?? $defaultLeader,
                'main_person' => $lvl['main_person'] ?? ($lvl['main_child'] ?? $defaultMain),
                'main_sales' => isset($lvl['main_sales']) ? (float) $lvl['main_sales'] : 0.0,
                'main_sale_id' => $lvl['main_sale_id'] ?? null,
                'side_person' => $sidePersonVal,
                'side_sales' => isset($lvl['side_sales']) ? (float) $lvl['side_sales'] : 0.0,
                'side_sale_id' => $lvl['side_sale_id'] ?? null,
                'additional_branches' => $lvl['additional_branches'] ?? [],
            ];
        }

        return $normalized;
    }
}
