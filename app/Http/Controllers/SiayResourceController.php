<?php

namespace App\Http\Controllers;

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
use App\Services\AuditLogger;
use App\Services\DiagnosticoInsegurancaAlimentarService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SiayResourceController extends Controller
{
    public function index(Request $request, string $module): Response
    {
        $definition = $this->definition($module);
        $this->authorizeModule($request, $definition['view']);

        /** @var class-string<Model> $model */
        $model = $definition['model'];
        $query = $model::query();

        if ($module === 'familias') {
            $query->with('questionarioInsegurancaAlimentar');
        }

        if ($definition['case_scoped'] ?? false) {
            $query->whereIn('caso_id', Caso::query()->visibleTo($request->user())->select('id'));
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($query) use ($definition, $search): void {
                foreach ($definition['search'] as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        if ($module === 'familias' && $request->filled('diagnostico')) {
            $query->whereHas('questionarioInsegurancaAlimentar', function ($query) use ($request): void {
                $query->where('diagnostico', $request->string('diagnostico')->toString());
            });
        }

        return Inertia::render('Siay/Resources/Index', [
            'moduleKey' => $module,
            'module' => [
                'title' => $definition['title'],
                'description' => $definition['description'],
                'fields' => $this->fieldsWithOptions($request, $definition['fields']),
            ],
            'records' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => $request->only('search', 'diagnostico'),
            'can' => [
                'manage' => $request->user()->hasAnyPermission($definition['manage']),
            ],
        ]);
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $definition = $this->definition($module);
        $this->authorizeModule($request, $definition['manage']);

        /** @var class-string<Model> $model */
        $model = $definition['model'];
        $data = $request->validate($this->rules($definition['fields']));
        $questionnaire = $module === 'familias' ? $this->validatedQuestionnaire($request) : null;

        if ($module === 'atendimentos') {
            $data['usuario_id'] = $request->user()->id;
        }

        $this->ensureCaseScopeIsVisible($request, $definition, $data['caso_id'] ?? null);

        if ($module === 'alertas') {
            $data['data_geracao'] ??= now();
        }

        $record = $model::create($data);

        if ($record instanceof Familia && $questionnaire) {
            $this->saveQuestionnaire($record, $questionnaire);
        }

        AuditLogger::record($module.'_created', $record, "{$definition['singular']} criado(a).");

        return back()->with('success', "{$definition['singular']} criado(a).");
    }

    public function update(Request $request, string $module, string $record): RedirectResponse
    {
        $definition = $this->definition($module);
        $this->authorizeModule($request, $definition['manage']);

        /** @var class-string<Model> $model */
        $model = $definition['model'];
        $entry = $this->findRecordForRequest($request, $definition, $model, $record);
        $data = $request->validate($this->rules($definition['fields'], updating: true));
        $questionnaire = $module === 'familias' ? $this->validatedQuestionnaire($request) : null;

        $this->ensureCaseScopeIsVisible($request, $definition, $data['caso_id'] ?? null);

        if ($module === 'atendimentos') {
            $data['corrigido_por_id'] = $request->user()->id;
        }

        $before = $entry->getOriginal();
        $entry->update($data);

        if ($entry instanceof Familia && $questionnaire) {
            $this->saveQuestionnaire($entry, $questionnaire);
        }

        AuditLogger::record($module.'_updated', $entry, "{$definition['singular']} atualizado(a).", [
            'before' => $before,
            'after' => $entry->fresh()->toArray(),
        ]);

        return back()->with('success', "{$definition['singular']} atualizado(a).");
    }

    public function destroy(Request $request, string $module, string $record): RedirectResponse
    {
        $definition = $this->definition($module);
        $this->authorizeModule($request, $definition['manage']);

        /** @var class-string<Model> $model */
        $model = $definition['model'];
        $entry = $this->findRecordForRequest($request, $definition, $model, $record);

        try {
            $before = $entry->toArray();
            $entry->delete();
        } catch (QueryException) {
            return back()->with('error', "{$definition['singular']} possui vinculos e nao pode ser excluido(a).");
        }

        AuditLogger::record($module.'_deleted', null, "{$definition['singular']} excluido(a).", [
            'before' => $before,
        ]);

        return back()->with('success', "{$definition['singular']} excluido(a).");
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  class-string<Model>  $model
     */
    private function findRecordForRequest(Request $request, array $definition, string $model, string $record): Model
    {
        $query = $model::query();

        if ($definition['case_scoped'] ?? false) {
            $query->whereIn('caso_id', Caso::query()->visibleTo($request->user())->select('id'));
        }

        return $query->findOrFail($record);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function ensureCaseScopeIsVisible(Request $request, array $definition, ?string $caseId): void
    {
        if (! ($definition['case_scoped'] ?? false) || blank($caseId)) {
            return;
        }

        abort_unless(
            Caso::query()->visibleTo($request->user())->whereKey($caseId)->exists(),
            403,
            'Voce nao tem permissao para alterar registros deste caso.'
        );
    }

    /**
     * @param  array<string, mixed>  $permissions
     */
    private function authorizeModule(Request $request, array $permissions): void
    {
        abort_unless($request->user()->hasAnyPermission($permissions), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $module): array
    {
        $definitions = $this->definitions();
        abort_unless(isset($definitions[$module]), 404);

        return $definitions[$module];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            'orgaos' => [
                'title' => 'Orgaos e parceiros',
                'singular' => 'Orgao',
                'description' => 'Instituicoes responsaveis, apoiadoras e pontos de contato da rede.',
                'model' => Orgao::class,
                'view' => ['organizations.manage'],
                'manage' => ['organizations.manage'],
                'search' => ['nome_orgao', 'tipo_orgao', 'municipio'],
                'fields' => [
                    ['name' => 'nome_orgao', 'label' => 'Nome do orgao', 'type' => 'text', 'required' => true],
                    ['name' => 'tipo_orgao', 'label' => 'Tipo', 'type' => 'select', 'options' => ['FUNAI', 'SESAI', 'SEMAS', 'CRAS', 'CREAS', 'DSEI', 'Casa de Transito', 'Outros']],
                    ['name' => 'esfera', 'label' => 'Esfera', 'type' => 'select', 'options' => ['Federal', 'Estadual', 'Municipal', 'Parceiro']],
                    ['name' => 'municipio', 'label' => 'Municipio', 'type' => 'text'],
                    ['name' => 'responsavel', 'label' => 'Responsavel', 'type' => 'text'],
                    ['name' => 'contato', 'label' => 'Contato', 'type' => 'text'],
                    ['name' => 'ativo', 'label' => 'Ativo', 'type' => 'boolean'],
                ],
            ],
            'municipios' => [
                'title' => 'Municipios',
                'singular' => 'Municipio',
                'description' => 'Base territorial para filtros, indicadores e restricao municipal.',
                'model' => Municipio::class,
                'view' => ['organizations.manage'],
                'manage' => ['organizations.manage'],
                'search' => ['nome_municipio', 'codigo_ibge'],
                'fields' => [
                    ['name' => 'nome_municipio', 'label' => 'Nome do municipio', 'type' => 'text', 'required' => true],
                    ['name' => 'estado', 'label' => 'UF', 'type' => 'text', 'required' => true],
                    ['name' => 'codigo_ibge', 'label' => 'Codigo IBGE', 'type' => 'text'],
                ],
            ],
            'comunidades' => [
                'title' => 'Comunidades e aldeias',
                'singular' => 'Comunidade',
                'description' => 'Cadastro territorial de comunidades, aldeias e polos base.',
                'model' => Comunidade::class,
                'view' => ['people.view', 'organizations.manage'],
                'manage' => ['people.manage', 'organizations.manage'],
                'search' => ['nome_comunidade', 'territorio', 'polo_base'],
                'fields' => [
                    ['name' => 'nome_comunidade', 'label' => 'Comunidade/Aldeia', 'type' => 'text', 'required' => true],
                    ['name' => 'territorio', 'label' => 'Territorio', 'type' => 'text'],
                    ['name' => 'municipio_id', 'label' => 'Municipio', 'type' => 'select', 'source' => 'municipios'],
                    ['name' => 'polo_base', 'label' => 'Polo base', 'type' => 'text'],
                    ['name' => 'observacoes', 'label' => 'Observacoes', 'type' => 'textarea'],
                ],
            ],
            'familias' => [
                'title' => 'Familias',
                'singular' => 'Familia',
                'description' => 'Nucleos familiares vinculaveis a pessoas e casos.',
                'model' => Familia::class,
                'view' => ['people.view'],
                'manage' => ['people.manage'],
                'search' => ['referencia_familiar', 'territorio', 'situacao_vulnerabilidade'],
                'fields' => [
                    ['name' => 'referencia_familiar', 'label' => 'Referencia familiar', 'type' => 'text', 'required' => true],
                    ['name' => 'comunidade_id', 'label' => 'Comunidade', 'type' => 'select', 'source' => 'comunidades'],
                    ['name' => 'quantidade_membros', 'label' => 'Quantidade de membros', 'type' => 'number'],
                    ['name' => 'territorio', 'label' => 'Territorio', 'type' => 'text'],
                    ['name' => 'situacao_vulnerabilidade', 'label' => 'Situacao de vulnerabilidade', 'type' => 'textarea'],
                    ['name' => 'observacoes', 'label' => 'Observacoes', 'type' => 'textarea'],
                ],
            ],
            'pessoas' => [
                'title' => 'Pessoas',
                'singular' => 'Pessoa',
                'description' => 'Cadastro nominal, documental e familiar.',
                'model' => Pessoa::class,
                'view' => ['people.view'],
                'manage' => ['people.manage'],
                'search' => ['nome_tradicional', 'nome_civil', 'cpf', 'rani', 'nis'],
                'fields' => [
                    ['name' => 'nome_tradicional', 'label' => 'Nome social/tradicional', 'type' => 'text'],
                    ['name' => 'nome_civil', 'label' => 'Nome civil', 'type' => 'text'],
                    ['name' => 'sexo', 'label' => 'Sexo', 'type' => 'select', 'options' => ['Feminino', 'Masculino', 'Nao informado']],
                    ['name' => 'data_nascimento', 'label' => 'Data de nascimento', 'type' => 'date'],
                    ['name' => 'idade_aproximada', 'label' => 'Idade aproximada', 'type' => 'number'],
                    ['name' => 'povo_indigena', 'label' => 'Povo indigena', 'type' => 'text'],
                    ['name' => 'cpf', 'label' => 'CPF', 'type' => 'text'],
                    ['name' => 'rani', 'label' => 'RANI', 'type' => 'text'],
                    ['name' => 'nis', 'label' => 'NIS', 'type' => 'text'],
                    ['name' => 'situacao_documental', 'label' => 'Situacao documental', 'type' => 'select', 'options' => ['Sem documento', 'CPF', 'RANI', 'NIS', 'Documentacao incompleta', 'Regular']],
                    ['name' => 'familia_id', 'label' => 'Familia', 'type' => 'select', 'source' => 'familias'],
                    ['name' => 'observacoes', 'label' => 'Observacoes', 'type' => 'textarea'],
                ],
            ],
            'atendimentos' => [
                'title' => 'Atendimentos',
                'singular' => 'Atendimento',
                'description' => 'Historico operacional do caso. Registros devem ser corrigidos, nao apagados.',
                'model' => Atendimento::class,
                'view' => ['care_records.view'],
                'manage' => ['care_records.create', 'care_records.update'],
                'case_scoped' => true,
                'search' => ['tipo_atendimento', 'descricao', 'proximo_passo'],
                'fields' => [
                    ['name' => 'caso_id', 'label' => 'Caso', 'type' => 'select', 'source' => 'casos', 'required' => true],
                    ['name' => 'pessoa_id', 'label' => 'Pessoa vinculada', 'type' => 'select', 'source' => 'pessoas'],
                    ['name' => 'orgao_id', 'label' => 'Orgao responsavel', 'type' => 'select', 'source' => 'orgaos', 'required' => true],
                    ['name' => 'tipo_atendimento', 'label' => 'Tipo de atendimento', 'type' => 'select', 'options' => ['Social', 'Saude', 'Documentacao', 'Protecao', 'Territorial', 'Acolhimento', 'Outro'], 'required' => true],
                    ['name' => 'data_atendimento', 'label' => 'Data do atendimento', 'type' => 'datetime-local', 'required' => true],
                    ['name' => 'descricao', 'label' => 'Descricao', 'type' => 'textarea', 'required' => true],
                    ['name' => 'proximo_passo', 'label' => 'Proximo passo', 'type' => 'textarea'],
                    ['name' => 'correcao_justificativa', 'label' => 'Justificativa de correcao', 'type' => 'textarea'],
                ],
            ],
            'encaminhamentos' => [
                'title' => 'Encaminhamentos',
                'singular' => 'Encaminhamento',
                'description' => 'Fluxo entre orgaos, prazos de resposta e retorno recebido.',
                'model' => Encaminhamento::class,
                'view' => ['referrals.view'],
                'manage' => ['referrals.create', 'referrals.update'],
                'case_scoped' => true,
                'search' => ['motivo', 'status_encaminhamento', 'resposta'],
                'fields' => [
                    ['name' => 'caso_id', 'label' => 'Caso', 'type' => 'select', 'source' => 'casos', 'required' => true],
                    ['name' => 'orgao_origem_id', 'label' => 'Orgao origem', 'type' => 'select', 'source' => 'orgaos', 'required' => true],
                    ['name' => 'orgao_destino_id', 'label' => 'Orgao destino', 'type' => 'select', 'source' => 'orgaos', 'required' => true],
                    ['name' => 'motivo', 'label' => 'Motivo', 'type' => 'textarea', 'required' => true],
                    ['name' => 'status_encaminhamento', 'label' => 'Status', 'type' => 'select', 'options' => ['Enviado', 'Recebido', 'Em analise', 'Respondido', 'Concluido', 'Recusado'], 'required' => true],
                    ['name' => 'data_envio', 'label' => 'Data envio', 'type' => 'datetime-local', 'required' => true],
                    ['name' => 'prazo_resposta', 'label' => 'Prazo resposta', 'type' => 'date'],
                    ['name' => 'resposta', 'label' => 'Resposta recebida', 'type' => 'textarea'],
                    ['name' => 'data_resposta', 'label' => 'Data resposta', 'type' => 'datetime-local'],
                ],
            ],
            'alertas' => [
                'title' => 'Alertas e riscos',
                'singular' => 'Alerta',
                'description' => 'Pendencias, riscos e alertas automaticos ou manuais.',
                'model' => Alerta::class,
                'view' => ['alerts.view'],
                'manage' => ['alerts.manage'],
                'case_scoped' => true,
                'search' => ['tipo_alerta', 'nivel_alerta', 'descricao', 'status_alerta'],
                'fields' => [
                    ['name' => 'caso_id', 'label' => 'Caso', 'type' => 'select', 'source' => 'casos', 'required' => true],
                    ['name' => 'tipo_alerta', 'label' => 'Tipo alerta', 'type' => 'select', 'options' => ['Caso sem atualizacao', 'Crianca desacompanhada', 'Violacao grave', 'Remocao de saude', 'Falta de documentacao', 'Risco alimentar', 'Outro'], 'required' => true],
                    ['name' => 'nivel_alerta', 'label' => 'Nivel', 'type' => 'select', 'options' => ['Alto', 'Medio', 'Baixo'], 'required' => true],
                    ['name' => 'descricao', 'label' => 'Descricao', 'type' => 'textarea'],
                    ['name' => 'status_alerta', 'label' => 'Status', 'type' => 'select', 'options' => ['Aberto', 'Resolvido'], 'required' => true],
                    ['name' => 'data_geracao', 'label' => 'Data geracao', 'type' => 'datetime-local'],
                ],
            ],
            'casa-transito' => [
                'title' => 'Casa de transito',
                'singular' => 'Acolhimento',
                'description' => 'Entrada, permanencia, transporte, saude e retorno ao territorio.',
                'model' => CasaTransito::class,
                'view' => ['transit_house.view'],
                'manage' => ['transit_house.manage'],
                'case_scoped' => true,
                'search' => ['motivo_acolhimento', 'situacao_saida', 'retorno_territorio'],
                'fields' => [
                    ['name' => 'caso_id', 'label' => 'Caso', 'type' => 'select', 'source' => 'casos', 'required' => true],
                    ['name' => 'data_entrada', 'label' => 'Data entrada', 'type' => 'datetime-local', 'required' => true],
                    ['name' => 'data_saida', 'label' => 'Data saida', 'type' => 'datetime-local'],
                    ['name' => 'composicao_familiar', 'label' => 'Composicao familiar', 'type' => 'textarea'],
                    ['name' => 'motivo_acolhimento', 'label' => 'Motivo permanencia', 'type' => 'textarea'],
                    ['name' => 'alimentacao', 'label' => 'Alimentacao', 'type' => 'textarea'],
                    ['name' => 'transporte', 'label' => 'Transporte', 'type' => 'textarea'],
                    ['name' => 'saude', 'label' => 'Saude', 'type' => 'textarea'],
                    ['name' => 'retorno_territorio', 'label' => 'Retorno territorio', 'type' => 'textarea'],
                    ['name' => 'situacao_saida', 'label' => 'Situacao saida', 'type' => 'textarea'],
                ],
            ],
            'anexos' => [
                'title' => 'Anexos',
                'singular' => 'Anexo',
                'description' => 'Documentos, fotos e links vinculados aos casos.',
                'model' => Anexo::class,
                'view' => ['attachments.manage'],
                'manage' => ['attachments.manage'],
                'case_scoped' => true,
                'search' => ['nome_arquivo', 'tipo_arquivo', 'url_arquivo'],
                'fields' => [
                    ['name' => 'caso_id', 'label' => 'Caso', 'type' => 'select', 'source' => 'casos', 'required' => true],
                    ['name' => 'nome_arquivo', 'label' => 'Nome arquivo', 'type' => 'text', 'required' => true],
                    ['name' => 'tipo_arquivo', 'label' => 'Tipo arquivo', 'type' => 'text'],
                    ['name' => 'url_arquivo', 'label' => 'URL/Caminho do arquivo', 'type' => 'text', 'required' => true],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    private function rules(array $fields, bool $updating = false): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $rule = ($field['required'] ?? false) ? ['required'] : ['nullable'];

            $rule[] = match ($field['type']) {
                'number' => 'integer',
                'date', 'datetime-local' => 'date',
                'boolean' => 'boolean',
                default => 'string',
            };

            if (($field['source'] ?? null) && $field['source'] !== 'casos') {
                $table = [
                    'municipios' => 'municipios',
                    'comunidades' => 'comunidades',
                    'familias' => 'familias',
                    'pessoas' => 'pessoas',
                    'orgaos' => 'orgaos',
                ][$field['source']] ?? null;

                if ($table) {
                    $rule[] = "exists:{$table},id";
                }
            }

            if (($field['source'] ?? null) === 'casos') {
                $rule[] = 'exists:casos,id';
            }

            $rules[$field['name']] = $rule;
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validatedQuestionnaire(Request $request): ?array
    {
        if (! $request->has('questionario')) {
            return null;
        }

        $yesNo = ['Sim', 'Nao'];
        $hasOtherFactor = in_array('Outros', (array) $request->input('questionario.fatores_limitantes', []), true);
        $hasOtherProgram = in_array('Outros', (array) $request->input('questionario.programas_assistencia', []), true);
        $hasOtherMarket = in_array('Outros', (array) $request->input('questionario.locais_compra', []), true);
        $hasOtherDesiredFood = in_array('Outros', (array) $request->input('questionario.alimentos_desejados', []), true);

        return $request->validate([
            'questionario' => ['required', 'array'],
            'questionario.refeicoes_semana' => ['required', Rule::in(['1 vez', '2 vezes', '3 vezes', '4 vezes', '5 vezes', '6 vezes', '7 vezes'])],
            'questionario.refeicoes_dia' => ['required', Rule::in(['1 vez ao dia', '2 vezes ao dia', '3 vezes ao dia', '4 vezes ao dia', '5 vezes ou mais', 'Nenhuma'])],
            'questionario.preocupacao_falta_comida' => ['required', Rule::in(['Diariamente', 'Semanalmente', 'Apenas 1 vez ao mes', 'Raramente', 'Nunca'])],
            'questionario.reduziu_porcoes' => ['required', Rule::in(['Sim', 'Nao', 'Frequentemente'])],
            'questionario.fatores_limitantes' => ['nullable', 'array'],
            'questionario.fatores_limitantes.*' => [Rule::in(['Falta de dinheiro', 'Falta de transporte', 'Falta de tempo para cozinhar', 'Falta de opcoes de alimentos saudaveis na regiao', 'Outros'])],
            'questionario.fatores_limitantes_outros' => [Rule::requiredIf($hasOtherFactor), 'nullable', 'string'],
            'questionario.programas_assistencia' => ['nullable', 'array'],
            'questionario.programas_assistencia.*' => [Rule::in(['Bancos de alimentos', 'Programas de alimentacao escolar', 'Recebimento de cestas basicas', 'Cupons de alimentos', 'Outros'])],
            'questionario.programas_assistencia_outros' => [Rule::requiredIf($hasOtherProgram), 'nullable', 'string'],
            'questionario.locais_compra' => ['nullable', 'array'],
            'questionario.locais_compra.*' => [Rule::in(['Supermercados', 'Feiras', 'Tabernas', 'Outros'])],
            'questionario.locais_compra_outros' => [Rule::requiredIf($hasOtherMarket), 'nullable', 'string'],
            'questionario.alimentos_acessiveis' => ['required', Rule::in($yesNo)],
            'questionario.frequencia_alimentos_baixo_custo' => ['required', Rule::in(['1 vez', '2 vezes', '3 vezes', '4 vezes', 'Acima de 4 vezes', 'Nunca'])],
            'questionario.alimentos_desejados' => ['nullable', 'array'],
            'questionario.alimentos_desejados.*' => [Rule::in(['Leites e derivados', 'Carnes e ovos', 'Oleos e margarinas', 'Cereais e leguminosas', 'Hortalicas e frutas', 'Outros'])],
            'questionario.alimentos_desejados_outros' => [Rule::requiredIf($hasOtherDesiredFood), 'nullable', 'string'],
            'questionario.situacao_financeira_afeta' => ['required', Rule::in($yesNo)],
            'questionario.participa_associacao' => ['required', Rule::in($yesNo)],
            'questionario.associacao_qual' => ['nullable', 'required_if:questionario.participa_associacao,Sim', 'string'],
        ])['questionario'];
    }

    /**
     * @param  array<string, mixed>  $questionnaire
     */
    private function saveQuestionnaire(Familia $familia, array $questionnaire): void
    {
        foreach ([
            'fatores_limitantes' => 'fatores_limitantes_outros',
            'programas_assistencia' => 'programas_assistencia_outros',
            'locais_compra' => 'locais_compra_outros',
            'alimentos_desejados' => 'alimentos_desejados_outros',
        ] as $optionsKey => $otherKey) {
            if (! in_array('Outros', (array) ($questionnaire[$optionsKey] ?? []), true)) {
                $questionnaire[$otherKey] = null;
            }
        }

        if (($questionnaire['participa_associacao'] ?? null) !== 'Sim') {
            $questionnaire['associacao_qual'] = null;
        }

        $diagnosis = app(DiagnosticoInsegurancaAlimentarService::class)->calculate($questionnaire);

        $familia->questionarioInsegurancaAlimentar()->updateOrCreate(
            ['familia_id' => $familia->id],
            array_merge($questionnaire, $diagnosis),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function fieldsWithOptions(Request $request, array $fields): array
    {
        return collect($fields)->map(function (array $field) use ($request): array {
            if (isset($field['options'])) {
                $field['options'] = collect($field['options'])->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => $value,
                ])->values();
            }

            if (! isset($field['source'])) {
                return $field;
            }

            $field['options'] = match ($field['source']) {
                'municipios' => Municipio::orderBy('nome_municipio')->get()->map(fn (Municipio $item): array => ['value' => $item->id, 'label' => $item->nome_municipio]),
                'comunidades' => Comunidade::orderBy('nome_comunidade')->get()->map(fn (Comunidade $item): array => ['value' => $item->id, 'label' => $item->nome_comunidade]),
                'familias' => Familia::orderBy('referencia_familiar')->get()->map(fn (Familia $item): array => ['value' => $item->id, 'label' => $item->referencia_familiar]),
                'pessoas' => Pessoa::orderBy('nome_civil')->orderBy('nome_tradicional')->limit(300)->get()->map(fn (Pessoa $item): array => ['value' => $item->id, 'label' => $item->nome_civil ?: $item->nome_tradicional ?: $item->id]),
                'orgaos' => Orgao::where('ativo', true)->orderBy('nome_orgao')->get()->map(fn (Orgao $item): array => ['value' => $item->id, 'label' => $item->nome_orgao]),
                'casos' => Caso::query()->visibleTo($request->user())->orderBy('numero_caso')->get()->map(fn (Caso $item): array => ['value' => $item->id, 'label' => $item->numero_caso]),
                default => collect(),
            };

            return $field;
        })->values()->all();
    }
}
