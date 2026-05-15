<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\Alerta;
use App\Models\Anexo;
use App\Models\Atendimento;
use App\Models\CasaTransito;
use App\Models\Caso;
use App\Models\Comunidade;
use App\Models\Encaminhamento;
use App\Models\Familia;
use App\Models\Municipio;
use App\Models\Orgao;
use App\Models\Pessoa;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiayCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@siay.test')->firstOrFail();
    }

    public function test_admin_can_create_update_and_deactivate_users(): void
    {
        $profile = AccessProfile::where('slug', 'tecnico-cras')->firstOrFail();
        $orgao = Orgao::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'Tecnico Teste',
                'email' => 'tecnico@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'phone' => '(92) 99999-9999',
                'position' => 'Tecnico',
                'orgao_id' => $orgao->id,
                'access_profile_id' => $profile->id,
                'municipio' => 'Sao Gabriel da Cachoeira',
                'active' => true,
            ])
            ->assertRedirect();

        $user = User::where('email', 'tecnico@example.test')->firstOrFail();
        $this->assertSame('Tecnico Teste', $user->name);

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => 'Tecnico Atualizado',
                'email' => 'tecnico@example.test',
                'password' => null,
                'password_confirmation' => null,
                'phone' => '(92) 98888-8888',
                'position' => 'Coordenador',
                'orgao_id' => $orgao->id,
                'access_profile_id' => $profile->id,
                'municipio' => 'Barcelos',
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Tecnico Atualizado',
            'municipio' => 'Barcelos',
            'active' => true,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $user))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'active' => false,
        ]);
    }

    public function test_admin_can_create_and_update_cases(): void
    {
        $municipio = Municipio::firstOrFail();
        $comunidade = Comunidade::firstOrFail();
        $orgao = Orgao::firstOrFail();
        $pessoa = Pessoa::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('cases.store'), [
                'tipo_caso' => 'Individual',
                'descricao_resumida' => 'Caso criado pelo teste automatizado.',
                'status_caso' => 'Aberto',
                'grau_risco' => 'Medio',
                'orgao_responsavel_id' => $orgao->id,
                'usuario_responsavel_id' => $this->admin->id,
                'municipio_id' => $municipio->id,
                'comunidade_id' => $comunidade->id,
                'sigiloso' => false,
                'pessoas' => [
                    ['id' => $pessoa->id, 'papel_no_caso' => 'Pessoa acompanhada'],
                ],
            ])
            ->assertRedirect();

        $case = Caso::where('descricao_resumida', 'Caso criado pelo teste automatizado.')->firstOrFail();

        $this->assertDatabaseHas('caso_pessoa', [
            'caso_id' => $case->id,
            'pessoa_id' => $pessoa->id,
            'papel_no_caso' => 'Pessoa acompanhada',
        ]);

        $this->actingAs($this->admin)
            ->put(route('cases.update', $case), [
                'tipo_caso' => 'Individual',
                'descricao_resumida' => 'Caso atualizado pelo teste automatizado.',
                'status_caso' => 'Em acompanhamento',
                'grau_risco' => 'Alto',
                'orgao_responsavel_id' => $orgao->id,
                'usuario_responsavel_id' => $this->admin->id,
                'municipio_id' => $municipio->id,
                'comunidade_id' => $comunidade->id,
                'sigiloso' => false,
                'pessoas' => [
                    ['id' => $pessoa->id, 'papel_no_caso' => 'Referenciado'],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('casos', [
            'id' => $case->id,
            'descricao_resumida' => 'Caso atualizado pelo teste automatizado.',
            'status_caso' => 'Em acompanhamento',
        ]);
    }

    public function test_admin_can_create_and_update_generic_resources(): void
    {
        $this->actingAs($this->admin)
            ->post(route('resources.store', 'municipios'), [
                'nome_municipio' => 'Teste Municipio',
                'estado' => 'AM',
                'codigo_ibge' => '9999999',
            ])
            ->assertRedirect();

        $municipio = Municipio::where('nome_municipio', 'Teste Municipio')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('resources.update', ['municipios', $municipio]), [
                'nome_municipio' => 'Teste Municipio Atualizado',
                'estado' => 'AM',
                'codigo_ibge' => '8888888',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('municipios', [
            'id' => $municipio->id,
            'nome_municipio' => 'Teste Municipio Atualizado',
            'codigo_ibge' => '8888888',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('resources.destroy', ['municipios', $municipio]))
            ->assertRedirect();

        $this->assertDatabaseMissing('municipios', [
            'id' => $municipio->id,
        ]);
    }

    public function test_admin_can_create_update_and_delete_every_generic_resource_module(): void
    {
        $case = Caso::firstOrFail();
        $municipio = Municipio::firstOrFail();
        $comunidade = Comunidade::firstOrFail();
        $familia = Familia::create([
            'referencia_familiar' => 'Familia base teste',
            'comunidade_id' => $comunidade->id,
            'quantidade_membros' => 2,
        ]);
        $pessoa = Pessoa::firstOrFail();
        $orgao = Orgao::firstOrFail();

        $modules = [
            'orgaos' => [
                Orgao::class,
                ['nome_orgao' => 'Orgao CRUD', 'tipo_orgao' => 'FUNAI', 'esfera' => 'Federal', 'municipio' => 'Manaus', 'responsavel' => 'Responsavel', 'contato' => 'contato@test', 'ativo' => true],
                ['nome_orgao' => 'Orgao CRUD Atualizado', 'tipo_orgao' => 'SESAI', 'esfera' => 'Estadual', 'municipio' => 'Manaus', 'responsavel' => 'Responsavel 2', 'contato' => 'contato2@test', 'ativo' => false],
                'nome_orgao',
                'Orgao CRUD Atualizado',
            ],
            'comunidades' => [
                Comunidade::class,
                ['nome_comunidade' => 'Comunidade CRUD', 'territorio' => 'Territorio', 'municipio_id' => $municipio->id, 'polo_base' => 'Polo', 'observacoes' => 'Obs'],
                ['nome_comunidade' => 'Comunidade CRUD Atualizada', 'territorio' => 'Territorio 2', 'municipio_id' => $municipio->id, 'polo_base' => 'Polo 2', 'observacoes' => 'Obs 2'],
                'nome_comunidade',
                'Comunidade CRUD Atualizada',
            ],
            'familias' => [
                Familia::class,
                ['referencia_familiar' => 'Familia CRUD', 'comunidade_id' => $comunidade->id, 'quantidade_membros' => 3, 'territorio' => 'Territorio', 'situacao_vulnerabilidade' => 'Baixa', 'observacoes' => 'Obs'],
                ['referencia_familiar' => 'Familia CRUD Atualizada', 'comunidade_id' => $comunidade->id, 'quantidade_membros' => 4, 'territorio' => 'Territorio 2', 'situacao_vulnerabilidade' => 'Media', 'observacoes' => 'Obs 2'],
                'referencia_familiar',
                'Familia CRUD Atualizada',
            ],
            'pessoas' => [
                Pessoa::class,
                ['nome_tradicional' => 'Tradicional CRUD', 'nome_civil' => 'Pessoa CRUD', 'sexo' => 'Nao informado', 'data_nascimento' => '2010-01-01', 'idade_aproximada' => 16, 'povo_indigena' => 'Yanomami', 'cpf' => '00000000000', 'rani' => 'RANI1', 'nis' => 'NIS1', 'situacao_documental' => 'CPF', 'familia_id' => $familia->id, 'observacoes' => 'Obs'],
                ['nome_tradicional' => 'Tradicional CRUD 2', 'nome_civil' => 'Pessoa CRUD Atualizada', 'sexo' => 'Feminino', 'data_nascimento' => '2011-01-01', 'idade_aproximada' => 15, 'povo_indigena' => 'Yanomami', 'cpf' => '11111111111', 'rani' => 'RANI2', 'nis' => 'NIS2', 'situacao_documental' => 'Regular', 'familia_id' => $familia->id, 'observacoes' => 'Obs 2'],
                'nome_civil',
                'Pessoa CRUD Atualizada',
            ],
            'atendimentos' => [
                Atendimento::class,
                ['caso_id' => $case->id, 'pessoa_id' => $pessoa->id, 'orgao_id' => $orgao->id, 'tipo_atendimento' => 'Social', 'data_atendimento' => '2026-05-15T10:00', 'descricao' => 'Atendimento CRUD', 'proximo_passo' => 'Passo', 'correcao_justificativa' => null],
                ['caso_id' => $case->id, 'pessoa_id' => $pessoa->id, 'orgao_id' => $orgao->id, 'tipo_atendimento' => 'Saude', 'data_atendimento' => '2026-05-15T11:00', 'descricao' => 'Atendimento CRUD Atualizado', 'proximo_passo' => 'Passo 2', 'correcao_justificativa' => 'Correcao'],
                'descricao',
                'Atendimento CRUD Atualizado',
            ],
            'encaminhamentos' => [
                Encaminhamento::class,
                ['caso_id' => $case->id, 'orgao_origem_id' => $orgao->id, 'orgao_destino_id' => $orgao->id, 'motivo' => 'Encaminhamento CRUD', 'status_encaminhamento' => 'Enviado', 'data_envio' => '2026-05-15T10:00', 'prazo_resposta' => '2026-05-20', 'resposta' => null, 'data_resposta' => null],
                ['caso_id' => $case->id, 'orgao_origem_id' => $orgao->id, 'orgao_destino_id' => $orgao->id, 'motivo' => 'Encaminhamento CRUD Atualizado', 'status_encaminhamento' => 'Respondido', 'data_envio' => '2026-05-15T11:00', 'prazo_resposta' => '2026-05-21', 'resposta' => 'Resposta', 'data_resposta' => '2026-05-16T11:00'],
                'motivo',
                'Encaminhamento CRUD Atualizado',
            ],
            'alertas' => [
                Alerta::class,
                ['caso_id' => $case->id, 'tipo_alerta' => 'Outro', 'nivel_alerta' => 'Medio', 'descricao' => 'Alerta CRUD', 'status_alerta' => 'Aberto', 'data_geracao' => '2026-05-15T10:00'],
                ['caso_id' => $case->id, 'tipo_alerta' => 'Violacao grave', 'nivel_alerta' => 'Alto', 'descricao' => 'Alerta CRUD Atualizado', 'status_alerta' => 'Resolvido', 'data_geracao' => '2026-05-15T11:00'],
                'descricao',
                'Alerta CRUD Atualizado',
            ],
            'casa-transito' => [
                CasaTransito::class,
                ['caso_id' => $case->id, 'data_entrada' => '2026-05-15T10:00', 'data_saida' => null, 'composicao_familiar' => 'Composicao', 'motivo_acolhimento' => 'Motivo', 'alimentacao' => 'Alimentacao', 'transporte' => 'Transporte', 'saude' => 'Saude', 'retorno_territorio' => 'Retorno', 'situacao_saida' => 'Situacao'],
                ['caso_id' => $case->id, 'data_entrada' => '2026-05-15T11:00', 'data_saida' => '2026-05-16T11:00', 'composicao_familiar' => 'Composicao 2', 'motivo_acolhimento' => 'Motivo Atualizado', 'alimentacao' => 'Alimentacao 2', 'transporte' => 'Transporte 2', 'saude' => 'Saude 2', 'retorno_territorio' => 'Retorno 2', 'situacao_saida' => 'Situacao 2'],
                'motivo_acolhimento',
                'Motivo Atualizado',
            ],
            'anexos' => [
                Anexo::class,
                ['caso_id' => $case->id, 'nome_arquivo' => 'arquivo.pdf', 'tipo_arquivo' => 'PDF', 'url_arquivo' => '/tmp/arquivo.pdf'],
                ['caso_id' => $case->id, 'nome_arquivo' => 'arquivo-atualizado.pdf', 'tipo_arquivo' => 'PDF', 'url_arquivo' => '/tmp/arquivo-atualizado.pdf'],
                'nome_arquivo',
                'arquivo-atualizado.pdf',
            ],
        ];

        foreach ($modules as $module => [$model, $createPayload, $updatePayload, $assertColumn, $assertValue]) {
            $this->actingAs($this->admin)
                ->post(route('resources.store', $module), $createPayload)
                ->assertRedirect();

            $record = $model::query()->where($assertColumn, $createPayload[$assertColumn])->firstOrFail();

            $this->actingAs($this->admin)
                ->put(route('resources.update', [$module, $record]), $updatePayload)
                ->assertRedirect();

            $this->assertDatabaseHas($record->getTable(), [
                'id' => $record->id,
                $assertColumn => $assertValue,
            ]);

            $this->actingAs($this->admin)
                ->delete(route('resources.destroy', [$module, $record]))
                ->assertRedirect();

            $this->assertDatabaseMissing($record->getTable(), [
                'id' => $record->id,
            ]);
        }
    }
}
