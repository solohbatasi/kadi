<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({ results: Object });
</script>

<template>
    <AdminLayout title="Pre-Live Check">
        <div class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Launch readiness checklist</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This read-only page mirrors `php artisan payments:prelive-check`.</p>
            </section>

            <section v-for="(items, section) in results" :key="section" class="rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ section.replaceAll('_', ' ') }}</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <div v-for="item in items" :key="item.label" class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                        <span class="text-gray-700 dark:text-gray-300">{{ item.label }}</span>
                        <span
                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="{
                                'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300': item.status === 'PASS',
                                'bg-amber-500/10 text-amber-700 dark:text-amber-300': item.status === 'WARN',
                                'bg-red-500/10 text-red-700 dark:text-red-300': item.status === 'FAIL',
                            }"
                        >
                            {{ item.status }}
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

