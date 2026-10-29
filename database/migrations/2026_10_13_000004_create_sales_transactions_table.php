<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Records sales transactions with original sale amounts as commission basis.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sales_transactions')) {
            Schema::create('sales_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_code', 100)->unique()->comment('Reference code e.g. TXN-202610-0001');
                $table->foreignId('member_id')
                    ->constrained('members')
                    ->onDelete('restrict')
                    ->comment('Member who originated the sale');
                
                // Original sale amount - strictly DECIMAL(15,2)
                $table->decimal('sale_amount', 15, 2)->comment('Original transaction amount as calculation base');
                
                $table->timestamp('transaction_date')->useCurrent()->index();
                $table->string('status', 20)->default('completed')->index()->comment('pending, completed, refunded, voided');
                $table->text('reference_note')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['member_id', 'transaction_date'], 'idx_sales_member_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};
