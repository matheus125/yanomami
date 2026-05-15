<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 px-4 pt-6 sm:justify-center sm:pt-0"
    >
        <div class="flex flex-col items-center gap-3 text-center">
            <Link href="/">
                <ApplicationLogo />
            </Link>
            <div>
                <h1 class="text-xl font-black text-gray-950">SIAY</h1>
                <p class="text-sm text-gray-600">Sistema Integrado de Acompanhamento Yanomami</p>
            </div>
        </div>

        <div
            class="mt-6 w-full overflow-hidden rounded border border-gray-200 bg-white px-6 py-5 shadow-sm sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
