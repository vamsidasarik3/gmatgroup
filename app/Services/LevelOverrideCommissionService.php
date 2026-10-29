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
 * LevelOverrideCommissionService
 *
 * Implements Model 2: Level / Generation Override Commission Calculation Engine.
 *
 * Core Business Rules:
 * 1. General Directed Hierarchy:
 *    Supports arbitrary network topologies with multiple branches and levels.
 * 2. Unaltered Sale Base:
 *    Every upline's override commission is calculated strictly against the ORIGINAL SALE AMOUNT.
 *    Formula: commission = round((sale_amount * override_rate) / 100, 2, PHP_ROUND_HALF_UP)
 *    NO compounding, NO weakest-link comparison, NO reduction based on junior commissions.
 * 3. Generation Traversal & Counting:
 *    - Direct parent of the selling member is Generation 1.
 *    - Grandparent is Generation 2, and so forth.
 *    - Traversal terminates when the configured max_generations (default: 5) ceiling is reached,
 *      or when a root leader (node without a parent) is reached.
 * 4. Isolation & Branch Scoping:
 *    Only direct ancestral uplines of the selling member receive override commissions.
 *    Members in sibling, parallel, or unconnected branches receive $0.00.
 * 5. Cycle Detection:
 *    Graph traversal validates directed acyclic structure. Circular loops trigger InvalidArgumentException.
 * 6. Audit & Immutable Ledger:
 *    Every calculation produces structured ledger records tracking source sale, earner, generation,
 *    rate applied, and final dollar amount.
 */
class LevelOverrideCommissionService implements CommissionCalculatorInterface
{
    public const DEFAULT_MAX_GENERATIONS = 5;
    public const DECIMAL_SCALE = 2;
    public const RATE_SCALE = 4;

    /**
     * Get model type identifier.
     */
    public function getModelType(): string
    {
        return 'generation_override';
    }

    /**
     * Calculate individual commission from sales amount and percentage rate.
     */
    public function calculateIndividualCommission(float|int|string $sales, float|int|string $rate): float
    {
        $sales = (float) $sales;
        $rate = (float) $rate;

        if ($sales < 0) {
            throw new InvalidArgumentException('Sales amount cannot be negative.');
        }

        if ($rate < 0 || $rate > 100) {
            throw new InvalidArgumentException('Override rate must be between 0.00% and 100.00%.');
        }

        return round(($sales * $rate) / 100, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP);
    }

    /**
     * Common interface execution adapter.
     */
    public function execute(array $hierarchyData, array $salesData = [], array $options = []): array
    {
        $maxGenerations = (int) ($options['max_generations'] ?? self::DEFAULT_MAX_GENERATIONS);
        $edges = $hierarchyData['edges'] ?? $hierarchyData;
        $sales = $salesData['sales'] ?? $salesData;

        return $this->calculate($edges, $sales, $maxGenerations, $options);
    }

    /**
     * Calculate Level / Generation Override commissions across hierarchical network.
     *
     * @param  array  $edges  Parent-child edges [['parent' => 'A', 'child' => 'B', 'rate' => 5.0], ...]
     * @param  array  $sales  Sales transactions [['salesperson' => 'B', 'amount' => 1000.0, 'sale_id' => 'TXN-1'], ...]
     * @param  int    $maxGenerations  Generation limit ceiling (default 5)
     * @param  array  $options  Additional options (e.g. run_id, model_id)
     * @return array Structured calculation results including earnings breakdown and ledger lines
     */
    public function calculate(
        array $edges,
        array $sales,
        int $maxGenerations = self::DEFAULT_MAX_GENERATIONS,
        array $options = []
    ): array {
        if ($maxGenerations < 1) {
            throw new InvalidArgumentException('Maximum generations must be at least 1.');
        }

        // 1. Normalize and validate edges
        $normalizedEdges = $this->normalizeEdges($edges);

        // 2. Validate tree structure and detect cycles
        $this->validateAcyclicStructure($normalizedEdges);

        // 3. Normalize and validate sales transactions
        $normalizedSales = $this->normalizeSales($sales);

        // 4. Build upline lookup map: child -> ['parent' => parent_name, 'rate' => override_rate]
        $parentOf = [];
        $allPeople = [];

        foreach ($normalizedEdges as $edge) {
            $p = $edge['parent'];
            $c = $edge['child'];
            $r = (float) $edge['rate'];

            $parentOf[$c] = [
                'parent' => $p,
                'rate' => $r,
            ];

            $allPeople[$p] = true;
            $allPeople[$c] = true;
        }

        foreach ($normalizedSales as $sale) {
            $allPeople[$sale['salesperson']] = true;
        }

        // 5. Initialize earnings and performance tracker per member
        $earnings = [];
        foreach (array_keys($allPeople) as $person) {
            $earnings[$person] = [
                'name' => $person,
                'personal_sales' => 0.00,
                'override_commission' => 0.00,
                'total_earnings' => 0.00,
                'sales_count' => 0,
                'commissions_received_count' => 0,
                'commissions_received' => [],
            ];
        }

        // 6. Record personal sales
        $totalSalesVolume = 0.00;
        foreach ($normalizedSales as $sale) {
            $seller = $sale['salesperson'];
            $amount = (float) $sale['amount'];
            $totalSalesVolume += $amount;

            $earnings[$seller]['personal_sales'] += $amount;
            $earnings[$seller]['sales_count']++;
        }

        // 7. Traverse uplines and compute override commissions for each sale
        $ledgerEntries = [];
        $totalCommissionPaid = 0.00;
        $totalCommissionsGeneratedCount = 0;

        foreach ($normalizedSales as $sale) {
            $seller = $sale['salesperson'];
            $saleAmount = (float) $sale['amount'];
            $saleId = $sale['sale_id'];

            $currentChild = $seller;
            $currentGeneration = 1;

            // Walk upwards along ancestral edges
            while (isset($parentOf[$currentChild])) {
                $uplineInfo = $parentOf[$currentChild];
                $parentName = $uplineInfo['parent'];
                $overrideRate = (float) $uplineInfo['rate'];

                // Generation boundary check
                $isEligible = ($currentGeneration <= $maxGenerations);

                if ($isEligible) {
                    $commissionAmount = $this->calculateIndividualCommission($saleAmount, $overrideRate);

                    // Credit beneficiary
                    $earnings[$parentName]['override_commission'] += $commissionAmount;
                    $earnings[$parentName]['commissions_received_count']++;
                    $earnings[$parentName]['commissions_received'][] = [
                        'sale_id' => $saleId,
                        'source_seller' => $seller,
                        'generation' => $currentGeneration,
                        'sale_amount' => $saleAmount,
                        'rate' => $overrideRate,
                        'commission' => $commissionAmount,
                    ];

                    $totalCommissionPaid += $commissionAmount;
                    $totalCommissionsGeneratedCount++;

                    $ledgerEntries[] = [
                        'sale_id' => $saleId,
                        'seller' => $seller,
                        'beneficiary' => $parentName,
                        'generation' => $currentGeneration,
                        'sale_amount' => $saleAmount,
                        'rate_applied' => $overrideRate,
                        'commission_amount' => $commissionAmount,
                        'is_eligible' => true,
                        'status' => 'approved',
                    ];
                } else {
                    // Log audit entry for boundary cutoff
                    $ledgerEntries[] = [
                        'sale_id' => $saleId,
                        'seller' => $seller,
                        'beneficiary' => $parentName,
                        'generation' => $currentGeneration,
                        'sale_amount' => $saleAmount,
                        'rate_applied' => $overrideRate,
                        'commission_amount' => 0.00,
                        'is_eligible' => false,
                        'status' => 'ineligible',
                        'reason' => "Exceeded maximum generation limit of {$maxGenerations}",
                    ];
                    // Once generation limit exceeded, ancestor chain is outside boundary
                    break;
                }

                // Advance to next upline level
                $currentChild = $parentName;
                $currentGeneration++;
            }
        }

        // Finalize total earnings per member
        foreach ($earnings as $person => &$data) {
            $data['total_earnings'] = round($data['override_commission'], self::DECIMAL_SCALE, PHP_ROUND_HALF_UP);
            $data['override_commission'] = round($data['override_commission'], self::DECIMAL_SCALE, PHP_ROUND_HALF_UP);
            $data['personal_sales'] = round($data['personal_sales'], self::DECIMAL_SCALE, PHP_ROUND_HALF_UP);
        }
        unset($data);

        return [
            'model_type' => 'generation_override',
            'max_generations' => $maxGenerations,
            'total_sales' => round($totalSalesVolume, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP),
            'total_commission' => round($totalCommissionPaid, self::DECIMAL_SCALE, PHP_ROUND_HALF_UP),
            'total_commission_count' => $totalCommissionsGeneratedCount,
            'earnings_by_person' => $earnings,
            'ledger_entries' => $ledgerEntries,
            'network_summary' => [
                'total_members' => count($allPeople),
                'total_relationships' => count($normalizedEdges),
                'total_sales_count' => count($normalizedSales),
            ],
        ];
    }

    /**
     * Persist calculation run atomically into database tables.
     */
    public function calculateAndPersistRun(
        CommissionModel $model,
        array $edges,
        array $sales,
        int $maxGenerations = self::DEFAULT_MAX_GENERATIONS,
        array $options = []
    ): CalculationRun {
        return DB::transaction(function () use ($model, $edges, $sales, $maxGenerations, $options) {
            $results = $this->calculate($edges, $sales, $maxGenerations, $options);

            // Update model metrics
            $model->update([
                'max_generations' => $maxGenerations,
                'total_sales' => $results['total_sales'],
                'final_commission' => $results['total_commission'],
                'calculation_results' => $results,
            ]);

            // Create historical calculation run
            $run = CalculationRun::create([
                'run_uuid' => (string) Str::uuid(),
                'run_number' => 'RUN-M2-' . date('Ymd-His'),
                'commission_model_id' => $model->id,
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'status' => 'completed',
                'generation_limit' => $maxGenerations,
                'total_sales_amount' => $results['total_sales'],
                'total_commission_payable' => $results['total_commission'],
                'total_beneficiaries_count' => count($results['earnings_by_person']),
                'settings_snapshot' => array_merge($model->settings ?? [], [
                    'max_generations' => $maxGenerations,
                    'model_type' => 'generation_override',
                    'rounding_policy' => 'PHP_ROUND_HALF_UP',
                ]),
                'summary_results' => [
                    'total_sales' => $results['total_sales'],
                    'total_commission' => $results['total_commission'],
                    'total_commission_count' => $results['total_commission_count'],
                ],
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            return $run;
        });
    }

    /**
     * Normalize edge input array.
     */
    protected function normalizeEdges(array $edges): array
    {
        $normalized = [];
        foreach ($edges as $index => $edge) {
            $parent = trim((string) ($edge['parent'] ?? ''));
            $child = trim((string) ($edge['child'] ?? ''));
            $rate = $edge['rate'] ?? null;

            if ($parent === '' || $child === '') {
                throw new InvalidArgumentException("Edge at index {$index} must specify both 'parent' and 'child'.");
            }

            if ($parent === $child) {
                throw new InvalidArgumentException("Self-referential relationship detected for member '{$parent}'.");
            }

            if ($rate === null || ! is_numeric($rate)) {
                throw new InvalidArgumentException("Invalid commission rate for relationship {$parent} -> {$child}.");
            }

            $rateFloat = (float) $rate;
            if ($rateFloat < 0 || $rateFloat > 100) {
                throw new InvalidArgumentException("Override rate for {$parent} -> {$child} must be between 0.00% and 100.00%.");
            }

            $normalized[] = [
                'parent' => $parent,
                'child' => $child,
                'rate' => $rateFloat,
            ];
        }

        return $normalized;
    }

    /**
     * Normalize and validate sales transactions, preventing duplicate transaction IDs.
     */
    protected function normalizeSales(array $sales): array
    {
        $normalized = [];
        $seenSaleIds = [];

        foreach ($sales as $index => $sale) {
            $person = trim((string) ($sale['salesperson'] ?? ($sale['member'] ?? '')));
            $amount = $sale['amount'] ?? null;
            $saleId = trim((string) ($sale['sale_id'] ?? ($sale['transaction_code'] ?? "SALE-{$index}")));

            if ($person === '') {
                throw new InvalidArgumentException("Sale at index {$index} must specify a salesperson.");
            }

            if ($amount === null || ! is_numeric($amount)) {
                throw new InvalidArgumentException("Invalid sale amount for {$person} at index {$index}.");
            }

            $amountFloat = (float) $amount;
            if ($amountFloat < 0) {
                throw new InvalidArgumentException("Sale amount for {$person} cannot be negative.");
            }

            if (isset($seenSaleIds[$saleId])) {
                throw new InvalidArgumentException("Duplicate sale transaction detected: '{$saleId}'.");
            }
            $seenSaleIds[$saleId] = true;

            $normalized[] = [
                'salesperson' => $person,
                'amount' => $amountFloat,
                'sale_id' => $saleId,
            ];
        }

        return $normalized;
    }

    /**
     * Verify directed tree structure: single parent per child and cycle-free.
     */
    protected function validateAcyclicStructure(array $edges): void
    {
        $parentMap = [];
        foreach ($edges as $edge) {
            $child = $edge['child'];
            $parent = $edge['parent'];

            if (isset($parentMap[$child]) && $parentMap[$child] !== $parent) {
                throw new InvalidArgumentException(
                    "Hierarchy conflict: Child '{$child}' has multiple parents ('{$parentMap[$child]}' and '{$parent}'). Each member must have at most one direct upline."
                );
            }
            $parentMap[$child] = $parent;
        }

        // Cycle detection: from each node, follow parent pointers
        foreach (array_keys($parentMap) as $startNode) {
            $visited = [];
            $curr = $startNode;

            while (isset($parentMap[$curr])) {
                if (isset($visited[$curr])) {
                    throw new InvalidArgumentException("Cycle detected in hierarchy involving member '{$curr}'.");
                }
                $visited[$curr] = true;
                $curr = $parentMap[$curr];
            }
        }
    }
}
