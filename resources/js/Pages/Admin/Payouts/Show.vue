<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ payout: Object, ledgerEntries: Array });
</script>

<template>
    <AdminLayout :title="payout.public_id">
        <div class="space-y-6">
            <section class="rounded-lg border bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold">{{ payout.public_id }} · {{ payout.status }}</h2>
                <p class="mt-2 text-sm text-gray-500">{{ payout.merchant?.business_name }} · {{ payout.phone }} · {{ payout.currency }} {{ payout.amount }}</p>
                <p v-if="payout.failure_reason" class="mt-2 text-sm text-red-600">{{ payout.failure_reason }}</p>
            </section>
            <pre class="overflow-auto rounded-lg bg-gray-100 p-4 text-xs dark:bg-[#0d111a]">{{ { payout, ledgerEntries } }}</pre>
        </div>
    </AdminLayout>
</template>

