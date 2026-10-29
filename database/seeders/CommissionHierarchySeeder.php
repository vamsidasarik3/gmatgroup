<?php

namespace Database\Seeders;

use App\Models\CalculationRun;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionModel;
use App\Models\Member;
use App\Models\MemberRelationship;
use App\Models\SalesTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommissionHierarchySeeder extends Seeder
{
    /**
     * Seed a small 5-generation test hierarchy with relationships,
     * sales transactions, and an atomic calculation run producing ledger entries.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Create Saved Commission Model
            $model = CommissionModel::firstOrCreate(
                ['name' => 'GmatGroup 5-Generation Override Model'],
                [
                    'description' => 'Standard organizational override commission model with 5 generation ceiling.',
                    'model_type' => 'generation_override',
                    'status' => 'active',
                    'commission_rate' => 5.0000,
                    'max_generations' => 5,
                    'settings' => [
                        'currency' => 'USD',
                        'payout_threshold' => 10.00,
                        'max_generations' => 5,
                        'rate_convention' => 'percentage',
                        'prevent_duplicates' => true,
                    ],
                ]
            );

            // 2. Create Members in a 5-tier Hierarchy (A -> B -> C -> D -> E)
            $memberA = Member::firstOrCreate(
                ['member_code' => 'MEM-A'],
                ['name' => 'Alice Adams (National Director)', 'email' => 'alice@gmatgroup.com', 'status' => 'active']
            );
            $memberB = Member::firstOrCreate(
                ['member_code' => 'MEM-B'],
                ['name' => 'Bob Brown (Regional VP)', 'email' => 'bob@gmatgroup.com', 'status' => 'active']
            );
            $memberC = Member::firstOrCreate(
                ['member_code' => 'MEM-C'],
                ['name' => 'Charlie Clark (Senior Director)', 'email' => 'charlie@gmatgroup.com', 'status' => 'active']
            );
            $memberD = Member::firstOrCreate(
                ['member_code' => 'MEM-D'],
                ['name' => 'Diana Davis (District Manager)', 'email' => 'diana@gmatgroup.com', 'status' => 'active']
            );
            $memberE = Member::firstOrCreate(
                ['member_code' => 'MEM-E'],
                ['name' => 'Evan Evans (Sales Representative)', 'email' => 'evan@gmatgroup.com', 'status' => 'active']
            );

            // 3. Create Parent-Child Relationships with Configurable Override Rates
            // A sponsors B (5.00%), B sponsors C (4.00%), C sponsors D (3.00%), D sponsors E (2.00%)
            $edges = [
                ['parent' => $memberA->id, 'child' => $memberB->id, 'rate' => 5.0000],
                ['parent' => $memberB->id, 'child' => $memberC->id, 'rate' => 4.0000],
                ['parent' => $memberC->id, 'child' => $memberD->id, 'rate' => 3.0000],
                ['parent' => $memberD->id, 'child' => $memberE->id, 'rate' => 2.0000],
            ];

            foreach ($edges as $edge) {
                MemberRelationship::firstOrCreate(
                    [
                        'parent_member_id' => $edge['parent'],
                        'child_member_id' => $edge['child'],
                        'commission_model_id' => $model->id,
                    ],
                    [
                        'override_rate' => $edge['rate'],
                        'status' => 'active',
                        'effective_from' => now()->startOfYear(),
                    ]
                );
            }

            // 4. Create a Sample Sales Transaction originated by Member E
            $sale = SalesTransaction::firstOrCreate(
                ['transaction_code' => 'TXN-202610-0001'],
                [
                    'member_id' => $memberE->id,
                    'sale_amount' => 1000.00,
                    'transaction_date' => now(),
                    'status' => 'completed',
                    'reference_note' => 'Q4 Enterprise Client Activation',
                ]
            );

            // 5. Create an Atomic Calculation Run
            $run = CalculationRun::firstOrCreate(
                ['run_number' => 'RUN-202610-001'],
                [
                    'run_uuid' => (string) Str::uuid(),
                    'commission_model_id' => $model->id,
                    'period_start' => now()->startOfMonth(),
                    'period_end' => now()->endOfMonth(),
                    'status' => 'completed',
                    'generation_limit' => 5,
                    'total_sales_amount' => 1000.00,
                    'total_commission_payable' => 0.00, // will sum below
                    'total_beneficiaries_count' => 4,
                    'settings_snapshot' => $model->settings,
                    'started_at' => now()->subMinute(),
                    'completed_at' => now(),
                ]
            );

            // 6. Generate Historical Ledger Entries Walking Up the Hierarchy
            // E is seller.
            // Gen 1: D earns 2.00% of $1000 = $20.00
            // Gen 2: C earns 3.00% of $1000 = $30.00
            // Gen 3: B earns 4.00% of $1000 = $40.00
            // Gen 4: A earns 5.00% of $1000 = $50.00
            $payouts = [
                ['beneficiary_id' => $memberD->id, 'gen' => 1, 'rate' => 2.0000, 'amount' => 20.00],
                ['beneficiary_id' => $memberC->id, 'gen' => 2, 'rate' => 3.0000, 'amount' => 30.00],
                ['beneficiary_id' => $memberB->id, 'gen' => 3, 'rate' => 4.0000, 'amount' => 40.00],
                ['beneficiary_id' => $memberA->id, 'gen' => 4, 'rate' => 5.0000, 'amount' => 50.00],
            ];

            $totalPayable = 0.00;

            foreach ($payouts as $item) {
                CommissionLedgerEntry::firstOrCreate(
                    [
                        'calculation_run_id' => $run->id,
                        'beneficiary_member_id' => $item['beneficiary_id'],
                        'source_sale_id' => $sale->id,
                    ],
                    [
                        'commission_model_id' => $model->id,
                        'seller_member_id' => $memberE->id,
                        'generation' => $item['gen'],
                        'sale_amount' => $sale->sale_amount,
                        'rate_applied' => $item['rate'],
                        'commission_amount' => $item['amount'],
                        'is_eligible' => true,
                        'payout_status' => 'approved',
                    ]
                );

                $totalPayable += $item['amount'];
            }

            // Update run totals
            $run->update([
                'total_commission_payable' => $totalPayable,
                'summary_results' => [
                    'source_sales_count' => 1,
                    'total_sales_amount' => 1000.00,
                    'total_commission_payable' => $totalPayable,
                    'beneficiaries' => ['MEM-D', 'MEM-C', 'MEM-B', 'MEM-A'],
                ],
            ]);
        });
    }
}
