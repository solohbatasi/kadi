<script setup>
const sections = [
    ['Data collected', 'TODO legal review: Merchant profile, API activity, payment transaction records, phone numbers, callback records, webhook deliveries, and audit logs may be processed.'],
    ['Data protection', 'Sensitive identifiers are encrypted where implemented. Access to operational screens should be limited to authorized staff.'],
    ['Retention', 'Define production retention windows for idempotency keys, logs, callbacks, invoices, payouts, and KYC records before launch.'],
    ['Processors', 'M-Pesa, email providers, hosting, queue, database, and logging providers may process data depending on deployment configuration.'],
];
</script>

<template>
    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-3xl font-bold">Privacy Policy</h1>
            <p class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">Placeholder content for legal review before production launch.</p>
            <section v-for="section in sections" :key="section[0]" class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">{{ section[0] }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ section[1] }}</p>
            </section>
        </div>
    </main>
</template>

