<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    merchant: Object,
});
</script>

<template>
    <AppLayout title="Developer Dashboard">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Merchant</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ merchant.business_name }}</p>
                <p class="mt-2 text-xs text-gray-500">{{ merchant.business_email }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Status</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ merchant.status }}</p>
                <p class="mt-2 text-xs text-emerald-400">Compliance: {{ merchant.compliance_status }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Environment</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">Sandbox</p>
                <p class="mt-2 text-xs text-gray-500">Live enabled: {{ merchant.live_enabled ? 'Yes' : 'No' }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Public ID</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ merchant.public_id }}</p>
                <p class="mt-2 text-xs text-gray-500">Keep this ID safe for API mapping.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Developer links</h2>
                <div class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <a :href="route('developer.api-keys.index')" class="block rounded-md border border-gray-200 px-4 py-3 hover:border-violet-500/70">Manage API keys</a>
                    <a :href="route('developer.wallet.overview')" class="block rounded-md border border-gray-200 px-4 py-3 hover:border-violet-500/70">View wallet</a>
                </div>
            </div>
            <div class="overflow-hidden rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Quick notes</h2>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-300">Create secret API keys and use them in the Authorization header with Bearer tokens. Only secret keys can access payment endpoints.</p>
            </div>
        </div>
    </AppLayout>
</template>
