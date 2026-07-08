<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ wallet: Object, ledgerEntries: Object, filters: Object });
</script>

<template>
    <AdminLayout title="Wallet Ledger">
        <div class="space-y-6">
            <section class="rounded-lg border bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold">{{ wallet.merchant?.business_name }}</h2>
                <p class="mt-2 text-sm text-gray-500">Available {{ wallet.currency }} {{ wallet.available_balance }} · Pending {{ wallet.pending_balance }}</p>
            </section>
            <div class="overflow-hidden rounded-lg border bg-white dark:border-[#232837] dark:bg-[#11141b]">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                    <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                        <tr v-for="entry in ledgerEntries.data" :key="entry.public_id">
                            <td class="px-5 py-4">{{ entry.public_id }}<p class="text-xs text-gray-500">{{ entry.entry_type }} · {{ entry.direction }}</p></td>
                            <td class="px-5 py-4">{{ entry.amount }}</td>
                            <td class="px-5 py-4">Balance after {{ entry.balance_after }}</td>
                            <td class="px-5 py-4">{{ entry.description }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

