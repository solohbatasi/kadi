<script setup>
import { Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
defineProps({ callbacks: Object, filters: Object });
</script>

<template>
    <AdminLayout title="M-Pesa Callbacks">
        <div class="overflow-hidden rounded-lg border bg-white dark:border-[#232837] dark:bg-[#11141b]">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-[#232837]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-[#0d111a]">
                    <tr><th class="px-5 py-3">Callback</th><th class="px-5 py-3">Transaction</th><th class="px-5 py-3">Result</th><th class="px-5 py-3">Processed</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <tr v-for="callback in callbacks.data" :key="callback.id">
                        <td class="px-5 py-4"><Link :href="route('admin.mpesa-callbacks.show', callback.id)" class="font-medium text-violet-700 dark:text-violet-300">{{ callback.checkout_request_id }}</Link></td>
                        <td class="px-5 py-4">{{ callback.transaction_public_id }}</td>
                        <td class="px-5 py-4">{{ callback.result_code }} · {{ callback.result_description }}</td>
                        <td class="px-5 py-4">{{ callback.processed_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

