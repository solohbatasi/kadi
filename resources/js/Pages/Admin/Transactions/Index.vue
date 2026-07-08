<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ transactions: Object, filters: Object });
</script>

<template>
    <AdminLayout title="Transactions">
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-[#0d111a]">
                    <tr><th class="px-5 py-3">Transaction</th><th class="px-5 py-3">Merchant</th><th class="px-5 py-3">Amount</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Phone</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <tr v-for="transaction in transactions.data" :key="transaction.public_id">
                        <td class="px-5 py-4"><Link :href="route('admin.transactions.show', transaction.id)" class="font-medium text-violet-700 dark:text-violet-300">{{ transaction.public_id }}</Link><p class="text-xs text-gray-500">{{ transaction.type }} · {{ transaction.environment }}</p></td>
                        <td class="px-5 py-4">{{ transaction.merchant?.business_name }}</td>
                        <td class="px-5 py-4">{{ transaction.currency }} {{ transaction.amount }}</td>
                        <td class="px-5 py-4">{{ transaction.status }}</td>
                        <td class="px-5 py-4">{{ transaction.phone }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

