<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    activity: Object,
    errors: Array,
    filters: Object,
});

const action = ref(props.filters.action ?? '');

const submit = () => {
    router.get(route('audit.index'), { action: action.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Auditoria" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-950">Auditoria</h1>
                <p class="mt-1 text-sm text-gray-600">Logs de atividades, usuario responsavel, IP e erros registrados pelo sistema.</p>
            </div>
        </template>

        <div class="space-y-6">
            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-4">
                    <form class="flex gap-2" @submit.prevent="submit">
                        <label class="relative flex-1">
                            <Search class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <input v-model="action" class="w-full rounded border-gray-300 pl-9 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Filtrar por acao. Ex: user_removed" />
                        </label>
                        <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Filtrar</button>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
                            <tr>
                                <th class="px-5 py-3">Data</th>
                                <th class="px-5 py-3">Usuario</th>
                                <th class="px-5 py-3">Acao</th>
                                <th class="px-5 py-3">Descricao</th>
                                <th class="px-5 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="log in activity.data" :key="log.id">
                                <td class="px-5 py-4 text-gray-600">{{ log.created_at }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-gray-950">{{ log.user?.name || 'Sistema' }}</div>
                                    <div class="text-xs text-gray-500">{{ log.user?.email }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded bg-gray-100 px-2 py-1 text-xs font-bold text-gray-700">{{ log.action }}</span>
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ log.description }}</td>
                                <td class="px-5 py-4 text-gray-600">{{ log.ip_address }}</td>
                            </tr>
                            <tr v-if="activity.data.length === 0">
                                <td colspan="5" class="px-5 py-10 text-center text-gray-500">Nenhum log encontrado.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    <Pagination :links="activity.links" />
                </div>
            </section>

            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="font-bold text-gray-950">Ultimos erros</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    <article v-for="error in errors" :key="error.id" class="p-5">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div class="font-bold text-red-800">{{ error.exception_class || 'Erro' }}</div>
                            <div class="text-xs text-gray-500">{{ error.created_at }}</div>
                        </div>
                        <p class="mt-2 text-sm text-gray-700">{{ error.message }}</p>
                        <div class="mt-2 text-xs text-gray-500">{{ error.file }}:{{ error.line }}</div>
                    </article>
                    <div v-if="errors.length === 0" class="p-8 text-center text-sm text-gray-500">Nenhum erro registrado.</div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
