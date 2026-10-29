<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Manages calculation runs and preserves immutable historical calculation headers.
     */
    public function up(): void
    {
        if (! Schema::hasTable('calculation_runs')) {
            Schema::create('calculation_runs', function (Blueprint $table) {
                $table->id();
                $table->uuid('run_uuid')->unique()->comment('Globally unique calculation run identifier');
                $table->string('run_number', 50)->index()->comment('Human readable identifier e.g. RUN-202610-001');
                $table->foreignId('commission_model_id')
                    ->constrained('commission_models')
                    ->onDelete('restrict')
                    ->comment('Model definition executed');
                
                $table->date('period_start')->nullable()->index();
                $table->date('period_end')->nullable()->index();
                
                // Status tracking
                $table->string('status', 20)->default('draft')->index()
                    ->comment('draft, queued, processing, completed, failed, cancelled');
                
                // Max generation limit applied for this run
                $table->unsignedInteger('generation_limit')->default(5)->comment('Max generation depth applied');
                
                // Financial totals (strictly DECIMAL)
                $table->decimal('total_sales_amount', 15, 2)->default(0.00);
                $table->decimal('total_commission_payable', 15, 2)->default(0.00);
                $table->unsignedInteger('total_beneficiaries_count')->default(0);
                
                // Immutable snapshots of settings & summary at execution time
                $table->json('settings_snapshot')->nullable()->comment('Exact rules, rates, and parameters at runtime');
                $table->json('summary_results')->nullable()->comment('Aggregated branch metrics and results');
                $table->text('error_message')->nullable()->comment('Failure reason if calculation failed');
                
                // Audit timestamps
                $table->timestamp('started_at')->nullable()->index();
                $table->timestamp('completed_at')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['commission_model_id', 'status', 'created_at'], 'idx_calc_runs_model_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculation_runs');
    }
};
