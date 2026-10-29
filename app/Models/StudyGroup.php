<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyGroup extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'creator_id',
        'name',
        'slug',
        'description',
        'is_private',
        'capacity',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'study_group_members', 'study_group_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function discussions()
    {
        return $this->morphMany(Discussion::class, 'discussable');
    }
}

