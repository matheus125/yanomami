<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_questionario_inseguranca_alimentar', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('familia_id')->unique();
            $table->string('refeicoes_semana');
            $table->string('refeicoes_dia');
            $table->string('preocupacao_falta_comida');
            $table->string('reduziu_porcoes');
            $table->json('fatores_limitantes')->nullable();
            $table->text('fatores_limitantes_outros')->nullable();
            $table->json('programas_assistencia')->nullable();
            $table->text('programas_assistencia_outros')->nullable();
            $table->json('locais_compra')->nullable();
            $table->text('locais_compra_outros')->nullable();
            $table->string('alimentos_acessiveis');
            $table->string('frequencia_alimentos_baixo_custo');
            $table->json('alimentos_desejados')->nullable();
            $table->text('alimentos_desejados_outros')->nullable();
            $table->string('situacao_financeira_afeta');
            $table->string('participa_associacao');
            $table->text('associacao_qual')->nullable();
            $table->unsignedInteger('pontuacao_total')->default(0);
            $table->string('diagnostico');
            $table->text('observacoes_diagnostico');
            $table->timestamps();

            $table->foreign('familia_id')->references('id')->on('familias')->cascadeOnDelete();
            $table->index('diagnostico');
            $table->index('pontuacao_total');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_questionario_inseguranca_alimentar');
    }
};
