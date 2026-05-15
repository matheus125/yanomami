<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, ClipboardList, Clock, FileWarning, Route, ShieldAlert } from 'lucide-vue-next';

defineProps({
    stats: Object,
    riskBreakdown: Array,
    recentCases: Array,
    urgentAlerts: Array,
});

const riskClass = (risk) =>
    ({
        Alto: 'bg-red-100 text-red-800',
        Medio: 'bg-amber-100 text-amber-800',
        Baixo: 'bg-emerald-100 text-emerald-800',
    })[risk] ?? 'bg-gray-100 text-gray-700';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-950">Dashboard SIAY</h1>
                    <p class="mt-1 text-sm text-gray-600">Panorama operacional dos casos, riscos, pendencias e fluxos intersetoriais.</p>
                </div>
                <Link
                    v-if="$page.props.auth.user.permissions.includes('cases.create')"
                    :href="route('cases.create')"
                    class="inline-flex items-center justify-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800"
                >
                    Novo caso
                    <ArrowRight class="h-4 w-4" />
                </Link>
            </div>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">Total de casos</p>
                    <ClipboardList class="h-5 w-5 text-emerald-700" />
                </div>
                <p class="mt-3 text-3xl font-black text-gray-950">{{ stats.total_cases }}</p>
            </div>
            <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">Casos ativos</p>
                    <Clock class="h-5 w-5 text-blue-700" />
                </div>
                <p class="mt-3 text-3xl font-black text-gray-950">{{ stats.active_cases }}</p>
            </div>
            <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">Casos criticos</p>
                    <ShieldAlert class="h-5 w-5 text-red-700" />
                </div>
                <p class="mt-3 text-3xl font-black text-gray-950">{{ stats.critical_cases }}</p>
            </div>
            <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">Sem atualizacao 30 dias</p>
                    <FileWarning class="h-5 w-5 text-amber-700" />
                </div>
                <p class="mt-3 text-3xl font-black text-gray-950">{{ stats.stale_cases }}</p>
            </div>
            <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">Encaminhamentos pendentes</p>
                    <Route class="h-5 w-5 text-indigo-700" />
                </div>
                <p class="mt-3 text-3xl font-black text-gray-950">{{ stats.pending_referrals }}</p>
            </div>
            <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">Alertas abertos</p>
                    <AlertTriangle class="h-5 w-5 text-rose-700" />
                </div>
                <p class="mt-3 text-3xl font-black text-gray-950">{{ stats.open_alerts }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-gray-950">Casos recentes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Numero</th>
                                <th class="px-5 py-3">Municipio</th>
                                <th class="px-5 py-3">Risco</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="caseRecord in recentCases" :key="caseRecord.id">
                                <td class="px-5 py-3 font-semibold text-gray-950">{{ caseRecord.numero_caso }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ caseRecord.municipio?.nome_municipio }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded px-2 py-1 text-xs font-bold" :class="riskClass(caseRecord.grau_risco)">
                                        {{ caseRecord.grau_risco }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600">{{ caseRecord.status_caso }}</td>
                                <td class="px-5 py-3 text-right">
                                    <Link :href="route('cases.show', caseRecord.id)" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">
                                        Abrir
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="recentCases.length === 0">
                                <td colspan="5" class="px-5 py-8 text-center text-gray-500">Nenhum caso visivel para este perfil.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-gray-950">Risco por classificacao</h2>
                <div class="mt-4 space-y-3">
                    <div v-for="item in riskBreakdown" :key="item.grau_risco" class="flex items-center justify-between rounded bg-gray-50 px-4 py-3">
                        <span class="rounded px-2 py-1 text-xs font-bold" :class="riskClass(item.grau_risco)">{{ item.grau_risco }}</span>
                        <span class="text-lg font-black text-gray-950">{{ item.total }}</span>
                    </div>
                    <div v-if="riskBreakdown.length === 0" class="rounded bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        Sem dados para consolidar.
                    </div>
                </div>

                <h2 class="mt-6 font-bold text-gray-950">Alertas urgentes</h2>
                <div class="mt-4 space-y-3">
                    <div v-for="alert in urgentAlerts" :key="alert.id" class="rounded border border-red-100 bg-red-50 p-3">
                        <div class="text-sm font-bold text-red-900">{{ alert.tipo_alerta }}</div>
                        <div class="mt-1 text-sm text-red-800">{{ alert.caso?.numero_caso }}</div>
                    </div>
                    <div v-if="urgentAlerts.length === 0" class="rounded bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                        Nenhum alerta alto aberto.
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
