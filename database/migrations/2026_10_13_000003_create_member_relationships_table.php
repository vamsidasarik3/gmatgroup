<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Manages parent-child hierarchical edges with configurable override rates.
     */
    public function up(): void
    {
        if (! Schema::hasTable('member_relationships')) {
            Schema::create('member_relationships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_member_id')
                    ->constrained('members')
                    ->onDelete('restrict')
                    ->comment('Upline parent member receiving override commission');
                $table->foreignId('child_member_id')
                    ->constrained('members')
                    ->onDelete('restrict')
                    ->comment('Downline child member generating override commission');
                
                // Configurable commission rate on the relationship (percentage e.g. 5.0000 for 5%)
                $table->decimal('override_rate', 8, 4)
                    ->default(0.0000)
                    ->comment('Override rate in percent e.g. 5.0000 for 5%');

                $table->foreignId('commission_model_id')
                    ->nullable()
                    ->constrained('commission_models')
                    ->onDelete('set null')
                    ->comment('Optional commission model this specific edge belongs to');

                $table->string('status', 20)->default('active')->index()->comment('active, inactive, terminated');
                $table->date('effective_from')->nullable()->index();
                $table->date('effective_to')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                // Unique constraint: A parent-child relationship within a specific model scope cannot be duplicated
                $table->unique(
                    ['parent_member_id', 'child_member_id', 'commission_model_id'],
                    'uniq_member_rel_parent_child_model'
                );

                $table->index(['parent_member_id', 'status'], 'idx_parent_status');
                $table->index(['child_member_id', 'status'], 'idx_child_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_relationships');
    }
};
