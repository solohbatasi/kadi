<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ payouts: Object, filters: Object });
</script>

<template>
    <AdminLayout title="Payouts">
        <div class="overflow-hidden rounded-lg border bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-[#0d111a]">
                    <tr><th class="px-5 py-3">Payout</th><th class="px-5 py-3">Merchant</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Phone</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <tr v-for="payout in payouts.data" :key="payout.public_id">
                        <td class="px-5 py-4"><Link :href="route('admin.payouts.show', payout.public_id)" class="font-medium text-violet-700 dark:text-violet-300">{{ payout.public_id }}</Link></td>
                        <td class="px-5 py-4">{{ payout.merchant?.business_name }}</td>
                        <td class="px-5 py-4">{{ payout.currency }} {{ payout.amount }}</td>
                        <td class="px-5 py-4">{{ payout.status }}</td>
                        <td class="px-5 py-4">{{ payout.phone }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
