<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Orgao extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'orgaos';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function casos(): HasMany
    {
        return $this->hasMany(Caso::class, 'orgao_responsavel_id');
    }
}
