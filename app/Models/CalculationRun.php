<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalculationRun extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'calculation_runs';

    protected $fillable = [
        'run_uuid',
        'run_number',
        'commission_model_id',
        'period_start',
        'period_end',
        'status',
        'generation_limit',
        'total_sales_amount',
        'total_commission_payable',
        'total_beneficiaries_count',
        'settings_snapshot',
        'summary_results',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generation_limit' => 'integer',
        'total_sales_amount' => 'decimal:2',
        'total_commission_payable' => 'decimal:2',
        'total_beneficiaries_count' => 'integer',
        'settings_snapshot' => 'array',
        'summary_results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The model configuration used for this calculation run.
     */
    public function commissionModel(): BelongsTo
    {
        return $this->belongsTo(CommissionModel::class, 'commission_model_id');
    }

    /**
     * Ledger entries generated within this calculation run.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CommissionLedgerEntry::class, 'calculation_run_id');
    }

    /**
     * Helper to check if calculation has completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
