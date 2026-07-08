<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ merchant: Object, profile: Object });

const form = useForm({
    business_name: props.merchant.business_name || '',
    business_email: props.merchant.business_email || '',
    business_phone: '',
    business_type: props.merchant.business_type || '',
    platform_url: props.merchant.platform_url || '',
    owner_name: props.profile.owner_name || '',
    owner_email: props.profile.owner_email || '',
    owner_phone: '',
    document_type: props.profile.document_type || '',
    document_number: '',
    kra_pin: '',
    address: props.profile.address || '',
    payout_phone: '',
    accept_terms: false,
    accept_privacy: false,
});

const submit = () => form.post(route('developer.compliance.submit'));
</script>

<template>
    <AppLayout title="Compliance">
        <form class="space-y-6" @submit.prevent="submit">
            <section class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold">Compliance submission</h2>
                <p class="mt-2 text-sm text-gray-500">Sensitive identifiers are stored encrypted. Document upload is intentionally not enabled yet.</p>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <label v-for="field in ['business_name','business_email','business_phone','business_type','platform_url','owner_name','owner_email','owner_phone','document_type','document_number','kra_pin','address','payout_phone']" :key="field" class="block">
                        <span class="text-sm font-medium capitalize">{{ field.replaceAll('_', ' ') }}</span>
                        <input v-model="form[field]" class="mt-1 w-full rounded-md border-gray-300 dark:border-[#232837] dark:bg-[#0f1320]" :type="field.includes('email') ? 'email' : 'text'" />
                        <p v-if="form.errors[field]" class="mt-1 text-xs text-red-600">{{ form.errors[field] }}</p>
                    </label>
                </div>
                <label class="mt-4 flex gap-2 text-sm"><input v-model="form.accept_terms" type="checkbox" /> I accept the Terms.</label>
                <p v-if="form.errors.accept_terms" class="mt-1 text-xs text-red-600">{{ form.errors.accept_terms }}</p>
                <label class="mt-2 flex gap-2 text-sm"><input v-model="form.accept_privacy" type="checkbox" /> I accept the Privacy Policy.</label>
                <p v-if="form.errors.accept_privacy" class="mt-1 text-xs text-red-600">{{ form.errors.accept_privacy }}</p>
                <button class="mt-6 rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white" :disabled="form.processing">Submit for review</button>
            </section>
        </form>
    </AppLayout>
</template>

