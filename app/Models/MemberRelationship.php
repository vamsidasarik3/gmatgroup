<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberRelationship extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'member_relationships';

    protected $fillable = [
        'parent_member_id',
        'child_member_id',
        'override_rate',
        'commission_model_id',
        'status',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'override_rate' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    /**
     * Parent (upline) member.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'parent_member_id');
    }

    public function parentMember(): BelongsTo
    {
        return $this->parent();
    }

    /**
     * Child (downline) member.
     */
    public function child(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'child_member_id');
    }

    public function childMember(): BelongsTo
    {
        return $this->child();
    }

    /**
     * Optional associated commission model.
     */
    public function commissionModel(): BelongsTo
    {
        return $this->belongsTo(CommissionModel::class, 'commission_model_id');
    }

    /**
     * Scope for active relationships.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
