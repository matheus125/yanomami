<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-2">
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="!link.url"
                class="rounded border border-gray-200 px-3 py-2 text-sm text-gray-400"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                class="rounded border px-3 py-2 text-sm transition"
                :class="
                    link.active
                        ? 'border-emerald-700 bg-emerald-700 text-white'
                        : 'border-gray-200 bg-white text-gray-700 hover:border-emerald-600'
                "
                v-html="link.label"
            />
        </template>
    </nav>
</template>
