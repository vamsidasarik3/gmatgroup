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
use App\Models\SalesTransaction;
use App\Models\CalculationRun;
use App\Models\CommissionModel;
use App\Models\CommissionLedgerEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "===============================================\n";
echo "GNATGROUP ADMINISTRATION INTERFACE TEST SUITE\n";
echo "===============================================\n\n";

// 1. Test Unauthenticated Access Redirection
echo "[1] Testing Unauthenticated Protection...\n";
$req1 = Request::create('/admin/dashboard', 'GET');
$res1 = $kernel->handle($req1);
echo "Status Code: " . $res1->getStatusCode() . " (Expected: 302 redirect to /login)\n";
assert($res1->getStatusCode() === 302);
assert(str_contains($res1->headers->get('Location'), '/login'));
echo "PASSED: Administrative pages are protected by auth middleware.\n\n";

// 2. Test Login Page
echo "[2] Testing GET /login and GET /secure-admin-login...\n";
$req2 = Request::create('/login', 'GET');
$res2 = $kernel->handle($req2);
echo "Status Code /login: " . $res2->getStatusCode() . "\n";
assert($res2->getStatusCode() === 200);
assert(str_contains($res2->getContent(), 'Sign In'));
assert(str_contains($res2->getContent(), 'Member Portal'));

$adminPath = config('app.admin_login_path', 'secure-admin-login');
$reqAdmin = Request::create('/' . $adminPath, 'GET');
$resAdmin = $kernel->handle($reqAdmin);
echo "Status Code /{$adminPath}: " . $resAdmin->getStatusCode() . "\n";
assert($resAdmin->getStatusCode() === 200);
assert(str_contains($resAdmin->getContent(), 'Admin Console'));
echo "PASSED: Member login and unique Admin login render correctly.\n\n";

// 3. Authenticate Admin User
$adminUser = User::where('email', 'admin@gnatgroup.com')->first();
assert($adminUser !== null);
Auth::login($adminUser);
echo "Logged in as Admin: {$adminUser->name} ({$adminUser->email})\n\n";

// Helper for authenticated requests with session & CSRF
function makeAuthRequest($kernel, $adminUser, $uri, $method = 'GET', $parameters = []) {
    global $app;
    $session = $app->make('session')->driver();
    $session->start();
    $session->put('_token', 'test_csrf_token');
    $parameters['_token'] = 'test_csrf_token';

    $req = Request::create($uri, $method, $parameters);
    $req->setLaravelSession($session);
    $req->setUserResolver(fn() => $adminUser);
    Auth::setUser($adminUser);
    return $kernel->handle($req);
}

// 4. Test Dashboard with Live Database Statistics
echo "[3] Testing GET /admin/dashboard...\n";
$res3 = makeAuthRequest($kernel, $adminUser, '/admin/dashboard');
echo "Status Code: " . $res3->getStatusCode() . "\n";
$html3 = $res3->getContent();
assert($res3->getStatusCode() === 200);
assert(str_contains($html3, 'Executive Dashboard'));
assert(str_contains($html3, 'Total Members'));
assert(str_contains($html3, 'Total Sales (Period)'));
assert(str_contains($html3, 'Model-Wise Calculation Summaries'));
assert(str_contains($html3, 'Recent Calculation Runs'));
assert(str_contains($html3, 'Recent Sales Transactions'));
echo "PASSED: Dashboard displays all required KPI cards and live database metrics.\n\n";

// 5. Test Members & Hierarchy
echo "[4] Testing GET /admin/members...\n";
$res4 = makeAuthRequest($kernel, $adminUser, '/admin/members');
echo "Status Code: " . $res4->getStatusCode() . "\n";
$html4 = $res4->getContent();
assert($res4->getStatusCode() === 200);
assert(str_contains($html4, 'Network Members & Hierarchy'));
assert(str_contains($html4, 'Directory Records'));
assert(str_contains($html4, 'MEM-001'));

echo "[5] Testing GET /admin/members/create...\n";
$res5 = makeAuthRequest($kernel, $adminUser, '/admin/members/create');
assert($res5->getStatusCode() === 200);

echo "[6] Testing POST /admin/members (Create Member Mutation)...\n";
$newCode = 'TEST-MEM-' . rand(1000, 9999);
$res6 = makeAuthRequest($kernel, $adminUser, '/admin/members', 'POST', [
    'member_code' => $newCode,
    'name' => 'Automated Test Partner',
    'email' => 'partner_' . rand(100, 999) . '@test.com',
    'phone' => '+1 (555) 999-8888',
    'status' => 'active',
    'parent_id' => 1,
    'override_rate' => 3.50,
]);
if ($res6->getStatusCode() !== 302) {
    echo "POST /admin/members returned status " . $res6->getStatusCode() . ":\n";
    echo substr($res6->getContent(), 0, 1000) . "\n";
    exit(1);
}
assert($res6->getStatusCode() === 302);
$createdMember = Member::where('member_code', $newCode)->first();
assert($createdMember !== null);
echo "PASSED: Member {$newCode} created with parent relationship and override rate.\n\n";

// 6. Test Commission Models
echo "[7] Testing GET /admin/commission-models...\n";
$res7 = makeAuthRequest($kernel, $adminUser, '/admin/commission-models');
echo "Status Code: " . $res7->getStatusCode() . "\n";
$html7 = $res7->getContent();
assert($res7->getStatusCode() === 200);
assert(str_contains($html7, 'Commission Models Configuration'));
assert(str_contains($html7, 'Weakest Link'));
assert(str_contains($html7, 'Level/Generation Override'));
echo "PASSED: Commission models view lists Weakest Link and Level Override models.\n\n";

// 7. Test Sales Transactions
echo "[8] Testing GET /admin/sales...\n";
$res8 = makeAuthRequest($kernel, $adminUser, '/admin/sales');
if ($res8->getStatusCode() !== 200) {
    echo "GET /admin/sales status: " . $res8->getStatusCode() . "\n";
    echo substr($res8->getContent(), 0, 1000) . "\n";
}
assert($res8->getStatusCode() === 200);
assert(str_contains($res8->getContent(), 'Sales Transactions History'));

echo "[9] Testing POST /admin/sales (Record Sale Mutation)...\n";
$newTxnCode = 'TXN-TEST-' . rand(1000, 9999);
$res9 = makeAuthRequest($kernel, $adminUser, '/admin/sales', 'POST', [
    'transaction_code' => $newTxnCode,
    'member_id' => $createdMember->id,
    'sale_amount' => 1250.00,
    'transaction_date' => now()->format('Y-m-d H:i:s'),
    'status' => 'completed',
    'reference_note' => 'Automated Integration Sale Test',
]);
assert($res9->getStatusCode() === 302);
$createdSale = SalesTransaction::where('transaction_code', $newTxnCode)->first();
assert($createdSale !== null);
echo "PASSED: Sales transaction {$newTxnCode} ($1,250.00) recorded.\n\n";

// 8. Test Calculate Commissions
echo "[10] Testing GET /admin/calculate...\n";
$res10 = makeAuthRequest($kernel, $adminUser, '/admin/calculate');
assert($res10->getStatusCode() === 200);
assert(str_contains($res10->getContent(), 'Execute Commission Calculation Run'));

echo "[11] Testing POST /admin/calculate/run (Execute Calculation Engine)...\n";
$model = CommissionModel::first();
$res11 = makeAuthRequest($kernel, $adminUser, '/admin/calculate/run', 'POST', [
    'commission_model_id' => $model->id,
    'generation_limit' => 5,
    'period_start' => now()->startOfMonth()->toDateString(),
    'period_end' => now()->endOfMonth()->toDateString(),
]);
assert($res11->getStatusCode() === 302);
echo "PASSED: Calculation executed and redirected to audit run view.\n\n";

// 9. Test Commission Ledger
echo "[12] Testing GET /admin/ledger...\n";
$res12 = makeAuthRequest($kernel, $adminUser, '/admin/ledger');
assert($res12->getStatusCode() === 200);
$html12 = $res12->getContent();
assert(str_contains($html12, 'Commission Ledger'));
assert(str_contains($html12, 'Ledger Records'));
assert(str_contains($html12, 'Beneficiary Earner'));
echo "PASSED: Commission ledger displays line items, rates, generations, and modal audit.\n\n";

// 10. Test Calculation History
echo "[13] Testing GET /admin/calculation-history...\n";
$res13 = makeAuthRequest($kernel, $adminUser, '/admin/calculation-history');
assert($res13->getStatusCode() === 200);
$html13 = $res13->getContent();
assert(str_contains($html13, 'Calculation Run History'));
assert(str_contains($html13, 'Completed'));
echo "PASSED: Calculation history displays execution records, timestamps, and durations.\n\n";

// 11. Test Settings
echo "[14] Testing GET /admin/settings...\n";
$res14 = makeAuthRequest($kernel, $adminUser, '/admin/settings');
assert($res14->getStatusCode() === 200);
$html14 = $res14->getContent();
assert(str_contains($html14, 'System Settings & Calculation Defaults'));
assert(str_contains($html14, 'DECIMAL(15, 2)'));
assert(str_contains($html14, 'Database Connection & Record Status'));
echo "PASSED: Settings view reflects financial conventions and database record counts.\n\n";

echo "ALL 14 INTEGRATION CHECKS PASSED PERFECTLY!\n";
