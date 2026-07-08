<script setup>
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
const props = defineProps({ delivery: Object });
const retry = () => router.post(route('admin.webhook-deliveries.retry', props.delivery.id), {}, { preserveScroll: true });
</script>

<template>
    <AdminLayout title="Webhook Delivery">
        <div class="space-y-6">
            <section class="rounded-lg border bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold">{{ delivery.event }} · {{ delivery.status }}</h2>
                <p class="mt-2 text-sm text-gray-500">{{ delivery.merchant?.business_name }} · {{ delivery.url }}</p>
                <button v-if="delivery.status !== 'success'" class="mt-4 rounded-md border px-3 py-2 text-sm" @click="retry">Retry</button>
            </section>
            <pre class="overflow-auto rounded-lg bg-gray-100 p-4 text-xs dark:bg-[#0d111a]">{{ delivery }}</pre>
        </div>
    </AdminLayout>
</template>

