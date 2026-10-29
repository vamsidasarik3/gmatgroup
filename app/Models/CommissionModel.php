<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'commission_models';

    protected $fillable = [
        'name',
        'description',
        'model_type',
        'status',
        'commission_rate',
        'number_of_levels',
        'max_generations',
        'total_sales',
        'total_potential_commission',
        'final_commission',
        'calculation_results',
        'settings',
        'weakest_person',
        'weakest_sales',
        'weakest_commission',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:4',
        'total_sales' => 'decimal:2',
        'total_potential_commission' => 'decimal:2',
        'final_commission' => 'decimal:2',
        'weakest_sales' => 'decimal:2',
        'weakest_commission' => 'decimal:2',
        'calculation_results' => 'array',
        'settings' => 'array',
    ];

    /**
     * Historical calculation runs executed for this model.
     */
    public function calculationRuns(): HasMany
    {
        return $this->hasMany(CalculationRun::class, 'commission_model_id');
    }

    /**
     * Itemized commission ledger entries generated under this model.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CommissionLedgerEntry::class, 'commission_model_id');
    }

    /**
     * Network relationships configured specifically for this model.
     */
    public function memberRelationships(): HasMany
    {
        return $this->hasMany(MemberRelationship::class, 'commission_model_id');
    }

    /**
     * Level definitions (Model 1 Weakest Link).
     */
    public function levels(): HasMany
    {
        return $this->hasMany(CommissionLevel::class, 'commission_model_id');
    }

    /**
     * Network nodes (Model 2 Generation Override).
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(ModelNode::class, 'commission_model_id');
    }

    /**
     * Network edges (Model 2 Generation Override).
     */
    public function edges(): HasMany
    {
        return $this->hasMany(ModelEdge::class, 'commission_model_id');
    }
}
