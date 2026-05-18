<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links?.length > 3" class="flex flex-wrap items-center gap-2">
        <Link
            v-for="link in links"
            :key="link.label"
            :href="link.url || '#'"
            class="rounded-md border px-3 py-1.5 text-sm"
            :class="[
                link.active ? 'border-emerald-500 bg-emerald-50 text-emerald-700 dark:border-violet-400 dark:bg-violet-500/10 dark:text-violet-200' : 'border-gray-200 text-gray-600 dark:border-gray-700 dark:text-gray-300',
                !link.url ? 'pointer-events-none opacity-40' : ''
            ]"
            v-html="link.label"
        />
    </div>
</template>
