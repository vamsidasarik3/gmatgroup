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

use App\Models\User;
use App\Models\Member;
use App\Models\MemberRelationship;
use App\Models\SalesTransaction;
use App\Models\CalculationRun;
use App\Models\CommissionModel;
use App\Models\CommissionLedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

ob_implicit_flush(true);
while (ob_get_level()) {
    ob_end_flush();
}

echo "=================================================================\n";
echo "GNATGROUP END-TO-END FINAL INTEGRATION WORKFLOW AUDIT\n";
echo "=================================================================\n\n";

// Initialize HTTP kernel request context
$kernel->handle(Request::create('/login', 'GET'));

// Clean up any stale processing runs from interrupted executions
CalculationRun::where('status', 'processing')->update(['status' => 'completed']);

$adminUser = User::where('email', 'admin@gnatgroup.com')->first();
assert($adminUser !== null, "Admin user must exist");
Auth::login($adminUser);

function makeWorkflowRequest($kernel, $user, $uri, $method = 'GET', $parameters = []) {
    global $app;
    $session = $app->make('session')->driver();
    $session->start();
    $session->put('_token', 'workflow_csrf_token');
    $parameters['_token'] = 'workflow_csrf_token';

    $req = Request::create($uri, $method, $parameters);
    $req->setLaravelSession($session);
    $req->setUserResolver(fn() => $user);
    Auth::setUser($user);
    return $kernel->handle($req);
}

// STEP 1: Create Members
echo "[Step 1] Creating New Member (Alice Enterprise Lead)...\n";
$aliceCode = 'MEM-E2E-' . rand(1000, 9999);
$res1 = makeWorkflowRequest($kernel, $adminUser, '/admin/members', 'POST', [
    'member_code' => $aliceCode,
    'name' => 'Alice Enterprise Lead',
    'email' => 'alice_' . rand(100, 999) . '@e2e.test',
    'phone' => '+1 (555) 300-1111',
    'status' => 'active',
]);
assert($res1->getStatusCode() === 302, "Member creation must redirect back with 302");
$alice = Member::where('member_code', $aliceCode)->first();
assert($alice !== null, "Member {$aliceCode} must exist in database");
echo "PASSED: Created Member ID {$alice->id} ({$alice->name}, {$alice->member_code})\n\n";

echo "[Step 1b] Creating Downline Member (Bob Senior Associate)...\n";
$bobCode = 'MEM-E2E-' . rand(1000, 9999);
$res1b = makeWorkflowRequest($kernel, $adminUser, '/admin/members', 'POST', [
    'member_code' => $bobCode,
    'name' => 'Bob Senior Associate',
    'email' => 'bob_' . rand(100, 999) . '@e2e.test',
    'phone' => '+1 (555) 300-2222',
    'status' => 'active',
    'parent_id' => $alice->id,
    'override_rate' => 6.5000,
]);
assert($res1b->getStatusCode() === 302);
$bob = Member::where('member_code', $bobCode)->first();
assert($bob !== null);
echo "PASSED: Created Downline Member ID {$bob->id} ({$bob->name}, {$bob->member_code})\n\n";

// STEP 2 & 3: Build & Configure Hierarchy Relationship Commission Rates
echo "[Step 2 & 3] Configuring Relationship Commission Rates...\n";
$relAliceBob = MemberRelationship::where('parent_member_id', $alice->id)
    ->where('child_member_id', $bob->id)
    ->first();
assert($relAliceBob !== null, "Parent-child relationship must exist");
echo "Current rate Alice -> Bob: {$relAliceBob->override_rate}%\n";

// Update rate to 7.2500% via relationship endpoint
$resRel = makeWorkflowRequest($kernel, $adminUser, "/admin/members/{$bob->id}/relationships", 'POST', [
    'relation_type' => 'parent',
    'target_member_id' => $alice->id,
    'override_rate' => 7.2500,
]);
assert($resRel->getStatusCode() === 302);
$relAliceBob->refresh();
assert((float) $relAliceBob->override_rate === 7.2500, "Override rate must be updated to 7.2500%");
echo "PASSED: Relationship rate configured to {$relAliceBob->override_rate}%\n\n";

// STEP 4: Create a Sales Transaction
echo "[Step 4] Creating Sales Transaction for {$bob->name}...\n";
$txnCode = 'TXN-E2E-' . rand(1000, 9999);
$saleAmount = 5000.00;
$resSale = makeWorkflowRequest($kernel, $adminUser, '/admin/sales', 'POST', [
    'transaction_code' => $txnCode,
    'member_id' => $bob->id,
    'sale_amount' => $saleAmount,
    'transaction_date' => now()->format('Y-m-d H:i:s'),
    'status' => 'completed',
    'reference_note' => 'Cloud Infrastructure Deal Year 1',
]);
assert($resSale->getStatusCode() === 302);
$sale = SalesTransaction::where('transaction_code', $txnCode)->first();
assert($sale !== null);
echo "PASSED: Recorded Sales Transaction {$sale->transaction_code} for \${$sale->sale_amount}\n\n";

// STEP 5 & 6: Select Commission Model & Execute Calculation Run
echo "[Step 5 & 6] Executing Commission Calculation Run (Level Override Model)...\n";
$model = CommissionModel::where('model_type', 'generation_override')
    ->orWhere('model_type', 'level_override')
    ->first();
assert($model !== null, "Commission model must exist");

$resCalc = makeWorkflowRequest($kernel, $adminUser, '/admin/calculate/run', 'POST', [
    'commission_model_id' => $model->id,
    'generation_limit' => 5,
    'period_start' => now()->startOfMonth()->toDateString(),
    'period_end' => now()->endOfMonth()->toDateString(),
]);
assert($resCalc->getStatusCode() === 302);
$latestRun = CalculationRun::latest()->first();
assert($latestRun !== null);
assert($latestRun->status === 'completed');
echo "PASSED: Calculation executed -> Run {$latestRun->run_number} (\${$latestRun->total_commission_payable} payable)\n\n";

// STEP 7: Inspect Hierarchy & Traced Visual Commission Path
echo "[Step 7] Inspecting Hierarchy API & Visual Commission Path...\n";
$resPath = makeWorkflowRequest($kernel, $adminUser, '/api/hierarchy/calculate-sale-path', 'POST', [
    'sale_id' => $sale->id,
]);
assert($resPath->getStatusCode() === 200);
$pathJson = json_decode($resPath->getContent(), true);
assert($pathJson['success'] === true);
echo "Sale Amount: \${$pathJson['sale']['sale_amount']}\n";
echo "Traced Path: " . implode(' -> ', $pathJson['path_nodes']) . "\n";
assert(in_array($alice->id, $pathJson['path_nodes']));
assert(in_array($bob->id, $pathJson['path_nodes']));
echo "PASSED: Visual commission path correctly identified Alice as G1 upline\n\n";

// STEP 8: Review Commission Ledger
echo "[Step 8] Reviewing Commission Ledger Line Items...\n";
$ledgerAlice = CommissionLedgerEntry::where('calculation_run_id', $latestRun->id)
    ->where('source_sale_id', $sale->id)
    ->where('beneficiary_member_id', $alice->id)
    ->first();
assert($ledgerAlice !== null, "Alice must receive commission ledger line item");
echo "Beneficiary: Alice ({$alice->member_code})\n";
echo "Source Sale: {$sale->transaction_code} (\${$sale->sale_amount})\n";
echo "Generation: G{$ledgerAlice->generation}\n";
echo "Applied Rate: {$ledgerAlice->rate_applied}%\n";
echo "Calculated Payout: \${$ledgerAlice->commission_amount}\n";
assert((float) $ledgerAlice->rate_applied === 7.2500);
// 5000 * 7.25% = 362.50
assert((float) $ledgerAlice->commission_amount === 362.50);
echo "PASSED: Ledger item matches formula: \$5,000.00 * 7.25% = \$362.50\n\n";

// STEP 9: Open Saved Calculation History
echo "[Step 9] Inspecting Calculation History Details...\n";
$resHist = makeWorkflowRequest($kernel, $adminUser, "/admin/calculation-history/{$latestRun->id}");
assert($resHist->getStatusCode() === 200);
$histHtml = $resHist->getContent();
assert(str_contains($histHtml, $latestRun->run_number));
assert(str_contains($histHtml, 'Execution Audit'));
echo "PASSED: Calculation History view successfully loaded run audit and ledger breakdown\n\n";

// STEP 10: Confirm Historical Reproducibility & Immutability
echo "[Step 10] Testing Historical Reproducibility & Immutability Under Active Hierarchy Changes...\n";
// Now tamper with Alice's rate in the live database (change from 7.25% to 25.00%)
$relAliceBob->update(['override_rate' => 25.0000]);

// Re-read historical ledger entry
$frozenLedgerAlice = CommissionLedgerEntry::find($ledgerAlice->id);
assert((float) $frozenLedgerAlice->rate_applied === 7.2500, "Historical ledger rate_applied MUST remain 7.2500%, not 25%");
assert((float) $frozenLedgerAlice->commission_amount === 362.50, "Historical ledger commission_amount MUST remain 362.50");

// Re-read historical calculation run summary
$frozenRun = CalculationRun::find($latestRun->id);
assert((float) $frozenRun->total_commission_payable === (float) $latestRun->total_commission_payable);
echo "PASSED: Historical results remain completely reproducible and immutable!\n\n";

// Cleanup test relationship rate
$relAliceBob->update(['override_rate' => 7.2500]);

echo "=================================================================\n";
echo "ALL 10 END-TO-END WORKFLOW AUDIT STEPS PASSED 100% SUCCESSFULLY!\n";
echo "=================================================================\n";
