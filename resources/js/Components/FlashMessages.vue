<script setup>
import { computed } from 'vue';
import { AlertCircle, AlertTriangle, CheckCircle2, Info, XCircle } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const flashItems = computed(() =>
    [
        {
            key: 'success',
            icon: CheckCircle2,
            title: 'Sucesso',
            message: page.props.flash?.success,
            classes: 'border-emerald-200 bg-emerald-50 text-emerald-900',
        },
        {
            key: 'error',
            icon: XCircle,
            title: 'Erro',
            message: page.props.flash?.error,
            classes: 'border-red-200 bg-red-50 text-red-900',
        },
        {
            key: 'warning',
            icon: AlertTriangle,
            title: 'Atencao',
            message: page.props.flash?.warning,
            classes: 'border-amber-200 bg-amber-50 text-amber-900',
        },
        {
            key: 'info',
            icon: Info,
            title: 'Informacao',
            message: page.props.flash?.info,
            classes: 'border-sky-200 bg-sky-50 text-sky-900',
        },
        {
            key: 'status',
            icon: Info,
            title: 'Status',
            message: page.props.flash?.status,
            classes: 'border-sky-200 bg-sky-50 text-sky-900',
        },
    ].filter((item) => item.message),
);

const validationErrors = computed(() => Object.values(page.props.errors ?? {}).filter(Boolean));
</script>

<template>
    <div v-if="flashItems.length || validationErrors.length" class="mb-4 space-y-3">
        <div
            v-for="item in flashItems"
            :key="item.key"
            class="rounded border px-4 py-3 text-sm"
            :class="item.classes"
        >
            <div class="flex gap-3">
                <component :is="item.icon" class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    <div class="font-bold">{{ item.title }}</div>
                    <div>{{ item.message }}</div>
                </div>
            </div>
        </div>

        <div v-if="validationErrors.length" class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <div class="flex gap-3">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    <div class="font-bold">Corrija os campos destacados</div>
                    <ul class="mt-1 list-disc space-y-1 pl-4">
                        <li v-for="(message, index) in validationErrors" :key="index">{{ message }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
