<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Edit, Plus, Save, Search, Trash2, X } from 'lucide-vue-next';

const props = defineProps({
    moduleKey: String,
    module: Object,
    records: Object,
    filters: Object,
    can: Object,
});

const editingRecord = ref(null);
const search = ref(props.filters.search ?? '');
const diagnosisFilter = ref(props.filters.diagnostico ?? '');

const isFamiliesModule = computed(() => props.moduleKey === 'familias');

const diagnosisOptions = [
    'Inseguranca Alimentar Leve',
    'Inseguranca Alimentar Moderada',
    'Inseguranca Alimentar Grave',
];

const questionnaireOptions = {
    refeicoes_semana: ['1 vez', '2 vezes', '3 vezes', '4 vezes', '5 vezes', '6 vezes', '7 vezes'],
    refeicoes_dia: ['1 vez ao dia', '2 vezes ao dia', '3 vezes ao dia', '4 vezes ao dia', '5 vezes ou mais', 'Nenhuma'],
    preocupacao_falta_comida: ['Diariamente', 'Semanalmente', 'Apenas 1 vez ao mes', 'Raramente', 'Nunca'],
    reduziu_porcoes: ['Sim', 'Nao', 'Frequentemente'],
    fatores_limitantes: ['Falta de dinheiro', 'Falta de transporte', 'Falta de tempo para cozinhar', 'Falta de opcoes de alimentos saudaveis na regiao', 'Outros'],
    programas_assistencia: ['Bancos de alimentos', 'Programas de alimentacao escolar', 'Recebimento de cestas basicas', 'Cupons de alimentos', 'Outros'],
    locais_compra: ['Supermercados', 'Feiras', 'Tabernas', 'Outros'],
    alimentos_acessiveis: ['Sim', 'Nao'],
    frequencia_alimentos_baixo_custo: ['1 vez', '2 vezes', '3 vezes', '4 vezes', 'Acima de 4 vezes', 'Nunca'],
    alimentos_desejados: ['Leites e derivados', 'Carnes e ovos', 'Oleos e margarinas', 'Cereais e leguminosas', 'Hortalicas e frutas', 'Outros'],
    sim_nao: ['Sim', 'Nao'],
};

const questionnaireDefaults = () => ({
    refeicoes_semana: '',
    refeicoes_dia: '',
    preocupacao_falta_comida: '',
    reduziu_porcoes: '',
    fatores_limitantes: [],
    fatores_limitantes_outros: '',
    programas_assistencia: [],
    programas_assistencia_outros: '',
    locais_compra: [],
    locais_compra_outros: '',
    alimentos_acessiveis: '',
    frequencia_alimentos_baixo_custo: '',
    alimentos_desejados: [],
    alimentos_desejados_outros: '',
    situacao_financeira_afeta: '',
    participa_associacao: '',
    associacao_qual: '',
});

const emptyValue = (field) => {
    if (field.type === 'boolean') return false;
    return '';
};

const buildPayload = (record = null) => {
    const payload = props.module.fields.reduce((payload, field) => {
        payload[field.name] = record ? formatForInput(record[field.name], field) : emptyValue(field);
        return payload;
    }, {});

    if (props.moduleKey === 'familias') {
        payload.questionario = questionnaireForInput(record?.questionario_inseguranca_alimentar);
    }

    return payload;
};

const form = useForm(buildPayload());

watch(
    () => props.moduleKey,
    () => {
        editingRecord.value = null;
        diagnosisFilter.value = props.filters.diagnostico ?? '';
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

function questionnaireForInput(questionnaire = null) {
    const defaults = questionnaireDefaults();

    if (!questionnaire) return defaults;

    return Object.keys(defaults).reduce((payload, key) => {
        payload[key] = questionnaire[key] ?? defaults[key];
        return payload;
    }, {});
}

function diagnosisClass(diagnosis) {
    return (
        {
            'Inseguranca Alimentar Leve': 'bg-yellow-100 text-yellow-800',
            'Inseguranca Alimentar Moderada': 'bg-orange-100 text-orange-800',
            'Inseguranca Alimentar Grave': 'bg-red-100 text-red-800',
        }[diagnosis] ?? 'bg-gray-100 text-gray-700'
    );
}

function hasSelected(field, option = 'Outros') {
    return Array.isArray(form.questionario?.[field]) && form.questionario[field].includes(option);
}

function questionError(field) {
    return form.errors[`questionario.${field}`];
}

const questionnairePreview = computed(() => {
    if (!isFamiliesModule.value || !form.questionario) return null;

    let score = 0;
    const answer = form.questionario;

    score += { '7 vezes': 0, '5 vezes': 1, '6 vezes': 1, '3 vezes': 2, '4 vezes': 2, '1 vez': 3, '2 vezes': 3 }[answer.refeicoes_semana] ?? 0;
    score += { '3 vezes ao dia': 0, '4 vezes ao dia': 0, '5 vezes ou mais': 0, '2 vezes ao dia': 1, '1 vez ao dia': 2, Nenhuma: 4 }[answer.refeicoes_dia] ?? 0;
    score += { Nunca: 0, Raramente: 1, 'Apenas 1 vez ao mes': 1, Semanalmente: 2, Diariamente: 3 }[answer.preocupacao_falta_comida] ?? 0;
    score += { Nao: 0, Sim: 2, Frequentemente: 4 }[answer.reduziu_porcoes] ?? 0;
    score += Array.isArray(answer.fatores_limitantes) ? answer.fatores_limitantes.length : 0;
    score += hasSelected('fatores_limitantes', 'Falta de dinheiro') ? 1 : 0;
    score += Math.min(Array.isArray(answer.programas_assistencia) ? answer.programas_assistencia.length : 0, 2);
    score += answer.alimentos_acessiveis === 'Nao' ? 2 : 0;
    score += { Nunca: 0, '1 vez': 1, '2 vezes': 1, '3 vezes': 2, '4 vezes': 2, 'Acima de 4 vezes': 3 }[answer.frequencia_alimentos_baixo_custo] ?? 0;
    score += Math.min(Array.isArray(answer.alimentos_desejados) ? answer.alimentos_desejados.length : 0, 3);
    score += answer.situacao_financeira_afeta === 'Sim' ? 3 : 0;

    if (score <= 6) {
        return {
            score,
            diagnosis: 'Inseguranca Alimentar Leve',
            observation: 'Familia classificada com Inseguranca Alimentar Leve devido a limitacoes ocasionais no acesso a alimentos nutritivos.',
        };
    }

    if (score <= 14) {
        return {
            score,
            diagnosis: 'Inseguranca Alimentar Moderada',
            observation: 'Familia classificada com Inseguranca Alimentar Moderada devido a preocupacao recorrente com falta de alimentos, limitacao financeira e reducao da qualidade alimentar.',
        };
    }

    return {
        score,
        diagnosis: 'Inseguranca Alimentar Grave',
        observation: 'Familia classificada com Inseguranca Alimentar Grave devido a privacao frequente de refeicoes adequadas, inseguranca alimentar constante e dependencia de recursos assistenciais.',
    };
});

const submitSearch = () => {
    router.get(
        route('resources.index', props.moduleKey),
        { search: search.value, diagnostico: isFamiliesModule.value ? diagnosisFilter.value : undefined },
        { preserveState: true, replace: true },
    );
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

const destroyRecord = (record) => {
    if (confirm('Excluir este registro? Esta acao ficara registrada na auditoria.')) {
        router.delete(route('resources.destroy', [props.moduleKey, record.id]), {
            preserveScroll: true,
            onSuccess: () => {
                if (editingRecord.value?.id === record.id) {
                    startCreate();
                }
            },
        });
    }
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

        <div class="grid gap-6" :class="isFamiliesModule ? 'xl:grid-cols-[1fr_520px]' : 'xl:grid-cols-[1fr_380px]'">
            <section class="space-y-4">
                <form class="flex flex-col gap-2 rounded border border-gray-200 bg-white p-4 shadow-sm lg:flex-row" @submit.prevent="submitSearch">
                    <label class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-gray-400" />
                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded border-gray-300 pl-9 text-sm focus:border-emerald-600 focus:ring-emerald-600"
                            placeholder="Buscar registros"
                        />
                    </label>
                    <select
                        v-if="isFamiliesModule"
                        v-model="diagnosisFilter"
                        class="rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600 lg:w-64"
                    >
                        <option value="">Todos os diagnosticos</option>
                        <option v-for="option in diagnosisOptions" :key="option" :value="option">{{ option }}</option>
                    </select>
                    <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Buscar</button>
                </form>

                <div class="overflow-hidden rounded border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
                                <tr>
                                    <th v-for="field in module.fields.slice(0, 5)" :key="field.name" class="px-5 py-3">{{ field.label }}</th>
                                    <th v-if="isFamiliesModule" class="px-5 py-3">Diagnostico</th>
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
                                    <td v-if="isFamiliesModule" class="px-5 py-4 text-gray-700">
                                        <span
                                            v-if="record.questionario_inseguranca_alimentar?.diagnostico"
                                            class="inline-flex rounded px-2 py-1 text-xs font-bold"
                                            :class="diagnosisClass(record.questionario_inseguranca_alimentar.diagnostico)"
                                        >
                                            {{ record.questionario_inseguranca_alimentar.diagnostico }}
                                        </span>
                                        <span v-else class="text-sm text-gray-400">Sem questionario</span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-3">
                                        <button
                                            v-if="can.manage"
                                            type="button"
                                            class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-900"
                                            @click="startEdit(record)"
                                        >
                                            <Edit class="h-4 w-4" />
                                            Editar
                                        </button>
                                        <button
                                            v-if="can.manage"
                                            type="button"
                                            class="inline-flex items-center gap-2 text-sm font-semibold text-red-700 hover:text-red-900"
                                            @click="destroyRecord(record)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            Excluir
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="records.data.length === 0">
                                    <td :colspan="module.fields.slice(0, 5).length + (isFamiliesModule ? 2 : 1)" class="px-5 py-10 text-center text-gray-500">
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

                    <section v-if="isFamiliesModule" class="border-t border-gray-200 pt-5">
                        <div>
                            <h3 class="text-sm font-bold uppercase text-gray-900">Questionario de Analise de Inseguranca Alimentar e Nutricional</h3>
                        </div>

                        <div class="mt-4 space-y-5">
                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Quantas vezes por semana voce e sua familia tem acesso a refeicoes completas?</legend>
                                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    <label v-for="option in questionnaireOptions.refeicoes_semana" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.refeicoes_semana" type="radio" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <span v-if="questionError('refeicoes_semana')" class="text-xs text-red-600">{{ questionError('refeicoes_semana') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Quantas vezes ao dia voce e sua familia tem acesso a refeicoes completas?</legend>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label v-for="option in questionnaireOptions.refeicoes_dia" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.refeicoes_dia" type="radio" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <span v-if="questionError('refeicoes_dia')" class="text-xs text-red-600">{{ questionError('refeicoes_dia') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Com que frequencia voce se preocupa em nao ter comida suficiente para voce e sua familia?</legend>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label v-for="option in questionnaireOptions.preocupacao_falta_comida" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.preocupacao_falta_comida" type="radio" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <span v-if="questionError('preocupacao_falta_comida')" class="text-xs text-red-600">{{ questionError('preocupacao_falta_comida') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Voce ja teve que reduzir o tamanho das porcoes ou pular refeicoes por falta de recursos financeiros para comprar alimentos?</legend>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    <label v-for="option in questionnaireOptions.reduziu_porcoes" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.reduziu_porcoes" type="radio" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <span v-if="questionError('reduziu_porcoes')" class="text-xs text-red-600">{{ questionError('reduziu_porcoes') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Quais sao os principais fatores que limitam o acesso da sua familia a alimentos nutritivos?</legend>
                                <div class="mt-2 space-y-2">
                                    <label v-for="option in questionnaireOptions.fatores_limitantes" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.fatores_limitantes" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <textarea v-if="hasSelected('fatores_limitantes')" v-model="form.questionario.fatores_limitantes_outros" rows="2" class="mt-2 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Especifique outros fatores" />
                                <span v-if="questionError('fatores_limitantes_outros')" class="text-xs text-red-600">{{ questionError('fatores_limitantes_outros') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Voce ja teve que recorrer a programas de assistencia alimentar?</legend>
                                <div class="mt-2 space-y-2">
                                    <label v-for="option in questionnaireOptions.programas_assistencia" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.programas_assistencia" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <textarea v-if="hasSelected('programas_assistencia')" v-model="form.questionario.programas_assistencia_outros" rows="2" class="mt-2 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Especifique outros programas" />
                                <span v-if="questionError('programas_assistencia_outros')" class="text-xs text-red-600">{{ questionError('programas_assistencia_outros') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Quais sao os locais onde voce geralmente faz compras de alimentos?</legend>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label v-for="option in questionnaireOptions.locais_compra" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.locais_compra" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <textarea v-if="hasSelected('locais_compra')" v-model="form.questionario.locais_compra_outros" rows="2" class="mt-2 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Especifique outros locais" />
                                <span v-if="questionError('locais_compra_outros')" class="text-xs text-red-600">{{ questionError('locais_compra_outros') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Voce considera os alimentos disponiveis nos mercados e feiras acessiveis em termos de preco e qualidade?</legend>
                                <div class="mt-2 flex gap-4">
                                    <label v-for="option in questionnaireOptions.alimentos_acessiveis" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.alimentos_acessiveis" type="radio" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <span v-if="questionError('alimentos_acessiveis')" class="text-xs text-red-600">{{ questionError('alimentos_acessiveis') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Com que frequencia voce se ve obrigado(a) a comprar alimentos de baixo custo, mesmo que sejam menos nutritivos?</legend>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <label v-for="option in questionnaireOptions.frequencia_alimentos_baixo_custo" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.frequencia_alimentos_baixo_custo" type="radio" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <span v-if="questionError('frequencia_alimentos_baixo_custo')" class="text-xs text-red-600">{{ questionError('frequencia_alimentos_baixo_custo') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Existe algum tipo de alimento que voce gostaria de comprar com mais frequencia, mas nao consegue devido ao custo?</legend>
                                <div class="mt-2 space-y-2">
                                    <label v-for="option in questionnaireOptions.alimentos_desejados" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.alimentos_desejados" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <textarea v-if="hasSelected('alimentos_desejados')" v-model="form.questionario.alimentos_desejados_outros" rows="2" class="mt-2 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Especifique outros alimentos" />
                                <span v-if="questionError('alimentos_desejados_outros')" class="text-xs text-red-600">{{ questionError('alimentos_desejados_outros') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Voce acredita que sua situacao financeira afeta diretamente a qualidade e variedade dos alimentos que voce consome?</legend>
                                <div class="mt-2 flex gap-4">
                                    <label v-for="option in questionnaireOptions.sim_nao" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.situacao_financeira_afeta" type="radio" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <span v-if="questionError('situacao_financeira_afeta')" class="text-xs text-red-600">{{ questionError('situacao_financeira_afeta') }}</span>
                            </fieldset>

                            <fieldset>
                                <legend class="text-sm font-semibold text-gray-700">Voce participa como membro de alguma associacao comunitaria/OSC?</legend>
                                <div class="mt-2 flex gap-4">
                                    <label v-for="option in questionnaireOptions.sim_nao" :key="option" class="flex items-center gap-2 text-sm text-gray-700">
                                        <input v-model="form.questionario.participa_associacao" type="radio" class="border-gray-300 text-emerald-700 focus:ring-emerald-600" :value="option" />
                                        {{ option }}
                                    </label>
                                </div>
                                <input v-if="form.questionario.participa_associacao === 'Sim'" v-model="form.questionario.associacao_qual" type="text" class="mt-2 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Qual associacao?" />
                                <span v-if="questionError('associacao_qual')" class="text-xs text-red-600">{{ questionError('associacao_qual') }}</span>
                            </fieldset>

                            <div v-if="questionnairePreview" class="border-t border-gray-200 pt-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-700">Previa do diagnostico</span>
                                    <span class="rounded px-2 py-1 text-xs font-bold" :class="diagnosisClass(questionnairePreview.diagnosis)">
                                        {{ questionnairePreview.diagnosis }}
                                    </span>
                                    <span class="text-sm font-bold text-gray-950">{{ questionnairePreview.score }} pontos</span>
                                </div>
                                <textarea readonly :value="questionnairePreview.observation" rows="3" class="mt-2 w-full rounded border-gray-200 bg-gray-50 text-sm text-gray-700" />
                            </div>
                        </div>
                    </section>

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
