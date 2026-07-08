<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ wallets: Object, filters: Object });
</script>

<template>
    <AdminLayout title="Wallets / Ledger">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-[#0d111a]">
                    <tr><th class="px-5 py-3">Wallet</th><th class="px-5 py-3">Merchant</th><th class="px-5 py-3">Available</th><th class="px-5 py-3">Pending</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <tr v-for="wallet in wallets.data" :key="wallet.id">
                        <td class="px-5 py-4"><Link :href="route('admin.wallets.show', wallet.id)" class="font-medium text-violet-700 dark:text-violet-300">{{ wallet.public_id || wallet.id }}</Link></td>
                        <td class="px-5 py-4">{{ wallet.merchant?.business_name }}</td>
                        <td class="px-5 py-4">{{ wallet.currency }} {{ wallet.available_balance }}</td>
                        <td class="px-5 py-4">{{ wallet.currency }} {{ wallet.pending_balance }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

