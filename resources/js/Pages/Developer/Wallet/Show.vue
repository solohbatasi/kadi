<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    wallet: Object,
});
</script>

<template>
    <AppLayout title="Wallet Overview">
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Current balances</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-md bg-slate-50 p-5 dark:bg-[#141824]">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Available</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ wallet.available_balance }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 p-5 dark:bg-[#141824]">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pending</p>
                        <p class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ wallet.pending_balance }}</p>
                    </div>
                </div>
                <p class="mt-5 text-sm text-gray-500 dark:text-gray-400">Currency: {{ wallet.currency }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Wallet details</h2>
                <div class="mt-6 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <p><span class="font-semibold text-gray-900 dark:text-white">Public ID:</span> {{ wallet.public_id || 'Not assigned' }}</p>
                    <p><span class="font-semibold text-gray-900 dark:text-white">Supported currency:</span> {{ wallet.currency }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ledger events will be recorded for all credits and debits.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
