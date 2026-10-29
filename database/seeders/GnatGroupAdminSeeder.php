<?php

namespace Database\Seeders;

use App\Models\CalculationRun;
use App\Models\CommissionLedgerEntry;
use App\Models\CommissionModel;
use App\Models\Member;
use App\Models\MemberRelationship;
use App\Models\SalesTransaction;
use App\Models\User;
use App\Services\HierarchyDataProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GnatGroupAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Seed Default Administrator User
            User::firstOrCreate(
                ['email' => 'admin@gmatgroup.com'],
                [
                    'name' => 'System Administrator',
                    'password' => Hash::make('admin123'),
                    'email_verified_at' => now(),
                ]
            );
            User::firstOrCreate(
                ['email' => 'admin@gnatgroup.com'],
                [
                    'name' => 'System Administrator',
                    'password' => Hash::make('admin123'),
                    'email_verified_at' => now(),
                ]
            );
            User::firstOrCreate(
                ['email' => 'member@gmatgroup.com'],
                [
                    'name' => 'Demo Member',
                    'role' => 'member',
                    'password' => Hash::make('member123'),
                    'email_verified_at' => now(),
                ]
            );

            // 2. Seed / Ensure Commission Models
            $overrideModel = CommissionModel::firstOrCreate(
                ['name' => 'Level/Generation Override Commission Model'],
                [
                    'description' => 'Calculates generational overrides using relationship rates up to a configurable ceiling.',
                    'model_type' => 'level_override',
                    'status' => 'active',
                    'commission_rate' => 5.0000,
                    'number_of_levels' => 7,
                    'max_generations' => 5,
                    'settings' => [
                        'currency' => 'USD',
                        'payout_threshold' => 10.00,
                        'max_generations' => 5,
                        'rate_convention' => 'percentage',
                        'rounding_mode' => 'ROUND_HALF_UP',
                    ],
                ]
            );

            $weakestLinkModel = CommissionModel::firstOrCreate(
                ['name' => 'Weakest Link Commission Model'],
                [
                    'description' => 'Evaluates parallel team legs and bases leader rewards on the minimum child branch volume.',
                    'model_type' => 'weakest_link',
                    'status' => 'active',
                    'commission_rate' => 6.0000,
                    'number_of_levels' => 7,
                    'max_generations' => 5,
                    'settings' => [
                        'currency' => 'USD',
                        'minimum_leg_count' => 2,
                        'rate_convention' => 'percentage',
                        'rounding_mode' => 'ROUND_HALF_UP',
                    ],
                ]
            );

            // 3. Seed Hierarchy Members & Relationships from HierarchyDataProvider
            $provider = new HierarchyDataProvider();
            $data = $provider->getHierarchyData();

            $memberMap = [];
            foreach ($data['members'] as $m) {
                $member = Member::updateOrCreate(
                    ['member_code' => $m['member_code']],
                    [
                        'name' => $m['name'],
                        'email' => $m['email'],
                        'phone' => $m['phone'],
                        'status' => $m['status'],
                        'joined_at' => $m['joined_date'],
                    ]
                );
                $memberMap[$m['id']] = $member;
            }

            // Seed Relationships
            foreach ($data['members'] as $m) {
                if ($m['parent_id'] !== null && isset($memberMap[$m['parent_id']])) {
                    MemberRelationship::updateOrCreate(
                        [
                            'parent_member_id' => $memberMap[$m['parent_id']]->id,
                            'child_member_id' => $memberMap[$m['id']]->id,
                            'commission_model_id' => $overrideModel->id,
                        ],
                        [
                            'override_rate' => $m['override_rate'],
                            'status' => 'active',
                            'effective_from' => $m['joined_date'],
                        ]
                    );
                }
            }

            // 4. Seed Sales Transactions
            $salesMap = [];
            foreach ($data['sales'] as $s) {
                $sellerMember = $memberMap[$s['seller_id']] ?? null;
                if (! $sellerMember) continue;

                $sale = SalesTransaction::updateOrCreate(
                    ['transaction_code' => $s['transaction_code']],
                    [
                        'member_id' => $sellerMember->id,
                        'sale_amount' => $s['sale_amount'],
                        'transaction_date' => $s['transaction_date'],
                        'status' => 'completed',
                        'reference_note' => $s['description'],
                    ]
                );
                $salesMap[$s['id']] = $sale;
            }

            // 5. Seed Initial Calculation Run & Ledger Entries
            $run = CalculationRun::firstOrCreate(
                ['run_number' => 'RUN-202610-001'],
                [
                    'run_uuid' => (string) Str::uuid(),
                    'commission_model_id' => $overrideModel->id,
                    'period_start' => '2026-10-01',
                    'period_end' => '2026-10-31',
                    'status' => 'completed',
                    'generation_limit' => 5,
                    'total_sales_amount' => array_sum(array_column($data['sales'], 'sale_amount')),
                    'total_commission_payable' => 0.00,
                    'total_beneficiaries_count' => 0,
                    'settings_snapshot' => $overrideModel->settings,
                    'summary_results' => [],
                    'started_at' => now()->subHours(2),
                    'completed_at' => now()->subHours(2)->addSeconds(4),
                ]
            );

            // Compute ledger entries for Sale 101 and Sale 107
            $salesToProcess = [101, 107];
            $totalCommission = 0.00;
            $beneficiaries = [];

            // Parent mapping for traversal
            $parentLookup = [];
            foreach ($data['members'] as $m) {
                if ($m['parent_id'] !== null) {
                    $parentLookup[$m['id']] = [
                        'parent_id' => $m['parent_id'],
                        'rate' => (float) $m['override_rate'],
                    ];
                }
            }

            foreach ($salesToProcess as $saleId) {
                $sObj = $salesMap[$saleId] ?? null;
                if (! $sObj) continue;

                $currProviderId = $saleId === 101 ? 21 : 25;
                $gen = 1;
                while (isset($parentLookup[$currProviderId])) {
                    $edge = $parentLookup[$currProviderId];
                    $pProviderId = $edge['parent_id'];
                    $pMember = $memberMap[$pProviderId] ?? null;
                    if (! $pMember) break;

                    $pId = $pMember->id;
                    $rate = $edge['rate'];
                    $isEligible = ($gen <= 5);
                    $commAmount = $isEligible ? round($sObj->sale_amount * ($rate / 100.0), 2) : 0.00;

                    CommissionLedgerEntry::updateOrCreate(
                        [
                            'calculation_run_id' => $run->id,
                            'beneficiary_member_id' => $pId,
                            'source_sale_id' => $sObj->id,
                        ],
                        [
                            'commission_model_id' => $overrideModel->id,
                            'seller_member_id' => $sObj->member_id,
                            'generation' => $gen,
                            'sale_amount' => $sObj->sale_amount,
                            'rate_applied' => $rate,
                            'commission_amount' => $commAmount,
                            'is_eligible' => $isEligible,
                            'payout_status' => $isEligible ? 'approved' : 'voided',
                            'ineligibility_reason' => $isEligible ? null : 'Exceeded generation depth limit (5)',
                        ]
                    );

                    if ($isEligible) {
                        $totalCommission += $commAmount;
                        $beneficiaries[$pId] = true;
                    }

                    $currProviderId = $pProviderId;
                    $gen++;
                }
            }

            $run->update([
                'total_commission_payable' => $totalCommission,
                'total_beneficiaries_count' => count($beneficiaries),
                'summary_results' => [
                    'sales_processed' => count($salesToProcess),
                    'total_sales_amount' => array_sum(array_column($data['sales'], 'sale_amount')),
                    'total_commission_payable' => $totalCommission,
                    'beneficiary_ids' => array_keys($beneficiaries),
                ],
            ]);
        });
    }
}
