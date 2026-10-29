<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the members table to manage network participants / distributors.
     */
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->id();
                $table->string('member_code', 50)->unique()->comment('Unique identifier e.g. MEM-001, A, B, E1');
                $table->string('name', 255)->index();
                $table->string('email', 255)->nullable()->unique();
                $table->string('phone', 50)->nullable();
                $table->string('status', 20)->default('active')->index()->comment('active, inactive, suspended');
                $table->timestamp('joined_at')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
