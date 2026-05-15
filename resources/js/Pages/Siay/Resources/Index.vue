<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Edit, Plus, Save, Search, X } from 'lucide-vue-next';

const props = defineProps({
    moduleKey: String,
    module: Object,
    records: Object,
    filters: Object,
    can: Object,
});

const editingRecord = ref(null);
const search = ref(props.filters.search ?? '');

const emptyValue = (field) => {
    if (field.type === 'boolean') return false;
    return '';
};

const buildPayload = (record = null) =>
    props.module.fields.reduce((payload, field) => {
        payload[field.name] = record ? formatForInput(record[field.name], field) : emptyValue(field);
        return payload;
    }, {});

const form = useForm(buildPayload());

watch(
    () => props.moduleKey,
    () => {
        editingRecord.value = null;
        form.defaults(buildPayload());
        form.reset();
        form.clearErrors();
    },
);

const title = computed(() => (editingRecord.value ? 'Editar registro' : 'Novo registro'));

function formatForInput(value, field) {
    if (value === null || value === undefined) return emptyValue(field);
    if (field.type === 'datetime-local') return String(value).slice(0, 16);
    if (field.type === 'date') return String(value).slice(0, 10);
    return value;
}

function optionLabel(field, value) {
    if (!field.options || value === null || value === undefined || value === '') return value || '-';
    return field.options.find((option) => option.value === value)?.label ?? value;
}

const submitSearch = () => {
    router.get(route('resources.index', props.moduleKey), { search: search.value }, { preserveState: true, replace: true });
};

const startCreate = () => {
    editingRecord.value = null;
    form.defaults(buildPayload());
    form.reset();
    form.clearErrors();
};

const startEdit = (record) => {
    editingRecord.value = record;
    form.defaults(buildPayload(record));
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingRecord.value) {
        form.put(route('resources.update', [props.moduleKey, editingRecord.value.id]), { preserveScroll: true });
        return;
    }

    form.post(route('resources.store', props.moduleKey), {
        preserveScroll: true,
        onSuccess: () => startCreate(),
    });
};
</script>

<template>
    <Head :title="module.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-950">{{ module.title }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ module.description }}</p>
                </div>
                <button
                    v-if="can.manage"
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                    @click="startCreate"
                >
                    <Plus class="h-4 w-4" />
                    Novo
                </button>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
            <section class="space-y-4">
                <form class="flex gap-2 rounded border border-gray-200 bg-white p-4 shadow-sm" @submit.prevent="submitSearch">
                    <label class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-gray-400" />
                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded border-gray-300 pl-9 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                            placeholder="Buscar registros"
                        />
                    </label>
                    <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Buscar</button>
                </form>

                <div class="overflow-hidden rounded border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
                                <tr>
                                    <th v-for="field in module.fields.slice(0, 5)" :key="field.name" class="px-5 py-3">{{ field.label }}</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="record in records.data" :key="record.id" class="align-top">
                                    <td v-for="field in module.fields.slice(0, 5)" :key="field.name" class="px-5 py-4 text-gray-700">
                                        <span v-if="field.type === 'boolean'" class="rounded px-2 py-1 text-xs font-bold" :class="record[field.name] ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                                            {{ record[field.name] ? 'Sim' : 'Nao' }}
                                        </span>
                                        <span v-else class="line-clamp-2">{{ optionLabel(field, record[field.name]) }}</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <button
                                            v-if="can.manage"
                                            type="button"
                                            class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-900"
                                            @click="startEdit(record)"
                                        >
                                            <Edit class="h-4 w-4" />
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="records.data.length === 0">
                                    <td :colspan="module.fields.slice(0, 5).length + 1" class="px-5 py-10 text-center text-gray-500">
                                        Nenhum registro encontrado.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination :links="records.links" />
            </section>

            <aside v-if="can.manage" class="rounded border border-gray-200 bg-white p-5 shadow-sm xl:sticky xl:top-24 xl:self-start">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-bold text-gray-950">{{ title }}</h2>
                    <button v-if="editingRecord" type="button" class="rounded p-2 text-gray-500 hover:bg-gray-100" @click="startCreate">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <label v-for="field in module.fields" :key="field.name" class="block">
                        <span class="text-sm font-semibold text-gray-700">{{ field.label }}</span>

                        <textarea
                            v-if="field.type === 'textarea'"
                            v-model="form[field.name]"
                            rows="4"
                            class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                        />

                        <select
                            v-else-if="field.type === 'select'"
                            v-model="form[field.name]"
                            class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                        >
                            <option value="">Selecione</option>
                            <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>

                        <span v-else-if="field.type === 'boolean'" class="mt-2 flex items-center gap-3 rounded border border-gray-200 px-3 py-2">
                            <input v-model="form[field.name]" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600" />
                            <span class="text-sm text-gray-700">Sim</span>
                        </span>

                        <input
                            v-else
                            v-model="form[field.name]"
                            :type="field.type === 'number' ? 'number' : field.type"
                            class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                        />

                        <span v-if="form.errors[field.name]" class="text-xs text-red-600">{{ form.errors[field.name] }}</span>
                    </label>

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        <Save class="h-4 w-4" />
                        Salvar
                    </button>
                </form>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
