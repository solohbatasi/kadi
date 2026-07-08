<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    endpoint: Object,
    deliveries: Array,
});

const url = ref(props.endpoint?.url || '');
const isEnabled = ref(Boolean(props.endpoint?.is_enabled));
</script>

<template>
    <AppLayout title="Webhooks">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Webhooks</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Receive signed transaction events on your own endpoint.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-[#232837]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent deliveries</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                        <div v-for="delivery in deliveries" :key="`${delivery.event}-${delivery.created_at}`" class="px-6 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ delivery.event }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ delivery.url }}</p>
                                </div>
                                <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                    <p>{{ delivery.status }}</p>
                                    <p>{{ delivery.status_code || 'No response' }}</p>
                                </div>
                            </div>
                            <p v-if="delivery.error_message" class="mt-2 text-xs text-red-600 dark:text-red-300">{{ delivery.error_message }}</p>
                        </div>
                        <div v-if="deliveries.length === 0" class="px-6 py-6 text-sm text-gray-500 dark:text-gray-400">
                            No webhook deliveries yet.
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Endpoint settings</h3>
                        <form :action="route('developer.webhooks.update')" method="post" class="mt-4 space-y-4">
                            <input type="hidden" name="_method" value="put" />
                            <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                            <input type="hidden" name="is_enabled" value="0" />

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">URL</label>
                                <input name="url" v-model="url" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secret</label>
                                <input name="secret" type="password" autocomplete="new-password" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ endpoint.has_secret ? 'Secret is saved. Enter a new one only to rotate it.' : 'Add a secret before enabling deliveries.' }}</p>
                            </div>

                            <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                                <input name="is_enabled" type="checkbox" value="1" v-model="isEnabled" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                                Enabled
                            </label>

                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Save</button>
                        </form>
                    </div>

                    <form :action="route('developer.webhooks.test')" method="post" class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Test endpoint</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Queue a signed test transaction event.</p>
                        <button type="submit" class="mt-4 inline-flex items-center justify-center rounded-md border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Send test</button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
