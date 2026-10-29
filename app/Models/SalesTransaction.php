<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_transactions';

    protected $fillable = [
        'transaction_code',
        'member_id',
        'sale_amount',
        'transaction_date',
        'status',
        'reference_note',
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    /**
     * The member who made the sale.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Ledger entries generated from this sale transaction.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CommissionLedgerEntry::class, 'source_sale_id');
    }

    /**
     * Alias for commission ledger entries.
     */
    public function commissionLedgerEntries(): HasMany
    {
        return $this->hasMany(CommissionLedgerEntry::class, 'source_sale_id');
    }

    /**
     * Scope for completed transactions eligible for commission calculation.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
