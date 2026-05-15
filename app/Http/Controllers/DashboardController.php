<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Caso;
use App\Models\Encaminhamento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $visibleCases = Caso::query()->visibleTo($request->user());
        $visibleCaseIds = (clone $visibleCases)->select('id');

        return Inertia::render('Siay/Dashboard', [
            'stats' => [
                'total_cases' => (clone $visibleCases)->count(),
                'active_cases' => (clone $visibleCases)->whereIn('status_caso', ['Aberto', 'Em acompanhamento'])->count(),
                'critical_cases' => (clone $visibleCases)->where('grau_risco', 'Alto')->count(),
                'stale_cases' => (clone $visibleCases)
                    ->where(function ($query): void {
                        $query->whereNull('data_ultima_atualizacao')
                            ->orWhere('data_ultima_atualizacao', '<', now()->subDays(30));
                    })
                    ->count(),
                'pending_referrals' => Encaminhamento::query()
                    ->whereIn('caso_id', $visibleCaseIds)
                    ->whereIn('status_encaminhamento', ['Enviado', 'Recebido', 'Em analise', 'Pendente'])
                    ->count(),
                'open_alerts' => Alerta::query()
                    ->whereIn('caso_id', (clone $visibleCases)->select('id'))
                    ->where('status_alerta', 'Aberto')
                    ->count(),
            ],
            'riskBreakdown' => Caso::query()
                ->visibleTo($request->user())
                ->selectRaw('grau_risco, count(*) as total')
                ->groupBy('grau_risco')
                ->orderBy('grau_risco')
                ->get(),
            'recentCases' => Caso::query()
                ->visibleTo($request->user())
                ->with(['municipio:id,nome_municipio', 'orgaoResponsavel:id,nome_orgao'])
                ->latest('data_ultima_atualizacao')
                ->limit(8)
                ->get(),
            'urgentAlerts' => Alerta::query()
                ->with(['caso:id,numero_caso,grau_risco,status_caso'])
                ->whereIn('caso_id', Caso::query()->visibleTo($request->user())->select('id'))
                ->where('status_alerta', 'Aberto')
                ->where('nivel_alerta', 'Alto')
                ->latest('data_geracao')
                ->limit(6)
                ->get(),
        ]);
    }
}
