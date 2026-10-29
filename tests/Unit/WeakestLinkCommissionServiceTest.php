<?php

namespace Tests\Unit;

use App\Services\WeakestLinkCommissionService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WeakestLinkCommissionServiceTest extends TestCase
{
    private WeakestLinkCommissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WeakestLinkCommissionService();
    }

    /**
     * Test 1: Leaf nodes direct calculation.
     * Hierarchy:
     * Leader A (Level 1)
     *   ├── Side S1: $200.00 @ 5% => $10.00
     *   └── Leaf B:  $100.00 @ 5% => $5.00
     * Expected:
     *   min($5.00, $10.00) = $5.00
     *   Weakest person: B ($5.00)
     */
    public function test_leaf_node_direct_calculation(): void
    {
        $levels = [
            [
                'level' => 1,
                'leader' => 'A',
                'main_person' => 'B',
                'main_sales' => 100.00,
                'side_person' => 'S1',
                'side_sales' => 200.00,
            ],
        ];

        $result = $this->service->calculate(5.00, $levels, 'A');

        $this->assertEquals(1, $result['number_of_levels']);
        $this->assertEquals(300.00, $result['total_sales']);
        $this->assertEquals(15.00, $result['total_potential_commission']);
        $this->assertEquals(5.00, $result['final_commission']);
        $this->assertEquals('B', $result['weakest_person']);
        $this->assertEquals(100.00, $result['weakest_sales']);
        $this->assertEquals(5.00, $result['weakest_commission']);
    }

    /**
     * Test 2: Multiple child branches under a single leader.
     * Leader A has 3 branches:
     *   Branch 1 (Main B):  $500.00 @ 10% = $50.00
     *   Branch 2 (Side S1): $300.00 @ 10% = $30.00
     *   Branch 3 (Side S2): $200.00 @ 10% = $20.00
     * Expected:
     *   Leader A commission = min($50.00, $30.00, $20.00) = $20.00
     *   Weakest branch: S2 ($20.00)
     */
    public function test_multiple_child_branches(): void
    {
        $levels = [
            [
                'level' => 1,
                'leader' => 'A',
                'main_person' => 'B',
                'main_sales' => 500.00,
                'side_person' => 'S1',
                'side_sales' => 300.00,
                'additional_branches' => [
                    ['person' => 'S2', 'sales' => 200.00, 'rate' => 10.00],
                ],
            ],
        ];

        $result = $this->service->calculate(10.00, $levels, 'A');

        $this->assertEquals(20.00, $result['final_commission']);
        $this->assertEquals('S2', $result['weakest_person']);
        $this->assertEquals(20.00, $result['weakest_commission']);
        $this->assertEquals(1000.00, $result['total_sales']);
        $this->assertCount(3, $result['levels'][0]['evaluated_branches']);
    }

    /**
     * Test 3: Unequal sales across multi-level chain.
     * Level 1: Leader A -> Main B, Side S1 ($1000 @ 5% = $50.00)
     * Level 2: Leader B -> Main C ($600 @ 5% = $30.00), Side S2 ($800 @ 5% = $40.00)
     * Level 2 min = min($30.00, $40.00) = $30.00 => Leader B incoming = $30.00
     * Level 1 min = min(B: $30.00, S1: $50.00) = $30.00 => Leader A final = $30.00
     */
    public function test_unequal_sales_across_levels(): void
    {
        $levels = [
            [
                'level' => 1,
                'leader' => 'A',
                'main_person' => 'B',
                'side_person' => 'S1',
                'side_sales' => 1000.00,
            ],
            [
                'level' => 2,
                'leader' => 'B',
                'main_person' => 'C',
                'main_sales' => 600.00,
                'side_person' => 'S2',
                'side_sales' => 800.00,
            ],
        ];

        $result = $this->service->calculate(5.00, $levels, 'A');

        $this->assertEquals(30.00, $result['final_commission']);
        $this->assertEquals('C', $result['weakest_person']);
        $this->assertEquals(600.00, $result['weakest_sales']);
        $this->assertEquals(30.00, $result['weakest_commission']);
        $this->assertEquals(2400.00, $result['total_sales']);
    }

    /**
     * Test 4: Zero sales scenario.
     * Leaf C has $0.00 sales => $0.00 commission.
     * MIN() evaluation at Level 2 yields min($0.00, $50.00) = $0.00.
     * Upward propagation causes Leader A to receive $0.00.
     */
    public function test_zero_sales_scenario(): void
    {
        $levels = [
            [
                'level' => 1,
                'leader' => 'A',
                'main_person' => 'B',
                'side_person' => 'S1',
                'side_sales' => 500.00,
            ],
            [
                'level' => 2,
                'leader' => 'B',
                'main_person' => 'C',
                'main_sales' => 0.00, // zero sales
                'side_person' => 'S2',
                'side_sales' => 500.00,
            ],
        ];

        $result = $this->service->calculate(10.00, $levels, 'A');

        $this->assertEquals(0.00, $result['final_commission']);
        $this->assertEquals('C', $result['weakest_person']);
        $this->assertEquals(0.00, $result['weakest_sales']);
        $this->assertEquals(0.00, $result['weakest_commission']);
    }

    /**
     * Test 5: Missing child branches with strict branching enabled.
     * If strict_branching is true and a leader only has 1 branch (e.g. no side branch),
     * the evaluation rule explicitly yields 0.00.
     */
    public function test_missing_child_branch_rules(): void
    {
        // Single branch under A (no side person defined)
        $levels = [
            [
                'level' => 1,
                'leader' => 'A',
                'main_person' => 'B',
                'main_sales' => 1000.00,
                'side_person' => null,
                'side_sales' => 0.00,
            ],
        ];

        // Non-strict: single branch evaluates to its own value ($50.00)
        $resNonStrict = $this->service->calculate(5.00, $levels, 'A', ['strict_branching' => false]);
        $this->assertEquals(50.00, $resNonStrict['final_commission']);

        // Strict: requires >= 2 branches; missing branch evaluates to $0.00
        $resStrict = $this->service->calculate(5.00, $levels, 'A', ['strict_branching' => true]);
        $this->assertEquals(0.00, $resStrict['final_commission']);
    }

    /**
     * Test 6: Rounding policy (half-up, cents precision).
     * Sales: $1234.56 @ 7.50%
     * Exact: 1234.56 * 0.075 = 92.592 => round half-up => $92.59
     * Sales: $987.65 @ 7.50%
     * Exact: 987.65 * 0.075 = 74.07375 => round half-up => $74.07
     */
    public function test_rounding_policy(): void
    {
        $levels = [
            [
                'level' => 1,
                'leader' => 'A',
                'main_person' => 'B',
                'main_sales' => 1234.56,
                'side_person' => 'S1',
                'side_sales' => 987.65,
            ],
        ];

        $result = $this->service->calculate(7.50, $levels, 'A');

        $this->assertEquals(92.59, $result['levels'][0]['main_commission']);
        $this->assertEquals(74.07, $result['levels'][0]['side_commission']);
        $this->assertEquals(74.07, $result['final_commission']);
        $this->assertEquals('S1', $result['weakest_person']);
    }

    /**
     * Test 7: Hierarchy-depth limits (configurable depth ceiling).
     * Provide a 10-level chain, but set max_depth = 5.
     * Only levels 1..5 are evaluated; levels 6..10 are pruned.
     */
    public function test_hierarchy_depth_limits(): void
    {
        $levels = $this->service->buildChain(
            numberOfLevels: 10,
            sideSales: 200.00,
            bottomMainSales: 50.00
        );

        $result = $this->service->calculate(5.00, $levels, 'A', ['max_depth' => 5]);

        $this->assertEquals(5, $result['number_of_levels']);
        $this->assertCount(5, $result['levels']);
    }

    /**
     * Test 8: Arbitrary depth support (10, 20, 50, 100 levels).
     */
    public function test_arbitrary_depth_fifty_and_hundred_levels(): void
    {
        // 50 levels: side = 500 ($25), bottom = 100 ($5)
        $levels50 = $this->service->buildChain(50, 500.00, 100.00);
        $res50 = $this->service->calculate(5.00, $levels50);
        $this->assertEquals(50, $res50['number_of_levels']);
        $this->assertEquals(5.00, $res50['final_commission']);

        // 100 levels: side = 400 ($40), bottom = 80 ($8) @ 10%
        $levels100 = $this->service->buildChain(100, 400.00, 80.00);
        $res100 = $this->service->calculate(10.00, $levels100);
        $this->assertEquals(100, $res100['number_of_levels']);
        $this->assertEquals(8.00, $res100['final_commission']);
    }

    /**
     * Test 9: Duplicate sale prevention.
     * If the same transaction identifier is encountered twice, throw InvalidArgumentException.
     */
    public function test_duplicate_sale_prevention(): void
    {
        $levels = [
            [
                'level' => 1,
                'leader' => 'A',
                'main_person' => 'B',
                'main_sales' => 100.00,
                'main_sale_id' => 'TXN-DUPLICATE-001',
                'side_person' => 'S1',
                'side_sales' => 200.00,
                'side_sale_id' => 'TXN-DUPLICATE-001', // duplicate sale key!
            ],
        ];

        try {
            $this->service->calculate(5.00, $levels);
            $this->fail('Expected InvalidArgumentException was not thrown for duplicate sales.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Duplicate sales detected in calculation run', $e->getMessage());
        }
    }

    /**
     * Test 10: Input validation for negative rates, out-of-range rates, and negative sales.
     */
    public function test_input_validation(): void
    {
        // Negative rate
        try {
            $this->service->calculateIndividualCommission(100, -5);
            $this->fail('Expected exception for negative rate');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('between 0.00% and 100.00%', $e->getMessage());
        }

        // Rate > 100%
        try {
            $this->service->calculateIndividualCommission(100, 105);
            $this->fail('Expected exception for rate > 100%');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('between 0.00% and 100.00%', $e->getMessage());
        }

        // Negative sales
        try {
            $this->service->calculateIndividualCommission(-50, 5);
            $this->fail('Expected exception for negative sales');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('cannot be negative', $e->getMessage());
        }
    }
}
