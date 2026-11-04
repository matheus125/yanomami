<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionarioInsegurancaAlimentar extends Model
{
    use HasUuids;

    public const DIAGNOSTICO_LEVE = 'Inseguranca Alimentar Leve';

    public const DIAGNOSTICO_MODERADA = 'Inseguranca Alimentar Moderada';

    public const DIAGNOSTICO_GRAVE = 'Inseguranca Alimentar Grave';

    protected $table = 'tb_questionario_inseguranca_alimentar';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'fatores_limitantes' => 'array',
        'programas_assistencia' => 'array',
        'locais_compra' => 'array',
        'alimentos_desejados' => 'array',
        'pontuacao_total' => 'integer',
    ];

    public function familia(): BelongsTo
    {
        return $this->belongsTo(Familia::class);
    }
}
