<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'" class="inline-flex size-9 items-center justify-center rounded-md bg-violet-500 text-white">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z" />
        </svg>
    </Link>
</template>
