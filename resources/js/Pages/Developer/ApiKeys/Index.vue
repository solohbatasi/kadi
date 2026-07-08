<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    apiKeys: Array,
});

const newKeyName = ref('New API Key');
const environment = ref('sandbox');
</script>

<template>
    <AppLayout title="API Keys">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">API Keys</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Create and revoke keys for your merchant account. Secret keys are shown only once.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-[#232837]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Existing keys</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                        <div v-for="key in apiKeys" :key="key.id" class="px-6 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ key.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ key.environment }} • {{ key.status }}</p>
                                </div>
                                <div class="text-right text-xs text-gray-500 dark:text-gray-400">{{ new Date(key.created_at).toLocaleDateString() }}</div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-sm">
                                <form :action="route('developer.api-keys.revoke', { apiKey: key.id })" method="post" class="inline">
                                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                    <button type="submit" class="rounded-md border border-red-200 px-3 py-2 text-red-600 hover:bg-red-50 dark:border-red-500/20 dark:text-red-300">Revoke</button>
                                </form>
                                <form :action="route('developer.api-keys.rotate', { apiKey: key.id })" method="post" class="inline">
                                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                    <button type="submit" class="rounded-md border border-gray-200 px-3 py-2 text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Rotate</button>
                                </form>
                                <form :action="route('developer.api-keys.destroy', { apiKey: key.id })" method="post" class="inline">
                                    <input type="hidden" name="_method" value="delete" />
                                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                    <button type="submit" class="rounded-md border border-gray-200 px-3 py-2 text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div v-if="apiKeys.length === 0" class="px-6 py-6 text-sm text-gray-500 dark:text-gray-400">
                            No API keys yet. Create one to begin.
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Create new key</h3>
                    <form :action="route('developer.api-keys.store')" method="post" class="mt-4 space-y-4">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input name="name" v-model="newKeyName" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Environment</label>
                            <select name="environment" v-model="environment" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100">
                                <option value="sandbox">Sandbox</option>
                                <option value="production">Production</option>
                            </select>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Create key</button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
