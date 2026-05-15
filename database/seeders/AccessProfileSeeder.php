<?php

namespace Database\Seeders;

use App\Models\AccessProfile;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccessProfileSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['dashboard.view', 'Painel', 'Ver dashboard'],
            ['cases.view_all', 'Casos', 'Ver todos os casos'],
            ['cases.view_municipality', 'Casos', 'Ver casos do municipio'],
            ['cases.view_orgao', 'Casos', 'Ver casos do orgao'],
            ['cases.view_assigned', 'Casos', 'Ver casos sob responsabilidade'],
            ['cases.create', 'Casos', 'Criar casos'],
            ['cases.update', 'Casos', 'Editar casos'],
            ['cases.view_sensitive', 'Casos', 'Ver casos sigilosos'],
            ['people.view', 'Pessoas e familias', 'Ver pessoas e familias'],
            ['people.manage', 'Pessoas e familias', 'Gerenciar pessoas e familias'],
            ['organizations.manage', 'Orgaos', 'Gerenciar orgaos, municipios e comunidades'],
            ['care_records.view', 'Atendimentos', 'Ver atendimentos'],
            ['care_records.create', 'Atendimentos', 'Registrar atendimentos'],
            ['care_records.update', 'Atendimentos', 'Corrigir atendimentos'],
            ['referrals.view', 'Encaminhamentos', 'Ver encaminhamentos'],
            ['referrals.create', 'Encaminhamentos', 'Criar encaminhamentos'],
            ['referrals.update', 'Encaminhamentos', 'Atualizar encaminhamentos'],
            ['alerts.view', 'Alertas', 'Ver alertas'],
            ['alerts.manage', 'Alertas', 'Gerenciar alertas'],
            ['transit_house.view', 'Casa de transito', 'Ver acolhimentos'],
            ['transit_house.manage', 'Casa de transito', 'Gerenciar acolhimentos'],
            ['attachments.manage', 'Anexos', 'Gerenciar anexos'],
            ['users.manage', 'Usuarios', 'Gerenciar todos os usuarios'],
            ['users.manage_municipality', 'Usuarios', 'Gerenciar usuarios municipais'],
            ['profiles.manage', 'Perfis', 'Gerenciar permissoes por perfil'],
            ['audit.view', 'Auditoria', 'Ver logs de auditoria'],
            ['reports.view', 'Relatorios', 'Ver relatorios e indicadores'],
            ['health.view', 'Saude', 'Ver campos de saude autorizados'],
            ['social.view', 'Assistencia social', 'Ver campos sociais autorizados'],
            ['territory.view', 'Territorio', 'Ver campos territoriais autorizados'],
        ];

        foreach ($permissions as [$key, $module, $name]) {
            Permission::updateOrCreate(
                ['key' => $key],
                [
                    'module' => $module,
                    'name' => $name,
                    'description' => $name,
                ],
            );
        }

        $all = Permission::pluck('id', 'key');

        $profiles = [
            'Administrador Estadual' => $all->keys()->all(),
            'Gestor Municipal' => [
                'dashboard.view',
                'cases.view_municipality',
                'cases.create',
                'cases.update',
                'people.view',
                'people.manage',
                'care_records.view',
                'care_records.create',
                'care_records.update',
                'referrals.view',
                'referrals.create',
                'referrals.update',
                'alerts.view',
                'alerts.manage',
                'users.manage_municipality',
                'reports.view',
                'social.view',
            ],
            'Tecnico CRAS' => [
                'dashboard.view',
                'cases.view_municipality',
                'cases.create',
                'cases.update',
                'people.view',
                'people.manage',
                'care_records.view',
                'care_records.create',
                'referrals.view',
                'referrals.create',
                'alerts.view',
                'social.view',
            ],
            'Tecnico CREAS' => [
                'dashboard.view',
                'cases.view_municipality',
                'cases.create',
                'cases.update',
                'people.view',
                'people.manage',
                'care_records.view',
                'care_records.create',
                'referrals.view',
                'referrals.create',
                'alerts.view',
                'alerts.manage',
                'social.view',
            ],
            'SESAI / Saude' => [
                'dashboard.view',
                'cases.view_orgao',
                'cases.create',
                'cases.update',
                'people.view',
                'care_records.view',
                'care_records.create',
                'referrals.view',
                'referrals.create',
                'alerts.view',
                'health.view',
            ],
            'FUNAI' => [
                'dashboard.view',
                'cases.view_orgao',
                'cases.create',
                'cases.update',
                'people.view',
                'care_records.view',
                'care_records.create',
                'referrals.view',
                'referrals.create',
                'alerts.view',
                'territory.view',
            ],
            'Casa de Transito' => [
                'dashboard.view',
                'cases.view_orgao',
                'people.view',
                'transit_house.view',
                'transit_house.manage',
                'care_records.view',
                'care_records.create',
                'referrals.view',
                'referrals.create',
                'alerts.view',
            ],
            'Visualizacao Institucional' => [
                'dashboard.view',
                'cases.view_municipality',
                'reports.view',
            ],
            'Auditoria / Controle' => [
                'dashboard.view',
                'audit.view',
                'reports.view',
            ],
        ];

        foreach ($profiles as $name => $keys) {
            $profile = AccessProfile::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => $this->descriptionFor($name),
                    'system' => true,
                ],
            );

            $profile->permissions()->sync(collect($keys)->map(fn (string $key) => $all[$key])->all());
        }
    }

    private function descriptionFor(string $name): string
    {
        return match ($name) {
            'Administrador Estadual' => 'Acesso total, usuarios, perfis, auditoria, indicadores e todos os municipios.',
            'Gestor Municipal' => 'Gestao dos casos e usuarios do municipio.',
            'Tecnico CRAS' => 'Atendimento social, familias, encaminhamentos e acompanhamento municipal.',
            'Tecnico CREAS' => 'Protecao especial, violacoes e casos criticos autorizados.',
            'SESAI / Saude' => 'Atendimentos e fluxos de saude restritos ao orgao.',
            'FUNAI' => 'Acompanhamento territorial e mediacoes institucionais.',
            'Casa de Transito' => 'Entradas, saidas, permanencia e retorno ao territorio.',
            'Visualizacao Institucional' => 'Dashboards e relatorios sem edicao operacional.',
            default => 'Auditoria de movimentacoes, uso e erros do sistema.',
        };
    }
}
