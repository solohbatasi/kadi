<script setup>
const sections = [
    ['Prohibited use', 'Do not use the platform for fraud, illegal goods or services, abusive traffic, unauthorized collections, or attempts to bypass payment/provider controls.'],
    ['API security', 'Merchants must keep secret keys server-side, rotate suspected exposed keys, and verify webhook signatures using raw JSON payloads.'],
    ['Monitoring', 'The platform may monitor suspicious activity, failed webhooks, abnormal payment attempts, and payout failures for operational safety.'],
    ['Enforcement', 'TODO legal review: Add final suspension, termination, and appeal procedures before production launch.'],
];
</script>

<template>
    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-3xl font-bold">Acceptable Use</h1>
            <p class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">Placeholder content for legal review before production launch.</p>
            <section v-for="section in sections" :key="section[0]" class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">{{ section[0] }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ section[1] }}</p>
            </section>
        </div>
    </main>
</template>

