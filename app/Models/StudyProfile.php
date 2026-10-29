<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyProfile extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'target_score',
        'target_date',
        'gmat_stage',
        'weaknesses',
        'strengths',
        'weekly_study_goal_hours',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'weaknesses' => 'array',
            'strengths' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

