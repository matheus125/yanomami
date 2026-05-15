<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Save } from 'lucide-vue-next';

const props = defineProps({
    profiles: Array,
    permissions: Object,
});

const selectedId = ref(props.profiles[0]?.id ?? null);
const selectedProfile = computed(() => props.profiles.find((profile) => profile.id === selectedId.value));

const form = useForm({
    permissions: selectedProfile.value?.permissions?.map((permission) => permission.id) ?? [],
});

watch(selectedProfile, (profile) => {
    form.permissions = profile?.permissions?.map((permission) => permission.id) ?? [];
    form.clearErrors();
});

const togglePermission = (permissionId) => {
    if (form.permissions.includes(permissionId)) {
        form.permissions = form.permissions.filter((id) => id !== permissionId);
        return;
    }

    form.permissions.push(permissionId);
};

const submit = () => {
    form.put(route('profiles.update', selectedProfile.value.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Perfis e permissoes" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-950">Perfis e permissoes</h1>
                <p class="mt-1 text-sm text-gray-600">Controle quais telas e operacoes cada perfil pode acessar.</p>
            </div>
        </template>

        <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
            <aside class="rounded border border-gray-200 bg-white p-4 shadow-sm lg:sticky lg:top-24 lg:self-start">
                <h2 class="px-2 text-sm font-bold uppercase text-gray-500">Perfis</h2>
                <div class="mt-3 space-y-1">
                    <button
                        v-for="profile in profiles"
                        :key="profile.id"
                        type="button"
                        class="w-full rounded px-3 py-3 text-left text-sm transition"
                        :class="profile.id === selectedId ? 'bg-emerald-700 text-white' : 'text-gray-700 hover:bg-gray-100'"
                        @click="selectedId = profile.id"
                    >
                        <div class="font-bold">{{ profile.name }}</div>
                        <div class="mt-1 line-clamp-2 text-xs" :class="profile.id === selectedId ? 'text-emerald-50' : 'text-gray-500'">
                            {{ profile.description }}
                        </div>
                    </button>
                </div>
            </aside>

            <section class="rounded border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-950">{{ selectedProfile?.name }}</h2>
                            <p class="text-sm text-gray-600">{{ selectedProfile?.description }}</p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60"
                            :disabled="form.processing || !selectedProfile"
                            @click="submit"
                        >
                            <Save class="h-4 w-4" />
                            Salvar
                        </button>
                    </div>
                </div>

                <div class="grid gap-5 p-5 xl:grid-cols-2">
                    <div v-for="(items, module) in permissions" :key="module" class="rounded border border-gray-200 p-4">
                        <h3 class="font-bold text-gray-950">{{ module }}</h3>
                        <div class="mt-3 space-y-2">
                            <label
                                v-for="permission in items"
                                :key="permission.id"
                                class="flex cursor-pointer items-start gap-3 rounded px-2 py-2 hover:bg-gray-50"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-1 rounded border-gray-300 text-emerald-700 focus:ring-emerald-600"
                                    :checked="form.permissions.includes(permission.id)"
                                    @change="togglePermission(permission.id)"
                                />
                                <span>
                                    <span class="block text-sm font-semibold text-gray-800">{{ permission.name }}</span>
                                    <span class="block text-xs text-gray-500">{{ permission.key }}</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
