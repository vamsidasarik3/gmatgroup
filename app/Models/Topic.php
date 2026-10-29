<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use HasUuids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'parent_id',
        'name',
        'slug',
        'description',
        'icon',
        'position',
        'meta_title',
        'meta_description',
    ];

    public function parent()
    {
        return $this->belongsTo(Topic::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Topic::class, 'parent_id')->orderBy('position');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'topic_id');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id')->orderBy('position');
    }
}

