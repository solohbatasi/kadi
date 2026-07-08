<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ deliveries: Object, filters: Object });
const retry = (delivery) => router.post(route('admin.webhook-deliveries.retry', delivery.id), {}, { preserveScroll: true });
</script>

<template>
    <AdminLayout title="Webhook Deliveries">
        <div class="overflow-hidden rounded-lg border bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-[#0d111a]">
                    <tr><th class="px-5 py-3">Delivery</th><th class="px-5 py-3">Merchant</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Attempts</th><th class="px-5 py-3">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <tr v-for="delivery in deliveries.data" :key="delivery.id">
                        <td class="px-5 py-4"><Link :href="route('admin.webhook-deliveries.show', delivery.id)" class="font-medium text-violet-700 dark:text-violet-300">{{ delivery.event }}</Link><p class="text-xs text-gray-500">{{ delivery.url }}</p></td>
                        <td class="px-5 py-4">{{ delivery.merchant?.business_name }}</td>
                        <td class="px-5 py-4">{{ delivery.status }} · {{ delivery.status_code || '-' }}</td>
                        <td class="px-5 py-4">{{ delivery.attempts }}</td>
                        <td class="px-5 py-4"><button v-if="delivery.status !== 'success'" class="rounded-md border px-2 py-1 text-xs" @click="retry(delivery)">Retry</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

