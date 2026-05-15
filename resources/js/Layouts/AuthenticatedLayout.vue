<script setup>
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Bell,
    Building2,
    ClipboardList,
    FileText,
    Home,
    House,
    Landmark,
    LockKeyhole,
    MapPinned,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';

const showingNavigationDropdown = ref(false);
const page = usePage();

const permissions = computed(() => page.props.auth.user?.permissions ?? []);
const can = (items) => items.some((permission) => permissions.value.includes(permission));

const navigation = computed(() =>
    [
        {
            label: 'Dashboard',
            href: route('dashboard'),
            active: route().current('dashboard'),
            icon: Home,
            permissions: ['dashboard.view'],
        },
        {
            label: 'Casos',
            href: route('cases.index'),
            active: route().current('cases.*'),
            icon: ClipboardList,
            permissions: ['cases.view_all', 'cases.view_municipality', 'cases.view_orgao', 'cases.view_assigned'],
        },
        {
            label: 'Pessoas',
            href: route('resources.index', 'pessoas'),
            active: route().current('resources.index') && route().params.module === 'pessoas',
            icon: Users,
            permissions: ['people.view'],
        },
        {
            label: 'Familias',
            href: route('resources.index', 'familias'),
            active: route().current('resources.index') && route().params.module === 'familias',
            icon: Users,
            permissions: ['people.view'],
        },
        {
            label: 'Atendimentos',
            href: route('resources.index', 'atendimentos'),
            active: route().current('resources.index') && route().params.module === 'atendimentos',
            icon: FileText,
            permissions: ['care_records.view'],
        },
        {
            label: 'Encaminhamentos',
            href: route('resources.index', 'encaminhamentos'),
            active: route().current('resources.index') && route().params.module === 'encaminhamentos',
            icon: Activity,
            permissions: ['referrals.view'],
        },
        {
            label: 'Alertas',
            href: route('resources.index', 'alertas'),
            active: route().current('resources.index') && route().params.module === 'alertas',
            icon: Bell,
            permissions: ['alerts.view'],
        },
        {
            label: 'Casa de transito',
            href: route('resources.index', 'casa-transito'),
            active: route().current('resources.index') && route().params.module === 'casa-transito',
            icon: House,
            permissions: ['transit_house.view'],
        },
        {
            label: 'Anexos',
            href: route('resources.index', 'anexos'),
            active: route().current('resources.index') && route().params.module === 'anexos',
            icon: FileText,
            permissions: ['attachments.manage'],
        },
        {
            label: 'Orgaos',
            href: route('resources.index', 'orgaos'),
            active: route().current('resources.index') && route().params.module === 'orgaos',
            icon: Landmark,
            permissions: ['organizations.manage'],
        },
        {
            label: 'Municipios',
            href: route('resources.index', 'municipios'),
            active: route().current('resources.index') && route().params.module === 'municipios',
            icon: MapPinned,
            permissions: ['organizations.manage'],
        },
        {
            label: 'Comunidades',
            href: route('resources.index', 'comunidades'),
            active: route().current('resources.index') && route().params.module === 'comunidades',
            icon: Building2,
            permissions: ['people.view', 'organizations.manage'],
        },
        {
            label: 'Usuarios',
            href: route('users.index'),
            active: route().current('users.*'),
            icon: ShieldCheck,
            permissions: ['users.manage', 'users.manage_municipality'],
        },
        {
            label: 'Perfis',
            href: route('profiles.index'),
            active: route().current('profiles.*'),
            icon: LockKeyhole,
            permissions: ['profiles.manage'],
        },
        {
            label: 'Auditoria',
            href: route('audit.index'),
            active: route().current('audit.*'),
            icon: Activity,
            permissions: ['audit.view'],
        },
    ].filter((item) => can(item.permissions)),
);
</script>

<template>
    <div class="min-h-screen bg-gray-100 text-gray-900">
        <aside
            class="fixed inset-y-0 left-0 hidden w-72 border-r border-gray-200 bg-white lg:flex lg:flex-col"
        >
            <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-5">
                <ApplicationLogo />
                <div>
                    <div class="text-sm font-black uppercase tracking-normal text-gray-900">SIAY</div>
                    <div class="text-xs text-gray-500">Acompanhamento Yanomami</div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <Link
                    v-for="item in navigation"
                    :key="item.label"
                    :href="item.href"
                    class="flex items-center gap-3 rounded px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        item.active
                            ? 'bg-emerald-700 text-white'
                            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-950'
                    "
                >
                    <component :is="item.icon" class="h-4 w-4 shrink-0" />
                    <span class="truncate">{{ item.label }}</span>
                </Link>
            </nav>

            <div class="border-t border-gray-200 p-4">
                <div class="text-sm font-semibold text-gray-900">{{ $page.props.auth.user.name }}</div>
                <div class="truncate text-xs text-gray-500">{{ $page.props.auth.user.profile }}</div>
            </div>
        </aside>

        <div class="lg:pl-72">
            <nav class="sticky top-0 z-30 border-b border-gray-200 bg-white">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 lg:hidden">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded p-2 text-gray-500 hover:bg-gray-100"
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                        >
                            <span class="sr-only">Menu</span>
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>
                        <ApplicationLogo />
                        <span class="text-sm font-black">SIAY</span>
                    </div>

                    <div class="hidden lg:block">
                        <slot name="eyebrow" />
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <div class="text-sm font-semibold text-gray-900">{{ $page.props.auth.user.name }}</div>
                            <div class="text-xs text-gray-500">{{ $page.props.auth.user.municipio || 'Acesso estadual' }}</div>
                        </div>
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded border border-gray-200 bg-white text-sm font-bold text-gray-700 hover:bg-gray-50"
                                >
                                    {{ $page.props.auth.user.name.substring(0, 1) }}
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Perfil</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Sair</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <div v-if="showingNavigationDropdown" class="border-t border-gray-200 bg-white lg:hidden">
                    <div class="space-y-1 px-3 py-3">
                        <ResponsiveNavLink
                            v-for="item in navigation"
                            :key="item.label"
                            :href="item.href"
                            :active="item.active"
                        >
                            {{ item.label }}
                        </ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <header v-if="$slots.header" class="border-b border-gray-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div
                    v-if="$page.props.flash.success"
                    class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                >
                    {{ $page.props.flash.success }}
                </div>
                <div
                    v-if="$page.props.flash.error"
                    class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"
                >
                    {{ $page.props.flash.error }}
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
