<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    title: String,
});

const page = usePage();
const sidebarOpen = ref(localStorage.getItem('sidebar') !== 'closed');

const navigation = computed(() => [
    { name: 'Dashboard', route: 'dashboard', href: route('dashboard'), icon: 'dashboard' },
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

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />
        <Banner />

        <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-gray-950 dark:text-gray-100">
            <aside
                class="fixed inset-y-0 left-0 z-40 hidden border-r border-gray-200 bg-white transition-all duration-200 lg:block dark:border-gray-800 dark:bg-gray-900"
                :class="sidebarOpen ? 'w-72' : 'w-20'"
            >
                <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-5 dark:border-gray-800">
                    <Link :href="route('dashboard')" class="flex items-center gap-3">
                        <ApplicationMark class="block h-9 w-auto" />
                        <span v-if="sidebarOpen" class="text-sm font-bold tracking-wide text-gray-900 dark:text-white">ISP SaaS</span>
                    </Link>
                </div>

                <nav class="space-y-1 px-3 py-5">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        class="group flex h-11 items-center gap-3 rounded-md px-3 text-sm font-medium transition"
                        :class="route().current(item.route) ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100 dark:bg-violet-500/10 dark:text-violet-200 dark:ring-violet-400/20' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'"
                        :title="sidebarOpen ? null : item.name"
                    >
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path v-if="item.icon === 'dashboard'" stroke-linecap="round" stroke-linejoin="round" d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-4H4v4Z" />
                            <path v-else-if="item.icon === 'users'" stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1.5A3.5 3.5 0 0 0 12.5 14h-5A3.5 3.5 0 0 0 4 17.5V19m15 0v-1a3 3 0 0 0-2-2.83M13 5.17a3 3 0 0 1 0 5.66M10 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
                            <path v-else-if="item.icon === 'shield'" stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-3.5 7-10V5l-7-2-7 2v6c0 6.5 7 10 7 10Z" />
                            <path v-else-if="item.icon === 'key'" stroke-linecap="round" stroke-linejoin="round" d="M15 7a4 4 0 1 1-1.2 2.86L4 19.66V22h3v-2h2v-2h2l2.14-2.14A4 4 0 0 1 15 7Z" />
                            <path v-else-if="item.icon === 'activity'" stroke-linecap="round" stroke-linejoin="round" d="M4 13h4l2-7 4 14 2-7h4" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 2h6a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm2 17h2" />
                        </svg>
                        <span v-if="sidebarOpen">{{ item.name }}</span>
                    </Link>
                </nav>
            </aside>

            <div class="transition-all duration-200" :class="sidebarOpen ? 'lg:pl-72' : 'lg:pl-20'">
                <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
                    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex size-10 items-center justify-center rounded-md border border-gray-200 text-gray-600 transition hover:border-blue-300 hover:text-blue-700 dark:border-gray-700 dark:text-gray-300 dark:hover:border-violet-400"
                                title="Toggle sidebar"
                                @click="toggleSidebar"
                            >
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                                </svg>
                            </button>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-violet-300">Administration</p>
                                <h1 class="text-base font-semibold text-gray-900 dark:text-white">{{ title }}</h1>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <ThemeToggle />

                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2 py-1.5 text-sm text-gray-700 transition hover:border-emerald-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                        <img v-if="$page.props.jetstream.managesProfilePhotos" class="size-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                        <span class="hidden sm:block">{{ $page.props.auth.user.name }}</span>
                                    </button>
                                </template>

                                <template #content>
                                    <div class="block px-4 py-2 text-xs text-gray-400">Manage Account</div>
                                    <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                                    <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">API Tokens</DropdownLink>
                                    <div class="border-t border-gray-200 dark:border-gray-600" />
                                    <form @submit.prevent="logout">
                                        <DropdownLink as="button">Log Out</DropdownLink>
                                    </form>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <div class="flex gap-2 overflow-x-auto border-t border-gray-100 px-4 py-2 lg:hidden dark:border-gray-800">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium"
                            :class="route().current(item.route) ? 'bg-emerald-50 text-emerald-700 dark:bg-violet-500/10 dark:text-violet-200' : 'text-gray-600 dark:text-gray-300'"
                        >
                            {{ item.name }}
                        </Link>
                    </div>
                </header>

                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
