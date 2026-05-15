<?php

namespace Database\Seeders;

use App\Models\AccessProfile;
use App\Models\Caso;
use App\Models\Comunidade;
use App\Models\Municipio;
use App\Models\Orgao;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AccessProfileSeeder::class);

        $municipio = Municipio::updateOrCreate(
            ['nome_municipio' => 'Sao Gabriel da Cachoeira'],
            ['estado' => 'AM', 'codigo_ibge' => '1303809'],
        );

        Municipio::updateOrCreate(
            ['nome_municipio' => 'Santa Isabel do Rio Negro'],
            ['estado' => 'AM', 'codigo_ibge' => '1303601'],
        );

        Municipio::updateOrCreate(
            ['nome_municipio' => 'Barcelos'],
            ['estado' => 'AM', 'codigo_ibge' => '1300409'],
        );

        $comunidade = Comunidade::updateOrCreate(
            ['nome_comunidade' => 'Comunidade Yanomami Piloto'],
            [
                'territorio' => 'Terra Indigena Yanomami',
                'municipio_id' => $municipio->id,
                'polo_base' => 'Polo Base Piloto',
            ],
        );

        $orgao = Orgao::updateOrCreate(
            ['nome_orgao' => 'Coordenacao Estadual SIAY'],
            [
                'tipo_orgao' => 'SEMAS',
                'esfera' => 'Estadual',
                'municipio' => $municipio->nome_municipio,
                'responsavel' => 'Administrador do Sistema',
                'contato' => 'admin@siay.test',
                'ativo' => true,
            ],
        );

        Orgao::updateOrCreate(
            ['nome_orgao' => 'CRAS Municipal Piloto'],
            [
                'tipo_orgao' => 'CRAS',
                'esfera' => 'Municipal',
                'municipio' => $municipio->nome_municipio,
                'ativo' => true,
            ],
        );

        $adminProfile = AccessProfile::where('slug', 'administrador-estadual')->firstOrFail();

        $admin = User::updateOrCreate(
            ['email' => 'admin@siay.test'],
            [
                'name' => 'Administrador SIAY',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'phone' => '(92) 0000-0000',
                'position' => 'Administrador Estadual',
                'orgao_id' => $orgao->id,
                'access_profile_id' => $adminProfile->id,
                'municipio' => $municipio->nome_municipio,
                'active' => true,
            ],
        );

        $person = Pessoa::updateOrCreate(
            ['nome_civil' => 'Pessoa Piloto'],
            [
                'nome_tradicional' => 'Nome tradicional piloto',
                'sexo' => 'Nao informado',
                'idade_aproximada' => 12,
                'povo_indigena' => 'Yanomami',
                'situacao_documental' => 'Documentacao incompleta',
            ],
        );

        $case = Caso::updateOrCreate(
            ['numero_caso' => 'AM-SGC-'.now()->year.'-000001'],
            [
                'tipo_caso' => 'Individual',
                'descricao_resumida' => 'Caso piloto para validacao inicial do fluxo intersetorial.',
                'data_abertura' => now(),
                'status_caso' => 'Aberto',
                'grau_risco' => 'Alto',
                'orgao_responsavel_id' => $orgao->id,
                'usuario_responsavel_id' => $admin->id,
                'municipio_id' => $municipio->id,
                'comunidade_id' => $comunidade->id,
                'data_ultima_atualizacao' => now(),
                'sigiloso' => false,
            ],
        );

        $case->pessoas()->syncWithoutDetaching([
            $person->id => ['papel_no_caso' => 'Pessoa acompanhada'],
        ]);
    }
}
