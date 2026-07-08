<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const props = defineProps({
    title: String,
    actions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const sidebarOpen = ref(localStorage.getItem('sidebar') !== 'closed');

const navigation = computed(() => [
    { name: 'Dashboard', route: 'developer.dashboard', href: route('developer.dashboard'), icon: 'dashboard' },
    { name: 'Onboarding', route: 'developer.onboarding.*', href: route('developer.onboarding.index'), icon: 'activity' },
    { name: 'API Keys', route: 'developer.api-keys.*', href: route('developer.api-keys.index'), icon: 'key' },
    { name: 'Wallet', route: 'developer.wallet.*', href: route('developer.wallet.overview'), icon: 'wallet' },
    { name: 'Transactions', route: 'developer.transactions.*', href: route('developer.transactions.index'), icon: 'transactions' },
    { name: 'Payment Links', route: 'developer.payment-links.*', href: route('developer.payment-links.index'), icon: 'link' },
    { name: 'Invoices', route: 'developer.invoices.*', href: route('developer.invoices.index'), icon: 'invoice' },
    { name: 'Payouts', route: 'developer.payouts.*', href: route('developer.payouts.index'), icon: 'payout' },
    { name: 'Webhooks', route: 'developer.webhooks.*', href: route('developer.webhooks.show'), icon: 'webhook' },
    { name: 'API Docs', route: 'developer.docs.*', href: route('developer.docs.index'), icon: 'docs' },
    { name: 'Users', route: 'admin.users.*', href: route('admin.users.index'), icon: 'users' },
    { name: 'Roles', route: 'admin.roles.*', href: route('admin.roles.index'), icon: 'shield' },
    { name: 'Permissions', route: 'admin.permissions.*', href: route('admin.permissions.index'), icon: 'key' },
    { name: 'System Health', route: 'admin.system-health', href: route('admin.system-health'), icon: 'activity' },
    page.props.jetstream.hasApiFeatures ? { name: 'API Tokens', route: 'api-tokens.index', href: route('api-tokens.index'), icon: 'mobile' } : null,
].filter(Boolean));

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
    localStorage.setItem('sidebar', sidebarOpen.value ? 'open' : 'closed');
};

const logout = () => router.post(route('logout'));
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#090c11] dark:text-gray-100">
            <aside
                class="fixed inset-y-0 left-0 z-40 hidden border-r border-gray-200 bg-white transition-all duration-200 lg:flex lg:flex-col dark:border-[#232837] dark:bg-[#11141b]"
                :class="sidebarOpen ? 'w-60' : 'w-[72px]'"
            >
                <div class="flex h-14 items-center gap-3 border-b border-gray-200 px-5 dark:border-[#232837]">
                    <div class="inline-flex size-7 items-center justify-center rounded-md bg-violet-500 text-white">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z" />
                        </svg>
                    </div>
                    <span v-if="sidebarOpen" class="text-sm font-bold text-gray-900 dark:text-white">ISP SaaS</span>
                </div>

                <div class="flex-1 overflow-y-auto px-2 py-4">
                    <p v-if="sidebarOpen" class="px-3 pb-3 text-[11px] font-medium uppercase tracking-wider text-gray-500">Menu</p>
                    <nav class="space-y-1">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            class="group flex h-9 items-center gap-3 rounded-md px-3 text-sm font-medium transition"
                            :class="route().current(item.route) ? 'bg-violet-500/10 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-[#1a1f2f] dark:hover:text-gray-100'"
                            :title="sidebarOpen ? null : item.name"
                        >
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path v-if="item.icon === 'dashboard'" stroke-linecap="round" stroke-linejoin="round" d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-4H4v4Z" />
                                <path v-else-if="item.icon === 'users'" stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1.5A3.5 3.5 0 0 0 12.5 14h-5A3.5 3.5 0 0 0 4 17.5V19m15 0v-1a3 3 0 0 0-2-2.83M13 5.17a3 3 0 0 1 0 5.66M10 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                                <path v-else-if="item.icon === 'shield'" stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-3.5 7-10V5l-7-2-7 2v6c0 6.5 7 10 7 10Z" />
                                <path v-else-if="item.icon === 'key'" stroke-linecap="round" stroke-linejoin="round" d="M15 7a4 4 0 1 1-1.2 2.86L4 19.66V22h3v-2h2v-2h2l2.14-2.14A4 4 0 0 1 15 7Z" />
                                <path v-else-if="item.icon === 'wallet'" stroke-linecap="round" stroke-linejoin="round" d="M4 7.5A2.5 2.5 0 0 1 6.5 5H19a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H6.5A2.5 2.5 0 0 1 4 17.5v-10Zm0 0A2.5 2.5 0 0 0 6.5 10H20m-4 4h.01" />
                                <path v-else-if="item.icon === 'transactions'" stroke-linecap="round" stroke-linejoin="round" d="M7 7h13l-3-3m3 3-3 3M17 17H4l3 3m-3-3 3-3" />
                                <path v-else-if="item.icon === 'link'" stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07L10.9 5.03m3.2 5.94a5 5 0 0 0-7.07 0L4.9 13.1a5 5 0 1 0 7.07 7.07l1.22-1.22" />
                                <path v-else-if="item.icon === 'invoice'" stroke-linecap="round" stroke-linejoin="round" d="M7 3h10a2 2 0 0 1 2 2v16l-3-1.5-3 1.5-3-1.5L7 21V5a2 2 0 0 1 2-2Zm3 6h6M10 13h6M10 17h3" />
                                <path v-else-if="item.icon === 'payout'" stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14" />
                                <path v-else-if="item.icon === 'webhook'" stroke-linecap="round" stroke-linejoin="round" d="M18 8a3 3 0 1 0-2.83-4H14a4 4 0 0 0-4 4v1m-4 7a3 3 0 1 0 2.83 4H10a4 4 0 0 0 4-4v-1m-8-3h12" />
                                <path v-else-if="item.icon === 'docs'" stroke-linecap="round" stroke-linejoin="round" d="M6 3h8l4 4v14H6V3Zm8 0v5h5M9 13h6M9 17h6" />
                                <path v-else-if="item.icon === 'activity'" stroke-linecap="round" stroke-linejoin="round" d="M4 13h4l2-7 4 14 2-7h4" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 2h6a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm2 17h2" />
                            </svg>
                            <span v-if="sidebarOpen">{{ item.name }}</span>
                        </Link>
                    </nav>
                </div>

                <div class="border-t border-gray-200 p-4 dark:border-[#232837]">
                    <div class="flex items-center gap-3">
                        <div class="inline-flex size-8 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700 dark:bg-[#252b3a] dark:text-gray-300">
                            {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                        </div>
                        <div v-if="sidebarOpen" class="min-w-0">
                            <p class="truncate text-xs font-semibold text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                        </div>
                    </div>
                    <button
                        v-if="sidebarOpen"
                        class="mt-3 h-7 w-full rounded-md border border-gray-200 text-xs text-gray-500 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-400 dark:hover:border-violet-500/50 dark:hover:text-white"
                        @click="logout"
                    >
                        Log Out
                    </button>
                </div>
            </aside>

            <div class="flex min-h-screen flex-col transition-all duration-200" :class="sidebarOpen ? 'lg:pl-60' : 'lg:pl-[72px]'">
                <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6 dark:border-[#232837] dark:bg-[#11141b]">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 transition hover:border-violet-400 hover:text-violet-700 dark:border-[#2a3040] dark:text-gray-400 dark:hover:border-violet-500/50 dark:hover:text-white"
                            title="Toggle sidebar"
                            @click="toggleSidebar"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-sm font-semibold text-gray-900 dark:text-white">{{ title }}</h1>
                            <p v-if="$slots.subtitle" class="text-xs text-gray-500"><slot name="subtitle" /></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <slot name="actions" />
                        <ThemeToggle />
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="inline-flex size-8 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-700 ring-1 ring-gray-200 transition hover:ring-violet-400 dark:bg-[#252b3a] dark:text-gray-200 dark:ring-[#2a3040] dark:hover:ring-violet-500/60">
                                    {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                                </button>
                            </template>

                            <template #content>
                                <div class="px-4 py-3">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700" />
                                <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                                <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">API Tokens</DropdownLink>
                                <div class="border-t border-gray-200 dark:border-gray-700" />
                                <form @submit.prevent="logout">
                                    <DropdownLink as="button">Log Out</DropdownLink>
                                </form>
                            </template>
                        </Dropdown>
                    </div>
                </header>

                <div class="flex gap-2 overflow-x-auto border-b border-gray-200 bg-white px-4 py-2 lg:hidden dark:border-[#232837] dark:bg-[#11141b]">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium"
                        :class="route().current(item.route) ? 'bg-violet-500/10 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300' : 'text-gray-500 dark:text-gray-400'"
                    >
                        {{ item.name }}
                    </Link>
                </div>

                <main class="mx-auto w-full max-w-[1180px] flex-1 px-4 py-6 sm:px-6">
                    <slot />
                </main>

                <footer class="mx-auto mt-auto flex w-full max-w-[1180px] flex-col gap-1 border-t border-gray-200 px-4 py-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-[#232837]">
                    <span>ISP SaaS v1.0.0</span>
                    <span>Maintained by bAtasi</span>
                </footer>
            </div>
        </div>
    </div>
</template>
