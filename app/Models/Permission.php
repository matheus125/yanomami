<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'module',
        'name',
        'description',
    ];

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(AccessProfile::class, 'access_profile_permission')->withTimestamps();
    }
}
