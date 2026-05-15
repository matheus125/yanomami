<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Encaminhamento extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data_envio' => 'datetime',
            'prazo_resposta' => 'date',
            'data_resposta' => 'datetime',
        ];
    }

    public function caso(): BelongsTo
    {
        return $this->belongsTo(Caso::class);
    }

    public function orgaoOrigem(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_origem_id');
    }

    public function orgaoDestino(): BelongsTo
    {
        return $this->belongsTo(Orgao::class, 'orgao_destino_id');
    }
}
