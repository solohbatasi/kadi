<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#090c11] dark:text-gray-100">
        <header class="border-b border-gray-200 bg-white/90 backdrop-blur dark:border-[#232837] dark:bg-[#11141b]/95">
            <div class="mx-auto flex h-14 max-w-[1180px] items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="inline-flex size-8 items-center justify-center rounded-md bg-violet-500 text-white">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z" />
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">ISP SaaS</span>
                </div>

                <nav v-if="canLogin" class="flex items-center gap-2">
                    <ThemeToggle />
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-violet-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-400"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-md border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-blue-400 hover:text-blue-700 dark:border-[#2a3040] dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-white">
                            Log in
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="rounded-md bg-violet-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-400">
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <main class="mx-auto grid min-h-[calc(100vh-7rem)] max-w-[1180px] content-center gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
            <section>
                <div class="mb-5 inline-flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <span class="size-2 rounded-full bg-emerald-400" />
                    Built for ISP operations
                </div>

                <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-gray-950 sm:text-5xl dark:text-white">
                    Professional access control, sessions, and mobile API security.
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-7 text-gray-600 dark:text-gray-400">
                    Manage users, roles, permissions, account status, browser sessions, and Sanctum API tokens from one consistent Laravel and Vue powered workspace.
                </p>

                <div class="mt-7 flex flex-wrap gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400"
                    >
                        Open dashboard
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-md bg-violet-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-violet-400">
                            Log in
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="rounded-md border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-blue-400 hover:text-blue-700 dark:border-[#2a3040] dark:text-gray-300 dark:hover:border-blue-400 dark:hover:text-white">
                            Create account
                        </Link>
                    </template>
                </div>

                <div class="mt-8 grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-md border border-gray-200 bg-white p-3 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Laravel</p>
                        <p class="mt-1 text-sm font-bold text-violet-600 dark:text-violet-300">v{{ laravelVersion }}</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-white p-3 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Vue</p>
                        <p class="mt-1 text-sm font-bold text-[#42b883]">Inertia UI</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-white p-3 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Security</p>
                        <p class="mt-1 text-sm font-bold text-emerald-500">Enforced</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-white p-3 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">PHP</p>
                        <p class="mt-1 text-sm font-bold text-blue-500">v{{ phpVersion }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-md border border-gray-200 bg-white p-5 dark:border-[#273044] dark:bg-[#11141b]">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-[#232837]">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">System overview</h2>
                        <p class="mt-1 text-xs text-gray-500">Aligned admin experience</p>
                    </div>
                    <span class="rounded-md bg-emerald-500/10 px-2 py-1 text-xs font-semibold text-emerald-500">Healthy</span>
                </div>

                <div class="mt-5 space-y-3">
                    <div class="flex items-center justify-between rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Roles and permissions</span>
                        <span class="text-xs font-semibold text-violet-500">RBAC</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Browser sessions</span>
                        <span class="text-xs font-semibold text-blue-500">Tracked</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Mobile API tokens</span>
                        <span class="text-xs font-semibold text-[#42b883]">Sanctum</span>
                    </div>
                    <div class="flex items-center justify-between rounded-md border border-gray-200 p-3 dark:border-[#2a3040]">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Account suspension</span>
                        <span class="text-xs font-semibold text-emerald-500">Active</span>
                    </div>
                </div>
            </section>
        </main>

        <footer class="mx-auto flex max-w-[1180px] flex-col gap-1 border-t border-gray-200 px-4 py-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-[#232837]">
            <span>ISP SaaS v1.0.0</span>
            <span>Maintained by bAtasi</span>
        </footer>
    </div>
</template>
