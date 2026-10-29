<?php

namespace Tests\Unit;

use App\Services\CommissionCalculationManager;
use App\Services\LevelOverrideCommissionService;
use App\Services\WeakestLinkCommissionService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LevelOverrideCommissionServiceTest extends TestCase
{
    private LevelOverrideCommissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LevelOverrideCommissionService();
    }

    /**
     * Test 1: Single sale with one upline.
     * Hierarchy: A -> B (rate = 5.00%)
     * Sale: B sells $1,000.00
     * Expected:
     * - Gen 1 (A): $1,000.00 * 5% = $50.00
     * - Total Commission: $50.00
     */
    public function test_single_sale_with_one_upline(): void
    {
        $edges = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 5.00],
        ];
        $sales = [
            ['salesperson' => 'B', 'amount' => 1000.00, 'sale_id' => 'TXN-1'],
        ];

        $result = $this->service->calculate($edges, $sales);

        $this->assertEquals(1000.00, $result['total_sales']);
        $this->assertEquals(50.00, $result['total_commission']);
        $this->assertEquals(50.00, $result['earnings_by_person']['A']['override_commission']);
        $this->assertEquals(0.00, $result['earnings_by_person']['B']['override_commission']);
        $this->assertEquals(1000.00, $result['earnings_by_person']['B']['personal_sales']);
    }

    /**
     * Test 2: Sale with several uplines using different rates.
     * Hierarchy: A -> B (8%), B -> C (6%), C -> D (4%), D -> E (2%)
     * Sale: E sells $2,000.00
     * Expected:
     * - Gen 1 (D): $2,000.00 * 2% = $40.00
     * - Gen 2 (C): $2,000.00 * 4% = $80.00
     * - Gen 3 (B): $2,000.00 * 6% = $120.00
     * - Gen 4 (A): $2,000.00 * 8% = $160.00
     * - Total: $40 + $80 + $120 + $160 = $400.00
     */
    public function test_sale_with_several_uplines_using_different_rates(): void
    {
        $edges = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 8.00],
            ['parent' => 'B', 'child' => 'C', 'rate' => 6.00],
            ['parent' => 'C', 'child' => 'D', 'rate' => 4.00],
            ['parent' => 'D', 'child' => 'E', 'rate' => 2.00],
        ];
        $sales = [
            ['salesperson' => 'E', 'amount' => 2000.00, 'sale_id' => 'TXN-E'],
        ];

        $result = $this->service->calculate($edges, $sales);

        $this->assertEquals(2000.00, $result['total_sales']);
        $this->assertEquals(400.00, $result['total_commission']);
        $this->assertEquals(40.00, $result['earnings_by_person']['D']['override_commission']);
        $this->assertEquals(80.00, $result['earnings_by_person']['C']['override_commission']);
        $this->assertEquals(120.00, $result['earnings_by_person']['B']['override_commission']);
        $this->assertEquals(160.00, $result['earnings_by_person']['A']['override_commission']);

        // Verify ledger entries count
        $this->assertCount(4, $result['ledger_entries']);
    }

    /**
     * Test 3: Multiple independent branches under root leader A.
     * Hierarchy:
     *   Branch 1: A -> B (5%) -> C (3%)
     *   Branch 2: A -> D (7%) -> E (4%)
     * Sales:
     *   C sells $1,000.00 (TXN-C)
     *   E sells $500.00 (TXN-E)
     * Expected:
     *   From C ($1000): B earns $30.00 (Gen 1), A earns $50.00 (Gen 2). D and E earn $0.
     *   From E ($500):  D earns $20.00 (Gen 1), A earns $35.00 (Gen 2). B and C earn $0.
     *   A total: $50 + $35 = $85.00.
     *   Total Commission: $30 + $50 + $20 + $35 = $135.00.
     */
    public function test_multiple_independent_branches(): void
    {
        $edges = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 5.00],
            ['parent' => 'B', 'child' => 'C', 'rate' => 3.00],
            ['parent' => 'A', 'child' => 'D', 'rate' => 7.00],
            ['parent' => 'D', 'child' => 'E', 'rate' => 4.00],
        ];
        $sales = [
            ['salesperson' => 'C', 'amount' => 1000.00, 'sale_id' => 'TXN-C'],
            ['salesperson' => 'E', 'amount' => 500.00, 'sale_id' => 'TXN-E'],
        ];

        $result = $this->service->calculate($edges, $sales);

        $this->assertEquals(1500.00, $result['total_sales']);
        $this->assertEquals(135.00, $result['total_commission']);
        $this->assertEquals(85.00, $result['earnings_by_person']['A']['override_commission']);
        $this->assertEquals(30.00, $result['earnings_by_person']['B']['override_commission']);
        $this->assertEquals(20.00, $result['earnings_by_person']['D']['override_commission']);
        $this->assertEquals(0.00, $result['earnings_by_person']['C']['override_commission']);
        $this->assertEquals(0.00, $result['earnings_by_person']['E']['override_commission']);
    }

    /**
     * Test 4: Zero-value sales.
     * Hierarchy: A -> B (10%)
     * Sale: B sells $0.00
     * Expected:
     * - Gen 1 (A): $0.00 * 10% = $0.00
     * - Total Commission: $0.00
     */
    public function test_zero_value_sales(): void
    {
        $edges = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 10.00],
        ];
        $sales = [
            ['salesperson' => 'B', 'amount' => 0.00, 'sale_id' => 'TXN-ZERO'],
        ];

        $result = $this->service->calculate($edges, $sales);

        $this->assertEquals(0.00, $result['total_sales']);
        $this->assertEquals(0.00, $result['total_commission']);
        $this->assertEquals(0.00, $result['earnings_by_person']['A']['override_commission']);
    }

    /**
     * Test 5: Member without an upline (root / orphan).
     * Hierarchy: A -> B (5%)
     * Sale: A (the root leader) sells $1,500.00
     * Expected:
     * - A has no uplines.
     * - Zero override commissions generated.
     */
    public function test_member_without_an_upline(): void
    {
        $edges = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 5.00],
        ];
        $sales = [
            ['salesperson' => 'A', 'amount' => 1500.00, 'sale_id' => 'TXN-A'],
        ];

        $result = $this->service->calculate($edges, $sales);

        $this->assertEquals(1500.00, $result['total_sales']);
        $this->assertEquals(0.00, $result['total_commission']);
        $this->assertEquals(1500.00, $result['earnings_by_person']['A']['personal_sales']);
        $this->assertEquals(0.00, $result['earnings_by_person']['A']['override_commission']);
        $this->assertEmpty($result['ledger_entries']);
    }

    /**
     * Test 6: Maximum-generation boundary.
     * Chain of 7 generations:
     * A -> B (1%) -> C (1%) -> D (1%) -> E (1%) -> F (1%) -> G (1%)
     * Sale: G sells $10,000.00
     * Max generations configured = 5.
     * Expected:
     * - Gen 1 (F): $100.00 (Eligible)
     * - Gen 2 (E): $100.00 (Eligible)
     * - Gen 3 (D): $100.00 (Eligible)
     * - Gen 4 (C): $100.00 (Eligible)
     * - Gen 5 (B): $100.00 (Eligible)
     * - Gen 6 (A): Exceeds ceiling (Ineligible, not paid)
     * Total Commission = $500.00
     */
    public function test_maximum_generation_boundary(): void
    {
        $edges = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 1.00],
            ['parent' => 'B', 'child' => 'C', 'rate' => 1.00],
            ['parent' => 'C', 'child' => 'D', 'rate' => 1.00],
            ['parent' => 'D', 'child' => 'E', 'rate' => 1.00],
            ['parent' => 'E', 'child' => 'F', 'rate' => 1.00],
            ['parent' => 'F', 'child' => 'G', 'rate' => 1.00],
        ];
        $sales = [
            ['salesperson' => 'G', 'amount' => 10000.00, 'sale_id' => 'TXN-G'],
        ];

        $result = $this->service->calculate($edges, $sales, maxGenerations: 5);

        $this->assertEquals(500.00, $result['total_commission']);
        $this->assertEquals(100.00, $result['earnings_by_person']['F']['override_commission']);
        $this->assertEquals(100.00, $result['earnings_by_person']['E']['override_commission']);
        $this->assertEquals(100.00, $result['earnings_by_person']['D']['override_commission']);
        $this->assertEquals(100.00, $result['earnings_by_person']['C']['override_commission']);
        $this->assertEquals(100.00, $result['earnings_by_person']['B']['override_commission']);
        $this->assertEquals(0.00, $result['earnings_by_person']['A']['override_commission']);
    }

    /**
     * Test 7: Invalid rates and cyclic relationships.
     */
    public function test_invalid_rates_and_cyclic_relationships(): void
    {
        // 7a: Negative rate
        try {
            $edges = [['parent' => 'A', 'child' => 'B', 'rate' => -5.00]];
            $this->service->calculate($edges, [['salesperson' => 'B', 'amount' => 100.00]]);
            $this->fail('Expected exception for negative rate');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('between 0.00% and 100.00%', $e->getMessage());
        }

        // 7b: Rate > 100%
        try {
            $edges = [['parent' => 'A', 'child' => 'B', 'rate' => 120.00]];
            $this->service->calculate($edges, [['salesperson' => 'B', 'amount' => 100.00]]);
            $this->fail('Expected exception for rate > 100%');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('between 0.00% and 100.00%', $e->getMessage());
        }

        // 7c: Circular loop (A -> B -> C -> A)
        try {
            $cyclicEdges = [
                ['parent' => 'A', 'child' => 'B', 'rate' => 5.00],
                ['parent' => 'B', 'child' => 'C', 'rate' => 5.00],
                ['parent' => 'C', 'child' => 'A', 'rate' => 5.00],
            ];
            $this->service->calculate($cyclicEdges, [['salesperson' => 'C', 'amount' => 100.00]]);
            $this->fail('Expected exception for cycle');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Cycle detected', $e->getMessage());
        }
    }

    /**
     * Test 8: Duplicate calculation attempts (duplicate sale ID in same run).
     */
    public function test_duplicate_calculation_attempts(): void
    {
        $edges = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 5.00],
        ];
        $sales = [
            ['salesperson' => 'B', 'amount' => 100.00, 'sale_id' => 'TXN-SAME'],
            ['salesperson' => 'B', 'amount' => 200.00, 'sale_id' => 'TXN-SAME'], // duplicate sale id!
        ];

        try {
            $this->service->calculate($edges, $sales);
            $this->fail('Expected exception for duplicate sale transaction');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Duplicate sale transaction detected', $e->getMessage());
        }
    }

    /**
     * Test 9: Recalculation after changing a rate.
     * Shows that changing rates updates calculation outputs while historical runs remain isolated.
     */
    public function test_recalculation_after_changing_rate(): void
    {
        // Initial setup: Rate = 5%
        $edgesV1 = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 5.00],
        ];
        $sales = [
            ['salesperson' => 'B', 'amount' => 1000.00, 'sale_id' => 'TXN-001'],
        ];

        $res1 = $this->service->calculate($edgesV1, $sales);
        $this->assertEquals(50.00, $res1['total_commission']);

        // Updated setup: Rate changed to 12%
        $edgesV2 = [
            ['parent' => 'A', 'child' => 'B', 'rate' => 12.00],
        ];

        $res2 = $this->service->calculate($edgesV2, $sales);
        $this->assertEquals(120.00, $res2['total_commission']);

        // Assert previous calculation result object is immutable
        $this->assertEquals(50.00, $res1['total_commission']);
    }

    /**
     * Test 10: Common calculation manager interface resolution.
     */
    public function test_common_calculation_manager_resolution(): void
    {
        $weakest = new WeakestLinkCommissionService();
        $manager = new CommissionCalculationManager($weakest, $this->service);

        // Resolve Model 1
        $calc1 = $manager->getCalculator('weakest_link');
        $this->assertEquals('weakest_link', $calc1->getModelType());

        // Resolve Model 2
        $calc2 = $manager->getCalculator('generation_override');
        $this->assertEquals('generation_override', $calc2->getModelType());
    }
}
