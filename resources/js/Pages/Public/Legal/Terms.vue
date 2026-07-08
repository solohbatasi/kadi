<script setup>
const sections = [
    ['Platform role', 'TODO legal review: This platform provides payment gateway tools for merchants and developers. It does not claim regulatory authorization in this placeholder text.'],
    ['Merchant responsibilities', 'Merchants are responsible for lawful use, accurate customer communication, refunds, disputes, taxes, and protecting API keys.'],
    ['Payment processing', 'Payment initiation, callbacks, wallet settlement, payouts, and webhooks are provided as technical services subject to configuration and provider availability.'],
    ['Refunds and disputes', 'TODO legal review: Add the final refund, reversal, chargeback, and dispute process before launch.'],
];
</script>

<template>
    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-3xl font-bold">Terms of Service</h1>
            <p class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">Placeholder content for legal review before production launch.</p>
            <section v-for="section in sections" :key="section[0]" class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">{{ section[0] }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ section[1] }}</p>
            </section>
        </div>
    </main>
</template>

