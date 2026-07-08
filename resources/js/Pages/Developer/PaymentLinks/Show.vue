<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    paymentLink: Object,
});

const title = ref(props.paymentLink.title);
const description = ref(props.paymentLink.description || '');
const amount = ref(props.paymentLink.amount || '');
const allowCustomAmount = ref(Boolean(props.paymentLink.allow_custom_amount));
const successRedirectUrl = ref(props.paymentLink.success_redirect_url || '');
</script>

<template>
    <AppLayout title="Payment Link">
        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ paymentLink.title }}</h2>
                <p class="mt-2 break-all text-sm text-gray-500 dark:text-gray-400">{{ paymentLink.public_url }}</p>

                <form :action="route('developer.payment-links.update', { paymentLink: paymentLink.public_id })" method="post" class="mt-6 space-y-4">
                    <input type="hidden" name="_method" value="put" />
                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                    <input type="hidden" name="allow_custom_amount" value="0" />
                    <input type="hidden" name="status" :value="paymentLink.status" />
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                        <input name="title" v-model="title" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                        <textarea name="description" v-model="description" rows="4" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100"></textarea>
                    </div>
                    <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300">
                        <input name="allow_custom_amount" type="checkbox" value="1" v-model="allowCustomAmount" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                        Allow custom amount
                    </label>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                        <input name="amount" v-model="amount" :disabled="allowCustomAmount" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 disabled:bg-gray-100 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100 dark:disabled:bg-[#171b26]" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Success redirect URL</label>
                        <input name="success_redirect_url" v-model="successRedirectUrl" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Save changes</button>
                </form>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Status</h3>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ paymentLink.status }}</p>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                    {{ paymentLink.allow_custom_amount ? 'Customer chooses amount' : `${paymentLink.currency} ${paymentLink.amount}` }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
