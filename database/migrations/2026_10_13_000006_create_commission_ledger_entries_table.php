<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates commission_ledger_entries to record granular line-item commissions.
     * Prevents duplicate payouts via unique constraint on (calculation_run_id, beneficiary_member_id, source_sale_id).
     */
    public function up(): void
    {
        if (! Schema::hasTable('commission_ledger_entries')) {
            Schema::create('commission_ledger_entries', function (Blueprint $table) {
                $table->id();
                
                $table->foreignId('calculation_run_id')
                    ->constrained('calculation_runs')
                    ->onDelete('restrict')
                    ->comment('Reference to immutable calculation execution');

                $table->foreignId('commission_model_id')
                    ->constrained('commission_models')
                    ->onDelete('restrict')
                    ->comment('Model configuration used');

                $table->foreignId('source_sale_id')
                    ->constrained('sales_transactions')
                    ->onDelete('restrict')
                    ->comment('Original sales transaction triggering commission');

                $table->foreignId('seller_member_id')
                    ->constrained('members')
                    ->onDelete('restrict')
                    ->comment('Originator of the sale');

                $table->foreignId('beneficiary_member_id')
                    ->constrained('members')
                    ->onDelete('restrict')
                    ->comment('Upline earner receiving commission');

                // Generation depth: 0 = Direct salesperson, 1 = Direct parent, 2 = 2nd generation upline, etc.
                $table->unsignedInteger('generation')->comment('Generational distance from seller (1=parent, 2=grandparent)');

                // Financial values - DECIMAL(15,2) and rates DECIMAL(8,4)
                $table->decimal('sale_amount', 15, 2)->comment('Original sale base amount');
                $table->decimal('rate_applied', 8, 4)->comment('Commission rate percentage applied e.g. 5.0000 for 5%');
                $table->decimal('commission_amount', 15, 2)->comment('Calculated commission payout amount');

                // Eligibility and status
                $table->boolean('is_eligible')->default(true)->comment('False if excluded by generation ceiling or member inactivity');
                $table->string('payout_status', 20)->default('calculated')->index()
                    ->comment('calculated, approved, paid, held, voided');
                $table->string('ineligibility_reason', 255)->nullable()->comment('Audit reason if not eligible e.g. Exceeded max generations');
                
                $table->timestamps();

                // Requirement: Prevent duplicate ledger entries for the same beneficiary, source sale and calculation run
                $table->unique(
                    ['calculation_run_id', 'beneficiary_member_id', 'source_sale_id'],
                    'uniq_run_beneficiary_sale'
                );

                // Supporting query and reporting indexes
                $table->index(['calculation_run_id', 'payout_status'], 'idx_run_payout_status');
                $table->index(['beneficiary_member_id', 'payout_status'], 'idx_beneficiary_status');
                $table->index(['seller_member_id', 'generation'], 'idx_seller_generation');
                $table->index(['source_sale_id'], 'idx_source_sale');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commission_ledger_entries');
    }
};
