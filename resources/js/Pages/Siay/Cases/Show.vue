<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Edit, FileText, Route, ShieldAlert } from 'lucide-vue-next';

const props = defineProps({
    caseRecord: Object,
    can: Object,
});

const riskClass = (risk) =>
    ({
        Alto: 'bg-red-100 text-red-800',
        Medio: 'bg-amber-100 text-amber-800',
        Baixo: 'bg-emerald-100 text-emerald-800',
    })[risk] ?? 'bg-gray-100 text-gray-700';
</script>

<template>
    <Head :title="caseRecord.numero_caso" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold text-gray-950">{{ caseRecord.numero_caso }}</h1>
                        <span class="rounded px-2 py-1 text-xs font-bold" :class="riskClass(caseRecord.grau_risco)">{{ caseRecord.grau_risco }}</span>
                        <span v-if="caseRecord.sigiloso" class="rounded bg-rose-100 px-2 py-1 text-xs font-bold text-rose-800">Sigiloso</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">{{ caseRecord.status_caso }} - {{ caseRecord.tipo_caso }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('cases.index')" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Voltar
                    </Link>
                    <Link
                        v-if="can.update"
                        :href="route('cases.edit', caseRecord.id)"
                        class="inline-flex items-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800"
                    >
                        <Edit class="h-4 w-4" />
                        Editar
                    </Link>
                </div>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-[1.1fr_.9fr]">
            <section class="space-y-6">
                <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-gray-950">Resumo</h2>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">{{ caseRecord.descricao_resumida }}</p>
                </div>

                <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-gray-950">Pessoas vinculadas</h2>
                    <div class="mt-4 divide-y divide-gray-100">
                        <div v-for="person in caseRecord.pessoas" :key="person.id" class="py-3">
                            <div class="font-semibold text-gray-950">{{ person.nome_civil || person.nome_tradicional || 'Pessoa sem nome informado' }}</div>
                            <div class="text-sm text-gray-500">{{ person.pivot?.papel_no_caso || 'Sem papel definido' }}</div>
                        </div>
                        <div v-if="caseRecord.pessoas.length === 0" class="py-6 text-center text-sm text-gray-500">Nenhuma pessoa vinculada.</div>
                    </div>
                </div>

                <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <FileText class="h-5 w-5 text-emerald-700" />
                        <h2 class="font-bold text-gray-950">Atendimentos</h2>
                    </div>
                    <div class="mt-4 space-y-3">
                        <article v-for="item in caseRecord.atendimentos" :key="item.id" class="rounded bg-gray-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-semibold text-gray-950">{{ item.tipo_atendimento }}</div>
                                <div class="text-xs text-gray-500">{{ item.data_atendimento }}</div>
                            </div>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ item.descricao }}</p>
                            <p v-if="item.proximo_passo" class="mt-2 text-sm font-medium text-gray-600">Proximo passo: {{ item.proximo_passo }}</p>
                        </article>
                        <p v-if="caseRecord.atendimentos.length === 0" class="rounded bg-gray-50 py-6 text-center text-sm text-gray-500">Sem atendimentos registrados.</p>
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-gray-950">Responsabilidade</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="font-semibold text-gray-500">Municipio</dt>
                            <dd class="text-gray-900">{{ caseRecord.municipio?.nome_municipio }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Comunidade</dt>
                            <dd class="text-gray-900">{{ caseRecord.comunidade?.nome_comunidade || 'Nao informada' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Orgao responsavel</dt>
                            <dd class="text-gray-900">{{ caseRecord.orgao_responsavel?.nome_orgao }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-gray-500">Tecnico responsavel</dt>
                            <dd class="text-gray-900">{{ caseRecord.usuario_responsavel?.name }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <Route class="h-5 w-5 text-indigo-700" />
                        <h2 class="font-bold text-gray-950">Encaminhamentos</h2>
                    </div>
                    <div class="mt-4 space-y-3">
                        <article v-for="item in caseRecord.encaminhamentos" :key="item.id" class="rounded bg-gray-50 p-3">
                            <div class="text-sm font-bold text-gray-950">{{ item.status_encaminhamento }}</div>
                            <div class="mt-1 text-xs text-gray-500">
                                {{ item.orgao_origem?.nome_orgao }} -> {{ item.orgao_destino?.nome_orgao }}
                            </div>
                            <p class="mt-2 text-sm text-gray-700">{{ item.motivo }}</p>
                        </article>
                        <p v-if="caseRecord.encaminhamentos.length === 0" class="rounded bg-gray-50 py-6 text-center text-sm text-gray-500">Sem encaminhamentos.</p>
                    </div>
                </div>

                <div class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <ShieldAlert class="h-5 w-5 text-red-700" />
                        <h2 class="font-bold text-gray-950">Alertas</h2>
                    </div>
                    <div class="mt-4 space-y-3">
                        <article v-for="item in caseRecord.alertas" :key="item.id" class="rounded bg-gray-50 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-bold text-gray-950">{{ item.tipo_alerta }}</span>
                                <span class="rounded px-2 py-1 text-xs font-bold" :class="riskClass(item.nivel_alerta)">{{ item.nivel_alerta }}</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-700">{{ item.descricao }}</p>
                        </article>
                        <p v-if="caseRecord.alertas.length === 0" class="rounded bg-gray-50 py-6 text-center text-sm text-gray-500">Sem alertas.</p>
                    </div>
                </div>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
