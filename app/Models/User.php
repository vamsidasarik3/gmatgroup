<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'email',
        'role',
        'password',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor'], true);
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function studyProfile()
    {
        return $this->hasOne(StudyProfile::class, 'user_id');
    }

    public function studyGroups()
    {
        return $this->belongsToMany(StudyGroup::class, 'study_group_members', 'user_id', 'study_group_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class, 'user_id');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'user_id');
    }
}
