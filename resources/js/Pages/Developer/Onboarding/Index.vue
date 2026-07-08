<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ merchant: Object, readiness: Object });

const labels = {
    business_profile_completed: 'Business profile completed',
    contact_information_completed: 'Contact information completed',
    payout_configured: 'Payout phone or recipient configured',
    webhook_configured: 'Webhook configured',
    sandbox_api_key_created: 'Sandbox API key created',
    first_sandbox_transaction_attempted: 'First sandbox transaction attempted',
    compliance_submitted: 'Compliance/KYC submitted',
    compliance_verified: 'Compliance verified',
    live_mode_enabled: 'Live mode enabled by admin',
    terms_accepted: 'Terms and privacy accepted',
};

const requestLive = () => router.post(route('developer.live-mode.request'), {}, { preserveScroll: true });
</script>

<template>
    <AppLayout title="Onboarding">
        <div class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Live readiness</h2>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ readiness.completed }} of {{ readiness.total }} complete · {{ readiness.percent }}%</p>
                    </div>
                    <button :disabled="!readiness.can_request_live" class="rounded-md border px-4 py-2 text-sm disabled:cursor-not-allowed disabled:opacity-50" @click="requestLive">
                        Request live mode
                    </button>
                </div>
                <div v-if="readiness.blocking_reasons.length" class="mt-4 rounded-md bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                    <p v-for="reason in readiness.blocking_reasons" :key="reason">{{ reason }}</p>
                </div>
            </section>

            <section class="grid gap-3 md:grid-cols-2">
                <div v-for="(done, key) in readiness.items" :key="key" class="rounded-lg border border-gray-200 bg-white p-4 dark:border-[#232837] dark:bg-[#11141b]">
                    <p class="font-medium text-gray-900 dark:text-white">{{ labels[key] }}</p>
                    <p class="mt-1 text-sm" :class="done ? 'text-emerald-600 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400'">{{ done ? 'Complete' : 'Incomplete' }}</p>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <a :href="route('developer.compliance.edit')" class="rounded-md border px-4 py-2 text-sm">Submit compliance</a>
                <a :href="route('developer.api-keys.index')" class="rounded-md border px-4 py-2 text-sm">Create API key</a>
                <a :href="route('developer.webhooks.show')" class="rounded-md border px-4 py-2 text-sm">Configure webhooks</a>
            </div>
        </div>
    </AppLayout>
</template>

