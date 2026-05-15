<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Edit, Save, Search, UserX, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
    profiles: Array,
    orgaos: Array,
    canManageAll: Boolean,
});

const editingUser = ref(null);
const search = ref(props.filters.search ?? '');

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    position: '',
    orgao_id: '',
    access_profile_id: '',
    municipio: '',
    active: true,
});

const submitSearch = () => {
    router.get(route('users.index'), { search: search.value }, { preserveState: true, replace: true });
};

const resetForm = () => {
    editingUser.value = null;
    form.defaults({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        phone: '',
        position: '',
        orgao_id: '',
        access_profile_id: '',
        municipio: '',
        active: true,
    });
    form.reset();
    form.clearErrors();
};

const startEdit = (user) => {
    editingUser.value = user;
    form.defaults({
        name: user.name ?? '',
        email: user.email ?? '',
        password: '',
        password_confirmation: '',
        phone: user.phone ?? '',
        position: user.position ?? '',
        orgao_id: user.orgao_id ?? '',
        access_profile_id: user.access_profile_id ?? '',
        municipio: user.municipio ?? '',
        active: Boolean(user.active),
    });
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), { preserveScroll: true });
        return;
    }

    form.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const deactivate = (user) => {
    if (confirm(`Desativar ${user.name}? Esta acao ficara registrada na auditoria.`)) {
        router.delete(route('users.destroy', user.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-950">Usuarios</h1>
                <p class="mt-1 text-sm text-gray-600">Cadastro operacional com perfil, orgao, municipio e status de acesso.</p>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-[1fr_400px]">
            <section class="space-y-4">
                <form class="flex gap-2 rounded border border-gray-200 bg-white p-4 shadow-sm" @submit.prevent="submitSearch">
                    <label class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-gray-400" />
                        <input v-model="search" type="search" class="w-full rounded border-gray-300 pl-9 text-sm focus:border-emerald-600 focus:ring-emerald-600" placeholder="Buscar por nome, email ou municipio" />
                    </label>
                    <button type="submit" class="rounded bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">Buscar</button>
                </form>

                <div class="overflow-hidden rounded border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-bold uppercase text-gray-500">
                                <tr>
                                    <th class="px-5 py-3">Usuario</th>
                                    <th class="px-5 py-3">Perfil</th>
                                    <th class="px-5 py-3">Orgao</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="user in users.data" :key="user.id">
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-gray-950">{{ user.name }}</div>
                                        <div class="text-xs text-gray-500">{{ user.email }}</div>
                                        <div class="text-xs text-gray-400">{{ user.municipio || 'Sem municipio' }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700">{{ user.profile?.name }}</td>
                                    <td class="px-5 py-4 text-gray-700">{{ user.orgao?.nome_orgao || '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded px-2 py-1 text-xs font-bold" :class="user.active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                                            {{ user.active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex justify-end gap-3">
                                            <button type="button" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:text-emerald-900" @click="startEdit(user)">
                                                <Edit class="h-4 w-4" />
                                                Editar
                                            </button>
                                            <button type="button" class="inline-flex items-center gap-1 text-sm font-semibold text-red-700 hover:text-red-900" @click="deactivate(user)">
                                                <UserX class="h-4 w-4" />
                                                Desativar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="px-5 py-10 text-center text-gray-500">Nenhum usuario encontrado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <Pagination :links="users.links" />
            </section>

            <aside class="rounded border border-gray-200 bg-white p-5 shadow-sm xl:sticky xl:top-24 xl:self-start">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-gray-950">{{ editingUser ? 'Editar usuario' : 'Novo usuario' }}</h2>
                    <button v-if="editingUser" type="button" class="rounded p-2 text-gray-500 hover:bg-gray-100" @click="resetForm">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Nome</span>
                        <input v-model="form.name" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" />
                        <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Email</span>
                        <input v-model="form.email" type="email" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" />
                        <span v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</span>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Senha</span>
                            <input v-model="form.password" type="password" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" />
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Confirmar senha</span>
                            <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" />
                        </label>
                    </div>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Perfil</span>
                        <select v-model="form.access_profile_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Selecione</option>
                            <option v-for="profile in profiles" :key="profile.id" :value="profile.id">{{ profile.name }}</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Orgao</span>
                        <select v-model="form.orgao_id" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="">Sem orgao</option>
                            <option v-for="orgao in orgaos" :key="orgao.id" :value="orgao.id">{{ orgao.nome_orgao }}</option>
                        </select>
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Cargo</span>
                            <input v-model="form.position" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" />
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold text-gray-700">Telefone</span>
                            <input v-model="form.phone" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" />
                        </label>
                    </div>
                    <label class="block">
                        <span class="text-sm font-semibold text-gray-700">Municipio</span>
                        <input v-model="form.municipio" :disabled="!canManageAll" class="mt-1 w-full rounded border-gray-300 text-sm focus:border-emerald-600 focus:ring-emerald-600 disabled:bg-gray-100" />
                    </label>
                    <label class="flex items-center gap-3 rounded border border-gray-200 px-3 py-2">
                        <input v-model="form.active" type="checkbox" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-600" />
                        <span class="text-sm font-semibold text-gray-700">Usuario ativo</span>
                    </label>

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60" :disabled="form.processing">
                        <Save class="h-4 w-4" />
                        Salvar usuario
                    </button>
                </form>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
