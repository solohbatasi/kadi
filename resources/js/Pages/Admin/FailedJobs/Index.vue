<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ jobs: Object });
</script>

<template>
    <AdminLayout title="Failed Jobs">
        <div class="overflow-hidden rounded-lg border bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-[#0d111a]">
                    <tr><th class="px-5 py-3">UUID</th><th class="px-5 py-3">Queue</th><th class="px-5 py-3">Exception</th><th class="px-5 py-3">Failed</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <tr v-for="job in jobs.data" :key="job.id">
                        <td class="px-5 py-4">{{ job.uuid }}</td>
                        <td class="px-5 py-4">{{ job.connection }} / {{ job.queue }}</td>
                        <td class="px-5 py-4">{{ job.exception_summary }}</td>
                        <td class="px-5 py-4">{{ job.failed_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

