<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ metrics: Object, security: Array, sessions: Array, tokens: Array });

const revokeSession = (session) => {
    if (confirm('Revoke this browser session?')) router.delete(route('admin.system-health.sessions.destroy', session.id), { preserveScroll: true });
};
const revokeToken = (token) => {
    if (confirm('Revoke this API token?')) router.delete(route('admin.system-health.tokens.destroy', token.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="System Health">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Health</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Operational view of account security, sessions, role coverage, and Sanctum API token access.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="(value, key) in metrics" :key="key" class="rounded-md border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs font-semibold uppercase text-gray-500">{{ key.replaceAll('_', ' ') }}</p>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ value }}</p>
            </div>
        </div>

        <div class="mt-6 rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h3 class="font-semibold text-gray-900 dark:text-white">Security enforcement</h3>
            </div>
            <div class="grid divide-y divide-gray-100 dark:divide-gray-800">
                <div v-for="item in security" :key="item.name" class="grid gap-2 px-5 py-4 md:grid-cols-[240px_160px_1fr]">
                    <p class="font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                    <span class="w-fit rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-violet-500/10 dark:text-violet-200">{{ item.status }}</span>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ item.detail }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Frontend sessions</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="session in sessions" :key="session.id">
                            <td class="px-4 py-3"><p class="font-medium">{{ session.user }}</p><p class="text-xs text-gray-500">{{ session.ip_address }} - {{ session.last_activity }}</p></td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ session.user_agent }}</td>
                            <td class="px-4 py-3 text-right"><button class="text-red-600 dark:text-red-300" @click="revokeSession(session)">Revoke</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Mobile API tokens</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr v-for="token in tokens" :key="token.id">
                            <td class="px-4 py-3"><p class="font-medium">{{ token.name }}</p><p class="text-xs text-gray-500">{{ token.owner }} - Last used {{ token.last_used_at || 'never' }}</p></td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ token.abilities?.join(', ') || 'All abilities' }}</td>
                            <td class="px-4 py-3 text-right"><button class="text-red-600 dark:text-red-300" @click="revokeToken(token)">Revoke</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
