<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Caso;
use App\Models\Comunidade;
use App\Models\Municipio;
use App\Models\Orgao;
use App\Models\Pessoa;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CaseController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Caso::query()
            ->visibleTo($request->user())
            ->with(['municipio:id,nome_municipio', 'comunidade:id,nome_comunidade', 'orgaoResponsavel:id,nome_orgao', 'usuarioResponsavel:id,name']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($query) use ($search): void {
                $query->where('numero_caso', 'like', "%{$search}%")
                    ->orWhere('descricao_resumida', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status_caso', $status);
        }

        if ($risk = $request->string('risk')->toString()) {
            $query->where('grau_risco', $risk);
        }

        return Inertia::render('Siay/Cases/Index', [
            'cases' => $query->latest('data_ultima_atualizacao')->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'risk']),
            'can' => [
                'create' => $request->user()->hasPermission('cases.create'),
                'update' => $request->user()->hasPermission('cases.update'),
                'viewSensitive' => $request->user()->hasPermission('cases.view_sensitive'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('cases.create'), 403);

        return Inertia::render('Siay/Cases/Form', [
            'mode' => 'create',
            'caseRecord' => null,
            'options' => $this->options($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('cases.create'), 403);

        $data = $this->validated($request);
        $people = $this->peoplePayload($request);
        unset($data['pessoas']);

        $data['numero_caso'] = $this->generateCaseNumber($data['municipio_id']);
        $data['data_abertura'] = now();
        $data['data_ultima_atualizacao'] = now();
        $data['sigiloso'] = $request->user()->hasPermission('cases.view_sensitive')
            ? $request->boolean('sigiloso')
            : false;

        $case = Caso::create($data);
        $case->pessoas()->sync($people);

        if ($case->grau_risco === 'Alto') {
            Alerta::create([
                'caso_id' => $case->id,
                'tipo_alerta' => 'Caso critico',
                'nivel_alerta' => 'Alto',
                'descricao' => 'Alerta automatico gerado por classificacao de alto risco.',
                'status_alerta' => 'Aberto',
                'data_geracao' => now(),
            ]);
        }

        AuditLogger::record('case_created', $case, "Caso {$case->numero_caso} criado.");

        return redirect()->route('cases.show', $case)->with('success', 'Caso criado com sucesso.');
    }

    public function show(Request $request, string $case): Response
    {
        $caseRecord = $this->findVisibleCase($request, $case)->load([
            'municipio',
            'comunidade',
            'orgaoResponsavel',
            'usuarioResponsavel',
            'pessoas.familia',
            'atendimentos.orgao',
            'atendimentos.usuario',
            'encaminhamentos.orgaoOrigem',
            'encaminhamentos.orgaoDestino',
            'alertas',
            'acolhimento',
        ]);

        AuditLogger::record('case_viewed', $caseRecord, "Caso {$caseRecord->numero_caso} visualizado.");

        return Inertia::render('Siay/Cases/Show', [
            'caseRecord' => $caseRecord,
            'can' => [
                'update' => $request->user()->hasPermission('cases.update'),
                'createAttendance' => $request->user()->hasPermission('care_records.create'),
                'createReferral' => $request->user()->hasPermission('referrals.create'),
            ],
        ]);
    }

    public function edit(Request $request, string $case): Response
    {
        abort_unless($request->user()->hasPermission('cases.update'), 403);

        return Inertia::render('Siay/Cases/Form', [
            'mode' => 'edit',
            'caseRecord' => $this->findVisibleCase($request, $case)->load('pessoas:id'),
            'options' => $this->options($request),
        ]);
    }

    public function update(Request $request, string $case): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('cases.update'), 403);

        $caseRecord = $this->findVisibleCase($request, $case);
        $data = $this->validated($request);
        $people = $this->peoplePayload($request);
        unset($data['pessoas']);

        $data['data_ultima_atualizacao'] = now();
        $data['sigiloso'] = $request->user()->hasPermission('cases.view_sensitive')
            ? $request->boolean('sigiloso')
            : $caseRecord->sigiloso;

        $before = $caseRecord->getOriginal();
        $caseRecord->update($data);
        $caseRecord->pessoas()->sync($people);

        AuditLogger::record('case_updated', $caseRecord, "Caso {$caseRecord->numero_caso} atualizado.", [
            'before' => $before,
            'after' => $caseRecord->fresh()->toArray(),
        ]);

        return redirect()->route('cases.show', $caseRecord)->with('success', 'Caso atualizado com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'tipo_caso' => ['required', 'in:Individual,Familiar,Coletivo'],
            'descricao_resumida' => ['required', 'string', 'min:10'],
            'status_caso' => ['required', 'in:Aberto,Em acompanhamento,Fechado'],
            'grau_risco' => ['required', 'in:Alto,Medio,Baixo'],
            'orgao_responsavel_id' => ['required', 'exists:orgaos,id'],
            'usuario_responsavel_id' => ['required', 'exists:users,id'],
            'municipio_id' => ['required', 'exists:municipios,id'],
            'comunidade_id' => ['nullable', 'exists:comunidades,id'],
            'sigiloso' => ['boolean'],
            'motivo_encerramento' => ['nullable', 'string', 'max:255'],
            'justificativa_encerramento' => ['nullable', 'string'],
            'pessoas' => ['array'],
            'pessoas.*.id' => ['nullable', 'exists:pessoas,id'],
            'pessoas.*.papel_no_caso' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function peoplePayload(Request $request): array
    {
        return collect($request->input('pessoas', []))
            ->filter(fn (array $person): bool => filled($person['id'] ?? null))
            ->mapWithKeys(fn (array $person): array => [
                $person['id'] => ['papel_no_caso' => $person['papel_no_caso'] ?? null],
            ])
            ->all();
    }

    private function findVisibleCase(Request $request, string $case): Caso
    {
        return Caso::query()->visibleTo($request->user())->whereKey($case)->firstOrFail();
    }

    private function generateCaseNumber(string $municipioId): string
    {
        $municipio = Municipio::find($municipioId);
        $year = now()->year;
        $state = $municipio?->estado ?: 'AM';
        $cityCode = strtoupper(Str::substr(str_replace('-', '', Str::slug($municipio?->nome_municipio ?: 'MUN')), 0, 3)) ?: 'MUN';
        $sequence = Caso::query()->whereYear('data_abertura', $year)->count() + 1;

        do {
            $number = sprintf('%s-%s-%d-%06d', $state, $cityCode, $year, $sequence++);
        } while (Caso::where('numero_caso', $number)->exists());

        return $number;
    }

    /**
     * @return array<string, mixed>
     */
    private function options(Request $request): array
    {
        return [
            'municipios' => Municipio::orderBy('nome_municipio')->get(['id', 'nome_municipio', 'estado']),
            'comunidades' => Comunidade::orderBy('nome_comunidade')->get(['id', 'nome_comunidade', 'municipio_id']),
            'orgaos' => Orgao::where('ativo', true)->orderBy('nome_orgao')->get(['id', 'nome_orgao', 'municipio']),
            'users' => User::where('active', true)->orderBy('name')->get(['id', 'name', 'email', 'municipio']),
            'pessoas' => Pessoa::orderBy('nome_civil')->orderBy('nome_tradicional')->limit(300)->get(['id', 'nome_civil', 'nome_tradicional']),
        ];
    }
}
