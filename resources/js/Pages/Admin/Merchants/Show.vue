<script setup>
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ merchant: Object, recentTransactions: Array, recentPayouts: Array });
const post = (action) => router.post(route(`admin.merchants.${action}`, props.merchant.id), {}, { preserveScroll: true });
</script>

<template>
    <AdminLayout :title="merchant.business_name">
        <div class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">{{ merchant.business_name }}</h2>
                        <p class="text-sm text-gray-500">{{ merchant.public_id }} · {{ merchant.business_email }} · {{ merchant.business_phone }}</p>
                        <p class="mt-2 text-sm">Status: {{ merchant.status }} · Compliance: {{ merchant.compliance_status }} · Live: {{ merchant.live_enabled ? 'yes' : 'no' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-md border px-3 py-2 text-sm" @click="post('activate')">Activate</button>
                        <button class="rounded-md border px-3 py-2 text-sm" @click="post('suspend')">Suspend</button>
                        <button class="rounded-md border px-3 py-2 text-sm" @click="post('enable-live')">Enable live</button>
                        <button class="rounded-md border px-3 py-2 text-sm" @click="post('disable-live')">Disable live</button>
                        <button class="rounded-md border px-3 py-2 text-sm" @click="post('verify-compliance')">Verify compliance</button>
                        <button class="rounded-md border px-3 py-2 text-sm" @click="post('reject-compliance')">Reject compliance</button>
                    </div>
                </div>
            </section>
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg border bg-white p-5 dark:border-[#232837] dark:bg-[#11141b]">Wallet: {{ merchant.wallet?.currency }} {{ merchant.wallet?.available_balance || 0 }}</div>
                <div class="rounded-lg border bg-white p-5 dark:border-[#232837] dark:bg-[#11141b]">API keys: {{ merchant.api_keys_count }}</div>
                <div class="rounded-lg border bg-white p-5 dark:border-[#232837] dark:bg-[#11141b]">Webhook: {{ merchant.webhook?.is_enabled ? 'enabled' : 'off' }}</div>
            </div>
            <pre class="overflow-auto rounded-lg bg-gray-100 p-4 text-xs dark:bg-[#0d111a]">{{ merchant.profile }}</pre>
        </div>
    </AdminLayout>
</template>

