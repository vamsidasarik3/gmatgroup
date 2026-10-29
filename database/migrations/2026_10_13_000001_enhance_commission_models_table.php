<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enhances the existing commission_models table with calculation settings,
     * lifecycle status, and soft deletion capability while preserving all historical data.
     */
    public function up(): void
    {
        if (Schema::hasTable('commission_models')) {
            Schema::table('commission_models', function (Blueprint $table) {
                if (! Schema::hasColumn('commission_models', 'status')) {
                    $table->string('status', 20)->default('active')->after('model_type')->index();
                }
                if (! Schema::hasColumn('commission_models', 'settings')) {
                    $table->json('settings')->nullable()->after('calculation_results');
                }
                if (! Schema::hasColumn('commission_models', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('commission_models')) {
            Schema::table('commission_models', function (Blueprint $table) {
                if (Schema::hasColumn('commission_models', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn('commission_models', 'settings')) {
                    $table->dropColumn('settings');
                }
                if (Schema::hasColumn('commission_models', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
