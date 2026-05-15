<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Caso extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'casos';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data_abertura' => 'datetime',
            'data_ultima_atualizacao' => 'datetime',
            'sigiloso' => 'boolean',
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->hasPermission('cases.view_sensitive')) {
            $query->where(function (Builder $query) use ($user): void {
                $query->where('sigiloso', false)
                    ->orWhere('usuario_responsavel_id', $user->id);
            });
        }

        if ($user->hasPermission('cases.view_all')) {
            return $query;
        }

        if ($user->hasPermission('cases.view_municipality')) {
            return $query->whereHas('municipio', function (Builder $query) use ($user): void {
                $query->where('nome_municipio', $user->municipio);
            });
        }

        if ($user->hasPermission('cases.view_orgao') && $user->orgao_id) {
            return $query->where('orgao_responsavel_id', $user->orgao_id);
        }

        return $query->where('usuario_responsavel_id', $user->id);
    }

    public function orgaoResponsavel(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_responsavel_id');
    }

    public function usuarioResponsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_responsavel_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function comunidade(): BelongsTo
    {
        return $this->belongsTo(Comunidade::class);
    }

    public function pessoas(): BelongsToMany
    {
        return $this->belongsToMany(Pessoa::class, 'caso_pessoa')->withPivot('papel_no_caso')->withTimestamps();
    }

    public function atendimentos(): HasMany
    {
        return $this->hasMany(Atendimento::class);
    }

    public function encaminhamentos(): HasMany
    {
        return $this->hasMany(Encaminhamento::class);
    }

    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class);
    }

    public function acolhimento(): HasOne
    {
        return $this->hasOne(CasaTransito::class);
    }
}
