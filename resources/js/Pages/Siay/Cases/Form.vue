<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Plus, Save, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    mode: String,
    caseRecord: Object,
    options: Object,
});

const editing = computed(() => props.mode === 'edit');

const form = useForm({
    tipo_caso: props.caseRecord?.tipo_caso ?? 'Individual',
    descricao_resumida: props.caseRecord?.descricao_resumida ?? '',
    status_caso: props.caseRecord?.status_caso ?? 'Aberto',
    grau_risco: props.caseRecord?.grau_risco ?? 'Medio',
    orgao_responsavel_id: props.caseRecord?.orgao_responsavel_id ?? '',
    usuario_responsavel_id: props.caseRecord?.usuario_responsavel_id ?? '',
    municipio_id: props.caseRecord?.municipio_id ?? '',
    comunidade_id: props.caseRecord?.comunidade_id ?? '',
    sigiloso: Boolean(props.caseRecord?.sigiloso ?? false),
    motivo_encerramento: props.caseRecord?.motivo_encerramento ?? '',
    justificativa_encerramento: props.caseRecord?.justificativa_encerramento ?? '',
    pessoas:
        props.caseRecord?.pessoas?.map((person) => ({
            id: person.id,
            papel_no_caso: person.pivot?.papel_no_caso ?? '',
        })) ?? [],
});

const filteredCommunities = computed(() => {
    if (!form.municipio_id) return props.options.comunidades;
    return props.options.comunidades.filter((item) => item.municipio_id === form.municipio_id);
});

const addPerson = () => {
    form.pessoas.push({ id: '', papel_no_caso: '' });
};

const removePerson = (index) => {
    form.pessoas.splice(index, 1);
};

const submit = () => {
    if (editing.value) {
        form.put(route('cases.update', props.caseRecord.id));
        return;
    }

    form.post(route('cases.store'));
};

const personLabel = (person) => person.nome_civil || person.nome_tradicional || person.id;
</script>

<template>
    <Head :title="editing ? 'Editar caso' : 'Novo caso'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-950">{{ editing ? 'Editar caso' : 'Novo caso' }}</h1>
                    <p class="mt-1 text-sm text-gray-600">Dados obrigatorios, classificacao de risco, responsaveis e envolvidos.</p>
                </div>
                <Link :href="route('cases.index')" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">Voltar para casos</Link>
            </div>
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <section class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-gray-950">Identificacao do caso</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Tipo de caso</span>
                        <select v-model="form.tipo_caso" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option>Individual</option>
                            <option>Familiar</option>
                            <option>Coletivo</option>
                        </select>
                        <span v-if="form.errors.tipo_caso" class="text-xs text-red-600">{{ form.errors.tipo_caso }}</span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Status</span>
                        <select v-model="form.status_caso" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option>Aberto</option>
                            <option>Em acompanhamento</option>
                            <option>Fechado</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Grau de risco</span>
                        <select v-model="form.grau_risco" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option>Alto</option>
                            <option>Medio</option>
                            <option>Baixo</option>
                        </select>
                    </label>
                    <label class="flex items-center gap-3 rounded border border-gray-200 px-3 py-3">
                        <input v-model="form.sigiloso" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600" />
                        <span class="text-sm font-semibold text-gray-700">Caso sigiloso</span>
                    </label>
                </div>

                <label class="mt-4 block">
                    <span class="text-sm font-semibold text-gray-700">Resumo do caso</span>
                    <textarea
                        v-model="form.descricao_resumida"
                        rows="5"
                        class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                    />
                    <span v-if="form.errors.descricao_resumida" class="text-xs text-red-600">{{ form.errors.descricao_resumida }}</span>
                </label>
            </section>

            <section class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-gray-950">Territorio e responsabilidade</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Municipio</span>
                        <select v-model="form.municipio_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Selecione</option>
                            <option v-for="item in options.municipios" :key="item.id" :value="item.id">{{ item.nome_municipio }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Comunidade/Aldeia</span>
                        <select v-model="form.comunidade_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Nao informada</option>
                            <option v-for="item in filteredCommunities" :key="item.id" :value="item.id">{{ item.nome_comunidade }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Orgao responsavel</span>
                        <select v-model="form.orgao_responsavel_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Selecione</option>
                            <option v-for="item in options.orgaos" :key="item.id" :value="item.id">{{ item.nome_orgao }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Tecnico responsavel</span>
                        <select v-model="form.usuario_responsavel_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Selecione</option>
                            <option v-for="item in options.users" :key="item.id" :value="item.id">{{ item.name }} - {{ item.email }}</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-bold text-gray-950">Pessoas vinculadas</h2>
                    <button type="button" class="inline-flex items-center gap-2 rounded border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="addPerson">
                        <Plus class="h-4 w-4" />
                        Adicionar
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    <div v-for="(person, index) in form.pessoas" :key="index" class="grid gap-3 rounded bg-gray-50 p-3 md:grid-cols-[1fr_1fr_auto]">
                        <select v-model="person.id" class="rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Selecione uma pessoa</option>
                            <option v-for="item in options.pessoas" :key="item.id" :value="item.id">{{ personLabel(item) }}</option>
                        </select>
                        <input
                            v-model="person.papel_no_caso"
                            class="rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                            placeholder="Papel no caso"
                        />
                        <button type="button" class="inline-flex items-center justify-center rounded border border-red-200 px-3 text-red-700 hover:bg-red-50" @click="removePerson(index)">
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                    <p v-if="form.pessoas.length === 0" class="rounded bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                        Nenhuma pessoa vinculada neste momento.
                    </p>
                </div>
            </section>

            <section v-if="form.status_caso === 'Fechado'" class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-gray-950">Encerramento</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="text-sm font-semibold text-gray-700">Motivo</span>
                        <input v-model="form.motivo_encerramento" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" />
                    </label>
                    <label>
                        <span class="text-sm font-semibold text-gray-700">Justificativa</span>
                        <textarea v-model="form.justificativa_encerramento" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" />
                    </label>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('cases.index')" class="inline-flex justify-center rounded border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancelar
                </Link>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60"
                    :disabled="form.processing"
                >
                    <Save class="h-4 w-4" />
                    Salvar caso
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
