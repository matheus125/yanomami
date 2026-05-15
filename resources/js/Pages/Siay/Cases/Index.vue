<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';
import { reactive } from 'vue';

const props = defineProps({
    cases: Object,
    filters: Object,
    can: Object,
});

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    risk: props.filters.risk ?? '',
});

const submit = () => {
    router.get(route('cases.index'), form, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.status = '';
    form.risk = '';
    submit();
};

const riskClass = (risk) =>
    ({
        Alto: 'bg-red-100 text-red-800',
        Medio: 'bg-amber-100 text-amber-800',
        Baixo: 'bg-emerald-100 text-emerald-800',
    })[risk] ?? 'bg-gray-100 text-gray-700';
</script>

<template>
    <Head title="Casos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-950">Casos</h1>
                    <p class="mt-1 text-sm text-gray-600">Nucleo do acompanhamento: risco, responsavel, municipio e historico vinculado.</p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('cases.create')"
                    class="inline-flex items-center justify-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800"
                >
                    <Plus class="h-4 w-4" />
                    Novo caso
                </Link>
            </div>
        </template>

        <form class="mb-5 grid gap-3 rounded border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_180px_180px_auto]" @submit.prevent="submit">
            <label class="relative">
                <Search class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-gray-400" />
                <input
                    v-model="form.search"
                    class="w-full rounded border-gray-300 pl-9 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                    placeholder="Buscar por numero ou resumo"
                    type="search"
                />
            </label>
            <select v-model="form.status" class="rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <option value="">Todos os status</option>
                <option>Aberto</option>
                <option>Em acompanhamento</option>
                <option>Fechado</option>
            </select>
            <select v-model="form.risk" class="rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                <option value="">Todos os riscos</option>
                <option>Alto</option>
                <option>Medio</option>
                <option>Baixo</option>
            </select>
            <div class="flex gap-2">
                <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filtrar</button>
                <button type="button" class="rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="clearFilters">
                    Limpar
                </button>
            </div>
        </form>

        <section class="overflow-hidden rounded border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
                        <tr>
                            <th class="px-5 py-3">Caso</th>
                            <th class="px-5 py-3">Municipio</th>
                            <th class="px-5 py-3">Responsavel</th>
                            <th class="px-5 py-3">Risco</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="caseRecord in cases.data" :key="caseRecord.id" class="align-top">
                            <td class="px-5 py-4">
                                <div class="font-bold text-gray-950">{{ caseRecord.numero_caso }}</div>
                                <div class="mt-1 line-clamp-2 max-w-md text-gray-600">{{ caseRecord.descricao_resumida }}</div>
                                <div v-if="caseRecord.sigiloso" class="mt-2 text-xs font-bold text-rose-700">Sigiloso</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                {{ caseRecord.municipio?.nome_municipio }}
                                <div class="text-xs text-gray-400">{{ caseRecord.comunidade?.nome_comunidade }}</div>
                            </td>
                            <td class="px-5 py-4 text-gray-600">
                                {{ caseRecord.orgao_responsavel?.nome_orgao }}
                                <div class="text-xs text-gray-400">{{ caseRecord.usuario_responsavel?.name }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded px-2 py-1 text-xs font-bold" :class="riskClass(caseRecord.grau_risco)">
                                    {{ caseRecord.grau_risco }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-gray-600">{{ caseRecord.status_caso }}</td>
                            <td class="px-5 py-4 text-right">
                                <Link :href="route('cases.show', caseRecord.id)" class="font-semibold text-emerald-700 hover:text-emerald-900">
                                    Abrir
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="cases.data.length === 0">
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">Nenhum caso encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="mt-5">
            <Pagination :links="cases.links" />
        </div>
    </AuthenticatedLayout>
</template>
