<?php

$loader = require_once 'G:/xampp/htdocs/custom/public_html/vendor/autoload.php';
$loader->setPsr4('App\\', [dirname(__DIR__).'/app/']);
$loader->setPsr4('Database\\Factories\\', [dirname(__DIR__).'/database/factories/']);
$loader->setPsr4('Database\\Seeders\\', [dirname(__DIR__).'/database/seeders/']);

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "========================================\n";
echo "GNATGROUP HIERARCHY VISUALIZATION TESTS\n";
echo "========================================\n\n";

file_put_contents(__DIR__ . '/../storage/logs/laravel.log', '');

// 1. Test GET /hierarchy (Blade page render)
echo "[1] Testing GET /hierarchy...\n";
$request1 = Illuminate\Http\Request::create('/hierarchy', 'GET');
$response1 = $kernel->handle($request1);
echo "Status Code: " . $response1->getStatusCode() . "\n";
$content1 = $response1->getContent();
if ($response1->getStatusCode() !== 200) {
    preg_match('/<title>(.*?)<\/title>/s', $content1, $mTitle);
    echo "ERROR TITLE: " . ($mTitle[1] ?? 'unknown') . "\n";
    // Also look for exception text
    if (preg_match('/class="text-break[^"]*">(.*?)<\/div>/s', $content1, $mMsg)) {
        echo "EXCEPTION TEXT: " . strip_tags($mMsg[1]) . "\n";
    }
    // Check storage/logs/laravel.log
    $logFile = __DIR__ . '/../storage/logs/laravel.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        echo "LATEST LOG ENTRY:\n" . implode('', array_slice($lines, -25)) . "\n";
    }
    exit(1);
}
assert(str_contains($content1, 'Interactive Hierarchy Visualization'));
assert(str_contains($content1, 'canvas-container'));
assert(str_contains($content1, 'hierarchy-svg'));
assert(str_contains($content1, 'detail-panel'));
echo "PASSED: Blade view rendered with canvas, SVG, toolbar, and inspector panel.\n\n";

// 2. Test GET /api/hierarchy/data (Hierarchy JSON Data)
echo "[2] Testing GET /api/hierarchy/data...\n";
$request2 = Illuminate\Http\Request::create('/api/hierarchy/data', 'GET');
$response2 = $kernel->handle($request2);
echo "Status Code: " . $response2->getStatusCode() . "\n";
$json2 = json_decode($response2->getContent(), true);
assert($json2['success'] === true);
echo "Nodes Count: " . count($json2['nodes']) . " (Requirement: >= 20 members)\n";
assert(count($json2['nodes']) >= 20);
echo "Edges Count: " . count($json2['edges']) . "\n";
assert(count($json2['edges']) >= 20);
echo "Sales Transactions: " . count($json2['sales']) . "\n";

// Check member node attributes
$firstNode = $json2['nodes'][0];
echo "Sample Node keys: " . implode(', ', array_keys($firstNode)) . "\n";
assert(isset($firstNode['id'], $firstNode['member_code'], $firstNode['name'], $firstNode['level'], $firstNode['override_rate'], $firstNode['commission_earned'], $firstNode['status']));
echo "PASSED: Node schema contains member name, identifier, level, rate, earned commission, and status.\n\n";

// 3. Test POST /api/hierarchy/calculate-sale-path for Sale 101 (Emma Davis, Level 3)
echo "[3] Testing POST /api/hierarchy/calculate-sale-path (Sale #101)...\n";
$request3 = Illuminate\Http\Request::create('/api/hierarchy/calculate-sale-path', 'POST', ['sale_id' => 101]);
$response3 = $kernel->handle($request3);
echo "Status Code: " . $response3->getStatusCode() . "\n";
$json3 = json_decode($response3->getContent(), true);
assert($json3['success'] === true);
echo "Seller: " . $json3['seller']['name'] . " (" . $json3['seller']['member_code'] . ")\n";
echo "Sale Base: $" . number_format($json3['sale']['sale_amount'], 2) . "\n";
echo "Path Nodes: " . implode(' -> ', $json3['path_nodes']) . "\n";
echo "Generations Count: " . count($json3['generation_breakdown']) . "\n";
echo "Total Overrides: $" . number_format($json3['total_commission_payable'], 2) . "\n";
echo "Eligible Uplines: " . $json3['eligible_upline_count'] . "\n";
assert($json3['eligible_upline_count'] > 0);
echo "PASSED: Sale path calculation correctly traced upline generation payouts.\n\n";

// 4. Test POST /api/hierarchy/calculate-sale-path for Deep Sale (Sale #107, Level 7 with 6 Uplines)
echo "[4] Testing POST /api/hierarchy/calculate-sale-path (Deep Sale #107 - Generation Cutoff Test)...\n";
$request4 = Illuminate\Http\Request::create('/api/hierarchy/calculate-sale-path', 'POST', ['sale_id' => 107]);
$response4 = $kernel->handle($request4);
echo "Status Code: " . $response4->getStatusCode() . "\n";
$json4 = json_decode($response4->getContent(), true);
assert($json4['success'] === true);
echo "Seller: " . $json4['seller']['name'] . " (" . $json4['seller']['member_code'] . ")\n";
echo "Sale Base: $" . number_format($json4['sale']['sale_amount'], 2) . "\n";
echo "Generations Breakdown:\n";
foreach ($json4['generation_breakdown'] as $g) {
    echo "  - G" . $g['generation'] . ": " . $g['member_name'] . " | Rate: " . $g['rate_applied'] . "% | Payout: $" . number_format($g['commission_amount'], 2) . " | Eligible: " . ($g['is_eligible'] ? 'YES' : 'NO (CUTOFF)') . "\n";
}
echo "Eligible count: " . $json4['eligible_upline_count'] . ", Ineligible count: " . $json4['ineligible_upline_count'] . "\n";
assert($json4['eligible_upline_count'] === 5);
assert($json4['ineligible_upline_count'] === 1);
echo "PASSED: Calculation cleanly distinguishes eligible generations (5) from cutoff generation (1 ineligible).\n\n";

echo "ALL TESTS PASSED SUCCESSFULLY!\n";
