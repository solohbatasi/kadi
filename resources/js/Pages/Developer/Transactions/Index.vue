<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    transactions: Array,
});
</script>

<template>
    <AppLayout title="Transactions">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Transactions</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Recent payment and payout activity for your merchant account.</p>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-[#0d111a] dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Transaction</th>
                                <th class="px-6 py-3">Reference</th>
                                <th class="px-6 py-3">Amount</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                            <tr v-for="transaction in transactions" :key="transaction.public_id">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ transaction.public_id }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ transaction.type }} / {{ transaction.direction }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ transaction.reference || transaction.receipt || '-' }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ transaction.currency }} {{ transaction.amount }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-[#252b3a] dark:text-gray-200">{{ transaction.status }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ new Date(transaction.created_at).toLocaleDateString() }}</td>
                            </tr>
                            <tr v-if="transactions.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">No transactions yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

