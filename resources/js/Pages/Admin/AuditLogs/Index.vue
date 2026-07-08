<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ logs: Object, filters: Object });
</script>

<template>
    <AdminLayout title="Audit Logs">
        <div class="overflow-hidden rounded-lg border bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-[#0d111a]">
                    <tr><th class="px-5 py-3">Action</th><th class="px-5 py-3">Merchant</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Metadata</th><th class="px-5 py-3">Time</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <tr v-for="log in logs.data" :key="log.id">
                        <td class="px-5 py-4 font-medium">{{ log.action }}</td>
                        <td class="px-5 py-4">{{ log.merchant?.business_name || '-' }}</td>
                        <td class="px-5 py-4">{{ log.user?.email || '-' }}</td>
                        <td class="px-5 py-4"><pre class="max-w-md overflow-auto text-xs">{{ log.metadata }}</pre></td>
                        <td class="px-5 py-4">{{ log.created_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

