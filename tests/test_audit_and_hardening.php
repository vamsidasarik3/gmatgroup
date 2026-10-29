<?php

$loader = require_once 'G:/xampp/htdocs/custom/public_html/vendor/autoload.php';
$loader->setPsr4('App\\', [dirname(__DIR__).'/app/']);
$loader->setPsr4('Database\\Seeders\\', [dirname(__DIR__).'/database/seeders/']);

foreach (glob(dirname(__DIR__) . '/app/Models/*.php') as $modelFile) {
    require_once $modelFile;
}

if (function_exists('opcache_reset')) {
    opcache_reset();
}

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Member;
use App\Models\MemberRelationship;
use App\Models\SalesTransaction;
use App\Models\CalculationRun;
use App\Models\CommissionModel;
use App\Models\CommissionLedgerEntry;
use App\Services\WeakestLinkCommissionService;
use App\Services\LevelOverrideCommissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

echo "====================================================\n";
echo "GNATGROUP SECURITY AUDIT & HARDENING TEST SUITE\n";
echo "====================================================\n\n";

$passedCount = 0;
$failedCount = 0;

function runTest(string $title, callable $test) {
    global $passedCount, $failedCount;
    echo "[TEST] {$title}...\n";
    try {
        $test();
        echo "  --> PASSED\n\n";
        $passedCount++;
    } catch (\Throwable $e) {
        echo "  --> FAILED: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n\n";
        $failedCount++;
    }
}

// Helper for authenticated requests
function makeTestRequest($kernel, $user, $uri, $method = 'GET', $parameters = [], $withCsrf = true) {
    global $app;
    $session = $app->make('session')->driver();
    $session->start();
    if ($withCsrf) {
        $session->put('_token', 'test_audit_csrf_token');
        $parameters['_token'] = 'test_audit_csrf_token';
    }

    $req = Request::create($uri, $method, $parameters);
    $req->setLaravelSession($session);
    if ($user) {
        $req->setUserResolver(fn() => $user);
        Auth::setUser($user);
    }
    return $kernel->handle($req);
}

// 1. Get or create test users
$adminUser = User::where('email', 'admin@gnatgroup.com')->first();
assert($adminUser !== null, "Admin user must exist");
$adminUser->update(['role' => 'admin']);

$regularUser = User::firstOrCreate(
    ['email' => 'regular_member@gnatgroup.com'],
    [
        'name' => 'Regular Member',
        'role' => 'member',
        'password' => bcrypt('secret123'),
    ]
);
$regularUser->update(['role' => 'member']);

// TEST 1: Unauthenticated protection
runTest("1. Authentication Protection on Admin Routes", function () use ($kernel) {
    $res = makeTestRequest($kernel, null, '/admin/ledger');
    assert($res->getStatusCode() === 302, "Expected 302 redirect for unauthenticated user, got {$res->getStatusCode()}");
    assert(str_contains($res->headers->get('Location'), '/login'), "Must redirect to /login");

    $res2 = makeTestRequest($kernel, null, '/admin/calculation-history');
    assert($res2->getStatusCode() === 302, "Expected 302 redirect for calculation history");
});

// TEST 2: Role-based authorization: non-admin access is rejected
runTest("2. Role-Based Authorization Enforcement (Non-Admin 403)", function () use ($kernel, $regularUser) {
    $res = makeTestRequest($kernel, $regularUser, '/admin/ledger');
    assert($res->getStatusCode() === 403, "Expected 403 Forbidden for non-admin role, got {$res->getStatusCode()}");

    $res2 = makeTestRequest($kernel, $regularUser, '/admin/calculation-history');
    assert($res2->getStatusCode() === 403, "Expected 403 Forbidden for history");
});

// TEST 3: Admin role has authorized access
runTest("3. Admin Role Access to Protected Ledger and History", function () use ($kernel, $adminUser) {
    $res = makeTestRequest($kernel, $adminUser, '/admin/ledger');
    assert($res->getStatusCode() === 200, "Admin must be able to view ledger, got {$res->getStatusCode()}");

    $res2 = makeTestRequest($kernel, $adminUser, '/admin/calculation-history');
    assert($res2->getStatusCode() === 200, "Admin must be able to view history, got {$res2->getStatusCode()}");
});

// TEST 4: CSRF Protection
runTest("4. CSRF Protection on State-Changing Requests", function () use ($kernel, $adminUser) {
    // Attempt POST without CSRF token
    $res = makeTestRequest($kernel, $adminUser, '/admin/members', 'POST', [
        'member_code' => 'CSRF-FAIL-' . rand(100, 999),
        'name' => 'Should Fail CSRF',
        'status' => 'active',
    ], false); // withCsrf = false

    assert($res->getStatusCode() === 419, "Expected 419 CSRF Token Mismatch, got {$res->getStatusCode()}");
});

// TEST 5: Server-Side Validation: Invalid Rates & Negative Sales
runTest("5. Server-Side Validation on Financial Boundaries", function () use ($kernel, $adminUser) {
    // 5a. Negative sale amount
    $resSale = makeTestRequest($kernel, $adminUser, '/admin/sales', 'POST', [
        'transaction_code' => 'NEG-' . rand(100, 999),
        'member_id' => 1,
        'sale_amount' => -150.00, // Invalid negative
        'transaction_date' => now()->toDateString(),
        'status' => 'completed',
    ]);
    assert($resSale->getStatusCode() === 302, "Validation failure must redirect back with errors");
    $sessionErrors = session()->get('errors');
    assert($sessionErrors !== null && $sessionErrors->has('sale_amount'), "Session must contain sale_amount validation error");

    // 5b. Invalid override rate (> 100%)
    $resMember = makeTestRequest($kernel, $adminUser, '/admin/members', 'POST', [
        'member_code' => 'RATE-ERR-' . rand(100, 999),
        'name' => 'Rate Overflow',
        'status' => 'active',
        'parent_id' => 1,
        'override_rate' => 150.00, // Exceeds 100%
    ]);
    assert($resMember->getStatusCode() === 302, "Invalid rate must fail validation");
    $errors = session()->get('errors');
    assert($errors !== null && $errors->has('override_rate'), "Session must contain override_rate validation error");
});

// TEST 6: Protection Against Hierarchy Cycles
runTest("6. Protection Against Hierarchy Cycles & Loops", function () use ($kernel, $adminUser) {
    // Try to assign a member as its own parent via relationships
    $res = makeTestRequest($kernel, $adminUser, '/admin/members/1/relationships', 'POST', [
        'relation_type' => 'parent',
        'target_member_id' => 1, // Self-referential loop
        'override_rate' => 5.0,
    ]);
    assert($res->getStatusCode() === 302, "Self loop must be rejected");
    $errorFlash = session()->get('error');
    assert($errorFlash !== null && str_contains($errorFlash, 'Cycle detected'), "Cycle detection must reject self-loop");
});

// TEST 7: Model 1 Weakest Link Engine Boundary Tests
runTest("7. Model 1 (Weakest Link) Calculation & Boundary Tests", function () {
    $service = new WeakestLinkCommissionService();

    // 7a. Normal calculation
    $levels = [
        [
            'level' => 1,
            'leader' => 'TopA',
            'main_person' => 'BranchB',
            'main_sales' => 1000.00,
            'side_person' => 'BranchC',
            'side_sales' => 500.00,
        ],
    ];
    $result = $service->calculate(5.00, $levels, 'TopA');
    // min(1000 * 5% = 50.00, 500 * 5% = 25.00) = 25.00
    assert($result['final_commission'] === 25.00, "Expected $25.00 for weakest branch, got {$result['final_commission']}");
    assert($result['weakest_person'] === 'BranchC', "Weakest branch must be BranchC");

    // 7b. Zero rate boundary
    $zeroResult = $service->calculate(0.00, $levels, 'TopA');
    assert($zeroResult['final_commission'] === 0.00, "0% rate must result in 0.00 commission");

    // 7c. Negative sales exception
    $caughtNeg = false;
    try {
        $service->calculateIndividualCommission(-100.00, 5.00);
    } catch (\InvalidArgumentException $e) {
        $caughtNeg = true;
    }
    assert($caughtNeg, "Negative sale must throw InvalidArgumentException");

    // 7d. Rate > 100% exception
    $caughtRate = false;
    try {
        $service->calculateIndividualCommission(100.00, 105.00);
    } catch (\InvalidArgumentException $e) {
        $caughtRate = true;
    }
    assert($caughtRate, "Rate > 100% must throw InvalidArgumentException");
});

// TEST 8: Model 2 Level/Generation Override Boundary Tests
runTest("8. Model 2 (Level Override) Calculation & Boundary Tests", function () {
    $service = new LevelOverrideCommissionService();

    // 8a. Multi-generation upline from original sale base
    $edges = [
        ['parent' => 'Root', 'child' => 'Leader', 'rate' => 3.0000],
        ['parent' => 'Leader', 'child' => 'Seller', 'rate' => 5.0000],
    ];
    $sales = [
        ['salesperson' => 'Seller', 'amount' => 1000.00, 'sale_id' => 'TXN-001'],
    ];

    $result = $service->calculate($edges, $sales, 5);
    // Leader gets Gen 1: 1000 * 5% = 50.00
    // Root gets Gen 2: 1000 * 3% = 30.00
    // Total commission = 80.00
    assert($result['total_commission'] === 80.00, "Expected $80.00 total commission, got {$result['total_commission']}");
    assert($result['earnings_by_person']['Leader']['override_commission'] === 50.00, "Leader must earn 50.00");
    assert($result['earnings_by_person']['Root']['override_commission'] === 30.00, "Root must earn 30.00");

    // 8b. Depth cutoff boundary (maxGenerations = 1 cuts off Root)
    $cutoffResult = $service->calculate($edges, $sales, 1);
    assert($cutoffResult['total_commission'] === 50.00, "Max generation 1 must cutoff Root at Gen 2");
    assert($cutoffResult['earnings_by_person']['Root']['override_commission'] === 0.00, "Root must receive 0 when beyond depth cutoff");

    // 8c. Cycle rejection in service
    $cycleEdges = [
        ['parent' => 'A', 'child' => 'B', 'rate' => 5.0],
        ['parent' => 'B', 'child' => 'A', 'rate' => 5.0], // Loop A -> B -> A
    ];
    $caughtCycle = false;
    try {
        $service->calculate($cycleEdges, $sales, 5);
    } catch (\InvalidArgumentException $e) {
        $caughtCycle = true;
    }
    assert($caughtCycle, "Cyclic edges in service must throw InvalidArgumentException");
});

// TEST 9: Safe Handling of Concurrent Calculation Requests
runTest("9. Concurrency Lock Protection on Calculations", function () use ($kernel, $adminUser) {
    $model = CommissionModel::first();
    assert($model !== null);

    // Create a mock active run with status 'processing'
    $mockActiveRun = CalculationRun::create([
        'run_uuid' => (string) \Illuminate\Support\Str::uuid(),
        'run_number' => 'RUN-ACTIVE-CONCURRENT',
        'commission_model_id' => $model->id,
        'period_start' => now()->toDateString(),
        'period_end' => now()->toDateString(),
        'status' => 'processing',
        'generation_limit' => 5,
        'total_sales_amount' => 0.00,
        'total_commission_payable' => 0.00,
        'total_beneficiaries_count' => 0,
        'settings_snapshot' => [],
        'started_at' => now(),
    ]);

    // Attempt to trigger concurrent run on same model
    $res = makeTestRequest($kernel, $adminUser, '/admin/calculate/run', 'POST', [
        'commission_model_id' => $model->id,
        'generation_limit' => 5,
    ]);

    // Cleanup mock run immediately
    $mockActiveRun->delete();

    assert($res->getStatusCode() === 302, "Must redirect back with lock error");
    $errorFlash = session()->get('error');
    assert(str_contains((string) $errorFlash, 'already in progress'), "Error message must indicate calculation is already in progress");
});

// TEST 10: Historical Invariance Verification
runTest("10. Historical Invariance: Previous Results Remain Unchanged When Rate Changes", function () use ($kernel, $adminUser) {
    // 1. Get an existing historical calculation run and ledger entry
    $historicalRun = CalculationRun::where('status', 'completed')->with('ledgerEntries')->first();
    assert($historicalRun !== null, "Must have at least one completed historical run");
    $historicalEntry = $historicalRun->ledgerEntries()->where('is_eligible', true)->first();
    assert($historicalEntry !== null, "Must have at least one eligible historical ledger line");

    $savedRate = (float) $historicalEntry->rate_applied;
    $savedCommission = (float) $historicalEntry->commission_amount;
    $savedTotalPayable = (float) $historicalRun->total_commission_payable;

    // 2. Select any active relationship in the system and modify its rate
    $rel = MemberRelationship::where('status', 'active')->first();
    assert($rel !== null, "Active relationship must exist");

    $originalRelRate = (float) $rel->override_rate;
    // Intentionally tamper with active rate to 88.88%
    $rel->update(['override_rate' => 88.8800]);

    // 3. Re-query the historical ledger entry and calculation run
    $reloadedEntry = CommissionLedgerEntry::find($historicalEntry->id);
    $reloadedRun = CalculationRun::find($historicalRun->id);

    // 4. Assert that the historical record is completely preserved and untouched!
    assert((float) $reloadedEntry->rate_applied === $savedRate, "Historical ledger rate_applied must remain {$savedRate}, unaffected by live rate edits");
    assert((float) $reloadedEntry->commission_amount === $savedCommission, "Historical commission_amount must remain {$savedCommission}");
    assert((float) $reloadedRun->total_commission_payable === $savedTotalPayable, "Historical run total_commission_payable must remain {$savedTotalPayable}");

    // Restore original relationship rate
    $rel->update(['override_rate' => $originalRelRate]);
});

echo "====================================================\n";
echo "AUDIT SUMMARY: {$passedCount} PASSED, {$failedCount} FAILED\n";
echo "====================================================\n";

if ($failedCount > 0) {
    exit(1);
}
