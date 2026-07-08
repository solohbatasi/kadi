<script setup>
const sections = [
    ['API keys', 'Secret API keys must never be placed in frontend code. Use sandbox keys for testing and production keys only after live approval.'],
    ['Webhooks', 'Verify X-PayGate-Signature, X-PayGate-Event, and X-PayGate-Timestamp using the raw JSON body and timing-safe comparison.'],
    ['Operations', 'Queue workers, scheduler, failed job monitoring, backups, security checks, and callback URL secrecy are required before production launch.'],
    ['Incident response', 'TODO legal/security review: Add final incident reporting and response contacts before launch.'],
];
</script>

<template>
    <main class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
        <div class="mx-auto max-w-3xl space-y-6">
            <h1 class="text-3xl font-bold">Security</h1>
            <p class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">Placeholder content for security and legal review before production launch.</p>
            <section v-for="section in sections" :key="section[0]" class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="font-semibold">{{ section[0] }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ section[1] }}</p>
            </section>
        </div>
    </main>
</template>

