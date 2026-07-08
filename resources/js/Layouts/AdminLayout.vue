<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({ title: String });

const nav = [
    ['Overview', 'admin.dashboard'],
    ['Merchants', 'admin.merchants.*'],
    ['Transactions', 'admin.transactions.*'],
    ['Wallets / Ledger', 'admin.wallets.*'],
    ['Payouts', 'admin.payouts.*'],
    ['M-Pesa Callbacks', 'admin.mpesa-callbacks.*'],
    ['Webhook Deliveries', 'admin.webhook-deliveries.*'],
    ['Failed Jobs', 'admin.failed-jobs.*'],
    ['Audit Logs', 'admin.audit-logs.*'],
];

const hrefFor = (name) => route(name.replace('.*', '.index'));
const logout = () => router.post(route('logout'));
</script>

<template>
    <div>
        <Head :title="title" />
        <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#090c11] dark:text-gray-100">
            <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-gray-200 bg-white lg:block dark:border-[#232837] dark:bg-[#11141b]">
                <div class="flex h-14 items-center border-b border-gray-200 px-5 dark:border-[#232837]">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">PayGate Ops</p>
                </div>
                <nav class="space-y-1 px-3 py-4">
                    <Link
                        v-for="[label, name] in nav"
                        :key="name"
                        :href="hrefFor(name)"
                        class="block rounded-md px-3 py-2 text-sm font-medium transition"
                        :class="route().current(name) ? 'bg-violet-500/10 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-[#1a1f2f]'"
                    >
                        {{ label }}
                    </Link>
                </nav>
            </aside>

            <div class="lg:pl-64">
                <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 dark:border-[#232837] dark:bg-[#11141b]">
                    <div>
                        <h1 class="text-sm font-semibold text-gray-900 dark:text-white">{{ title }}</h1>
                        <p class="text-xs text-gray-500">Platform operations</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <ThemeToggle />
                        <button class="rounded-md border border-gray-200 px-3 py-1.5 text-sm text-gray-600 dark:border-[#2a3040] dark:text-gray-300" @click="logout">Log Out</button>
                    </div>
                </header>
                <div class="flex gap-2 overflow-x-auto border-b border-gray-200 bg-white px-4 py-2 lg:hidden dark:border-[#232837] dark:bg-[#11141b]">
                    <Link
                        v-for="[label, name] in nav"
                        :key="name"
                        :href="hrefFor(name)"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm"
                        :class="route().current(name) ? 'bg-violet-500/10 text-violet-700 dark:text-violet-300' : 'text-gray-500 dark:text-gray-400'"
                    >
                        {{ label }}
                    </Link>
                </div>
                <main class="mx-auto max-w-[1280px] px-4 py-6 sm:px-6">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
