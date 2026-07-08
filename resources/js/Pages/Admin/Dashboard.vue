<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({ stats: Object, recentTransactions: Array, recentPayouts: Array });
</script>

<template>
    <AdminLayout title="Admin Overview">
        <div class="space-y-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="(value, key) in stats" :key="key" class="rounded-lg border border-gray-200 bg-white p-5 dark:border-[#232837] dark:bg-[#11141b]">
                    <p class="text-xs uppercase text-gray-500">{{ key.replaceAll('_', ' ') }}</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ value }}</p>
                </div>
            </div>
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                    <h2 class="border-b border-gray-200 px-5 py-4 text-sm font-semibold dark:border-[#232837]">Recent transactions</h2>
                    <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                        <div v-for="transaction in recentTransactions" :key="transaction.public_id" class="px-5 py-3 text-sm">
                            <p class="font-medium">{{ transaction.public_id }} · {{ transaction.status }}</p>
                            <p class="text-gray-500">{{ transaction.merchant?.business_name }} · {{ transaction.currency }} {{ transaction.amount }}</p>
                        </div>
                    </div>
                </section>
                <section class="rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                    <h2 class="border-b border-gray-200 px-5 py-4 text-sm font-semibold dark:border-[#232837]">Recent payouts</h2>
                    <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                        <div v-for="payout in recentPayouts" :key="payout.public_id" class="px-5 py-3 text-sm">
                            <p class="font-medium">{{ payout.public_id }} · {{ payout.status }}</p>
                            <p class="text-gray-500">{{ payout.merchant?.business_name }} · {{ payout.currency }} {{ payout.amount }}</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

