<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensures commission_models table exists with calculation settings,
     * lifecycle status, and soft deletion capability.
     */
    public function up(): void
    {
        if (! Schema::hasTable('commission_models')) {
            Schema::create('commission_models', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('model_type', 50)->default('level_override');
                $table->string('status', 20)->default('active')->index();
                $table->json('calculation_results')->nullable();
                $table->json('settings')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
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
        Schema::dropIfExists('commission_models');
    }
};
