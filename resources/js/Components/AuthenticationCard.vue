<script setup>
import { Link } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#090c11] dark:text-gray-100">
        <header class="border-b border-gray-200 bg-white/90 backdrop-blur dark:border-[#232837] dark:bg-[#11141b]/95">
            <div class="mx-auto flex h-14 max-w-[1180px] items-center justify-between px-4 sm:px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-3">
                    <div class="inline-flex size-8 items-center justify-center rounded-md bg-violet-500 text-white">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z" />
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">ISP SaaS</span>
                </Link>

                <ThemeToggle />
            </div>
        </header>

        <main class="mx-auto grid min-h-[calc(100vh-7rem)] max-w-[1180px] content-center gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[0.85fr_1fr] lg:items-center">
            <section class="hidden lg:block">
                <div class="mb-5 inline-flex items-center gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
                    Secure access
                </div>
                <h1 class="max-w-lg text-4xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Sign in to manage the ISP SaaS control plane.
                </h1>
                <p class="mt-5 max-w-lg text-sm leading-6 text-gray-600 dark:text-gray-400">
                    Laravel security, Vue interactions, Sanctum API access, and session-aware administration in one consistent workspace.
                </p>

                <div class="mt-7 grid max-w-lg grid-cols-2 gap-3">
                    <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Laravel</p>
                        <p class="mt-2 text-lg font-bold text-violet-500">Purple core</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Vue</p>
                        <p class="mt-2 text-lg font-bold text-[#42b883]">Reactive UI</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Sessions</p>
                        <p class="mt-2 text-lg font-bold text-blue-500">Tracked</p>
                    </div>
                    <div class="rounded-md border border-gray-200 bg-white p-4 dark:border-[#273044] dark:bg-[#11141b]">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Security</p>
                        <p class="mt-2 text-lg font-bold text-emerald-500">Enforced</p>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-md">
                <div class="rounded-md border border-gray-200 bg-white p-6 dark:border-[#273044] dark:bg-[#11141b]">
                    <div class="mb-6 flex items-center gap-3">
                        <slot name="logo" />
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Account access</h2>
                            <p class="text-xs text-gray-500">Protected by Laravel Jetstream</p>
                        </div>
                    </div>

                    <slot />
                </div>
            </section>
        </main>

        <footer class="mx-auto flex max-w-[1180px] flex-col gap-1 border-t border-gray-200 px-4 py-5 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-[#232837]">
            <span>ISP SaaS v1.0.0</span>
            <span>Maintained by bAtasi</span>
        </footer>
    </div>
</template>
