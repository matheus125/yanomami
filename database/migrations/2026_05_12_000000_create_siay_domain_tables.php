<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('module')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('access_profile_permission', function (Blueprint $table) {
            $table->uuid('access_profile_id');
            $table->uuid('permission_id');
            $table->timestamps();

            $table->primary(['access_profile_id', 'permission_id']);
            $table->foreign('access_profile_id')->references('id')->on('access_profiles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('orgaos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome_orgao');
            $table->string('tipo_orgao')->nullable();
            $table->string('esfera')->nullable();
            $table->string('municipio')->nullable()->index();
            $table->string('responsavel')->nullable();
            $table->string('contato')->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('municipios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome_municipio')->index();
            $table->string('estado', 2)->default('AM');
            $table->string('codigo_ibge')->nullable();
            $table->timestamps();
        });

        Schema::create('comunidades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome_comunidade')->index();
            $table->string('territorio')->nullable()->index();
            $table->uuid('municipio_id')->nullable()->index();
            $table->string('polo_base')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('municipio_id')->references('id')->on('municipios')->nullOnDelete();
        });

        Schema::create('familias', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('referencia_familiar');
            $table->uuid('comunidade_id')->nullable()->index();
            $table->unsignedInteger('quantidade_membros')->default(0);
            $table->string('territorio')->nullable()->index();
            $table->text('situacao_vulnerabilidade')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('comunidade_id')->references('id')->on('comunidades')->nullOnDelete();
        });

        Schema::create('pessoas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome_tradicional')->nullable()->index();
            $table->string('nome_civil')->nullable()->index();
            $table->string('sexo')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->unsignedInteger('idade_aproximada')->nullable();
            $table->string('povo_indigena')->nullable()->default('Yanomami');
            $table->string('cpf')->nullable()->index();
            $table->string('rani')->nullable()->index();
            $table->string('nis')->nullable()->index();
            $table->string('situacao_documental')->nullable();
            $table->uuid('familia_id')->nullable()->index();
            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->foreign('familia_id')->references('id')->on('familias')->nullOnDelete();
        });

        Schema::create('casos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_caso')->unique();
            $table->string('tipo_caso');
            $table->text('descricao_resumida');
            $table->timestamp('data_abertura')->nullable();
            $table->string('status_caso')->default('Aberto')->index();
            $table->string('grau_risco')->default('Medio')->index();
            $table->uuid('orgao_responsavel_id')->index();
            $table->uuid('usuario_responsavel_id')->index();
            $table->uuid('municipio_id')->index();
            $table->uuid('comunidade_id')->nullable()->index();
            $table->timestamp('data_ultima_atualizacao')->nullable()->index();
            $table->boolean('sigiloso')->default(false)->index();
            $table->string('motivo_encerramento')->nullable();
            $table->text('justificativa_encerramento')->nullable();
            $table->timestamps();

            $table->foreign('orgao_responsavel_id')->references('id')->on('orgaos')->restrictOnDelete();
            $table->foreign('usuario_responsavel_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('municipio_id')->references('id')->on('municipios')->restrictOnDelete();
            $table->foreign('comunidade_id')->references('id')->on('comunidades')->nullOnDelete();
        });

        Schema::create('caso_pessoa', function (Blueprint $table) {
            $table->uuid('caso_id')->index();
            $table->uuid('pessoa_id')->index();
            $table->string('papel_no_caso')->nullable();
            $table->timestamps();

            $table->primary(['caso_id', 'pessoa_id']);
            $table->foreign('caso_id')->references('id')->on('casos')->cascadeOnDelete();
            $table->foreign('pessoa_id')->references('id')->on('pessoas')->cascadeOnDelete();
        });

        Schema::create('atendimentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('caso_id')->index();
            $table->uuid('pessoa_id')->nullable()->index();
            $table->uuid('orgao_id')->index();
            $table->uuid('usuario_id')->index();
            $table->string('tipo_atendimento')->index();
            $table->text('descricao');
            $table->timestamp('data_atendimento')->nullable();
            $table->text('proximo_passo')->nullable();
            $table->uuid('corrigido_por_id')->nullable()->index();
            $table->text('correcao_justificativa')->nullable();
            $table->timestamps();

            $table->foreign('caso_id')->references('id')->on('casos')->restrictOnDelete();
            $table->foreign('pessoa_id')->references('id')->on('pessoas')->nullOnDelete();
            $table->foreign('orgao_id')->references('id')->on('orgaos')->restrictOnDelete();
            $table->foreign('usuario_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('corrigido_por_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('encaminhamentos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('caso_id')->index();
            $table->uuid('orgao_origem_id')->index();
            $table->uuid('orgao_destino_id')->index();
            $table->text('motivo');
            $table->string('status_encaminhamento')->default('Enviado')->index();
            $table->timestamp('data_envio')->nullable();
            $table->date('prazo_resposta')->nullable()->index();
            $table->text('resposta')->nullable();
            $table->timestamp('data_resposta')->nullable();
            $table->timestamps();

            $table->foreign('caso_id')->references('id')->on('casos')->restrictOnDelete();
            $table->foreign('orgao_origem_id')->references('id')->on('orgaos')->restrictOnDelete();
            $table->foreign('orgao_destino_id')->references('id')->on('orgaos')->restrictOnDelete();
        });

        Schema::create('alertas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('caso_id')->index();
            $table->string('tipo_alerta')->index();
            $table->string('nivel_alerta')->default('Medio')->index();
            $table->text('descricao')->nullable();
            $table->string('status_alerta')->default('Aberto')->index();
            $table->timestamp('data_geracao')->nullable();
            $table->timestamps();

            $table->foreign('caso_id')->references('id')->on('casos')->cascadeOnDelete();
        });

        Schema::create('casa_transito', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('caso_id')->index();
            $table->timestamp('data_entrada')->nullable();
            $table->timestamp('data_saida')->nullable();
            $table->text('composicao_familiar')->nullable();
            $table->text('motivo_acolhimento')->nullable();
            $table->text('alimentacao')->nullable();
            $table->text('transporte')->nullable();
            $table->text('saude')->nullable();
            $table->text('retorno_territorio')->nullable();
            $table->text('situacao_saida')->nullable();
            $table->timestamps();

            $table->foreign('caso_id')->references('id')->on('casos')->cascadeOnDelete();
        });

        Schema::create('anexos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('caso_id')->index();
            $table->string('nome_arquivo');
            $table->string('tipo_arquivo')->nullable();
            $table->text('url_arquivo');
            $table->uuid('enviado_por_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('caso_id')->references('id')->on('casos')->cascadeOnDelete();
            $table->foreign('enviado_por_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('table_name')->nullable()->index();
            $table->string('action')->index();
            $table->uuid('record_id')->nullable()->index();
            $table->string('subject_type')->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('exception_class')->nullable();
            $table->text('message');
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->text('trace_summary')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('anexos');
        Schema::dropIfExists('casa_transito');
        Schema::dropIfExists('alertas');
        Schema::dropIfExists('encaminhamentos');
        Schema::dropIfExists('atendimentos');
        Schema::dropIfExists('caso_pessoa');
        Schema::dropIfExists('casos');
        Schema::dropIfExists('pessoas');
        Schema::dropIfExists('familias');
        Schema::dropIfExists('comunidades');
        Schema::dropIfExists('municipios');
        Schema::dropIfExists('orgaos');
        Schema::dropIfExists('access_profile_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('access_profiles');
    }
};
