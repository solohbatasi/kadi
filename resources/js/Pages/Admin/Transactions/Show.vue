<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ transaction: Object, callbacks: Array, ledgerEntries: Array, webhookDeliveries: Array });
</script>

<template>
    <AdminLayout :title="transaction.public_id">
        <div class="space-y-6">
            <section class="rounded-lg border bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold">{{ transaction.public_id }} · {{ transaction.status }}</h2>
                <p class="mt-2 text-sm text-gray-500">{{ transaction.merchant?.business_name }} · {{ transaction.phone }} · {{ transaction.currency }} {{ transaction.amount }}</p>
                <p class="mt-2 text-sm">Commission {{ transaction.commission_amount }} · Net {{ transaction.net_amount }} · Receipt {{ transaction.mpesa_receipt_number || '-' }}</p>
            </section>
            <pre class="overflow-auto rounded-lg bg-gray-100 p-4 text-xs dark:bg-[#0d111a]">{{ { transaction, callbacks, ledgerEntries, webhookDeliveries } }}</pre>
        </div>
    </AdminLayout>
</template>

