<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionLedgerEntry extends Model
{
    use HasFactory;

    protected $table = 'commission_ledger_entries';

    protected $fillable = [
        'calculation_run_id',
        'commission_model_id',
        'source_sale_id',
        'seller_member_id',
        'beneficiary_member_id',
        'generation',
        'sale_amount',
        'rate_applied',
        'commission_amount',
        'is_eligible',
        'payout_status',
        'ineligibility_reason',
    ];

    protected $casts = [
        'generation' => 'integer',
        'sale_amount' => 'decimal:2',
        'rate_applied' => 'decimal:4',
        'commission_amount' => 'decimal:2',
        'is_eligible' => 'boolean',
    ];

    /**
     * Parent calculation run.
     */
    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class, 'calculation_run_id');
    }

    /**
     * Commission model.
     */
    public function commissionModel(): BelongsTo
    {
        return $this->belongsTo(CommissionModel::class, 'commission_model_id');
    }

    /**
     * Source sale transaction.
     */
    public function sourceSale(): BelongsTo
    {
        return $this->belongsTo(SalesTransaction::class, 'source_sale_id');
    }

    /**
     * Originating seller.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'seller_member_id');
    }

    public function sellerMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'seller_member_id');
    }

    /**
     * Beneficiary receiving commission.
     */
    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'beneficiary_member_id');
    }

    public function beneficiaryMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'beneficiary_member_id');
    }

    /**
     * Scope for eligible payouts.
     */
    public function scopeEligible($query)
    {
        return $query->where('is_eligible', true);
    }
}
