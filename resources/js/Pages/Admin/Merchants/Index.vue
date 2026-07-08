<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ merchants: Object, filters: Object });
const update = (merchant, action) => router.post(route(`admin.merchants.${action}`, merchant.id), {}, { preserveScroll: true });
</script>

<template>
    <AdminLayout title="Merchants">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-[#0d111a]">
                    <tr><th class="px-5 py-3">Merchant</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Wallet</th><th class="px-5 py-3">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <tr v-for="merchant in merchants.data" :key="merchant.public_id">
                        <td class="px-5 py-4">
                            <Link :href="route('admin.merchants.show', merchant.id)" class="font-medium text-violet-700 dark:text-violet-300">{{ merchant.business_name }}</Link>
                            <p class="text-xs text-gray-500">{{ merchant.public_id }} · {{ merchant.business_email }}</p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-300">{{ merchant.status }} · {{ merchant.compliance_status }} · live: {{ merchant.live_enabled ? 'yes' : 'no' }}</td>
                        <td class="px-5 py-4">{{ merchant.wallet?.currency || 'KES' }} {{ merchant.wallet?.available_balance || 0 }}</td>
                        <td class="space-x-2 px-5 py-4">
                            <button class="rounded-md border px-2 py-1 text-xs" @click="update(merchant, 'activate')">Activate</button>
                            <button class="rounded-md border px-2 py-1 text-xs" @click="update(merchant, 'suspend')">Suspend</button>
                            <button class="rounded-md border px-2 py-1 text-xs" @click="update(merchant, merchant.live_enabled ? 'disable-live' : 'enable-live')">{{ merchant.live_enabled ? 'Disable live' : 'Enable live' }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

