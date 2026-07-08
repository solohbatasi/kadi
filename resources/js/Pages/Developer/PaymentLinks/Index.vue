<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    paymentLinks: Array,
});

const title = ref('');
const description = ref('');
const amount = ref('');
const allowCustomAmount = ref(false);
const successRedirectUrl = ref('');

const copy = async (value) => {
    if (navigator?.clipboard) {
        await navigator.clipboard.writeText(value);
    }
};
</script>

<template>
    <AppLayout title="Payment Links">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payment Links</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Create simple hosted payment pages for customers.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-[#232837]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Existing links</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                        <div v-for="link in paymentLinks" :key="link.public_id" class="px-6 py-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <a :href="route('developer.payment-links.show', { paymentLink: link.public_id })" class="font-medium text-gray-900 hover:text-violet-600 dark:text-white">
                                        {{ link.title }}
                                    </a>
                                    <p class="mt-1 break-all text-xs text-gray-500 dark:text-gray-400">{{ link.public_url }}</p>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        {{ link.allow_custom_amount ? 'Custom amount' : `${link.currency} ${link.amount}` }} · {{ link.status }} · {{ new Date(link.created_at).toLocaleDateString() }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="copy(link.public_url)" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Copy</button>
                                    <form v-if="link.status === 'active'" :action="route('developer.payment-links.deactivate', { paymentLink: link.public_id })" method="post">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                        <button type="submit" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Deactivate</button>
                                    </form>
                                    <form v-else :action="route('developer.payment-links.activate', { paymentLink: link.public_id })" method="post">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                        <button type="submit" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Activate</button>
                                    </form>
                                    <form :action="route('developer.payment-links.destroy', { paymentLink: link.public_id })" method="post">
                                        <input type="hidden" name="_method" value="delete" />
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:border-red-500/20 dark:text-red-300">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div v-if="paymentLinks.length === 0" class="px-6 py-6 text-sm text-gray-500 dark:text-gray-400">
                            No payment links yet.
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Create link</h3>
                    <form :action="route('developer.payment-links.store')" method="post" class="mt-4 space-y-4">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                        <input type="hidden" name="allow_custom_amount" value="0" />
                        <input type="hidden" name="status" value="active" />
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                            <input name="title" v-model="title" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                            <textarea name="description" v-model="description" rows="3" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100"></textarea>
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
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Create link</button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
