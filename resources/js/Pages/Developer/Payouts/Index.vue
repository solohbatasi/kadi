<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    wallet: Object,
    recipients: Array,
    payouts: Array,
    minimumAmount: Number,
});

const amount = ref('');
const phone = ref('');
const recipientPublicId = ref('');
const name = ref('');
const recipientPhone = ref('');
</script>

<template>
    <AppLayout title="Payouts">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Payouts</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Available: {{ wallet.currency }} {{ wallet.available_balance }}</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <div class="space-y-6">
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-[#232837]">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recent payouts</h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                            <div v-for="payout in payouts" :key="payout.public_id" class="px-6 py-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ payout.currency }} {{ payout.amount }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ payout.recipient?.name || payout.phone }} · {{ payout.status }}</p>
                                        <p v-if="payout.failure_reason" class="mt-1 text-xs text-red-600 dark:text-red-300">{{ payout.failure_reason }}</p>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ new Date(payout.created_at).toLocaleDateString() }}</p>
                                </div>
                            </div>
                            <div v-if="payouts.length === 0" class="px-6 py-6 text-sm text-gray-500 dark:text-gray-400">No payouts yet.</div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-[#232837]">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Recipients</h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                            <div v-for="recipient in recipients" :key="recipient.public_id" class="flex items-center justify-between gap-4 px-6 py-4">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ recipient.name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ recipient.phone }} · {{ recipient.status }}</p>
                                </div>
                                <form :action="route('developer.payout-recipients.deactivate', { recipient: recipient.public_id })" method="post">
                                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                    <button type="submit" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Deactivate</button>
                                </form>
                            </div>
                            <div v-if="recipients.length === 0" class="px-6 py-6 text-sm text-gray-500 dark:text-gray-400">No recipients saved.</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Request payout</h3>
                        <form :action="route('developer.payouts.store')" method="post" class="mt-4 space-y-4">
                            <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                                <input name="amount" v-model="amount" inputmode="numeric" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimum {{ wallet.currency }} {{ minimumAmount }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recipient</label>
                                <select name="recipient_public_id" v-model="recipientPublicId" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100">
                                    <option value="">Use phone number</option>
                                    <option v-for="recipient in recipients" :key="recipient.public_id" :value="recipient.public_id">{{ recipient.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                <input name="phone" v-model="phone" :disabled="recipientPublicId !== ''" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 disabled:bg-gray-100 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Request payout</button>
                        </form>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Save recipient</h3>
                        <form :action="route('developer.payout-recipients.store')" method="post" class="mt-4 space-y-4">
                            <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input name="name" v-model="name" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                <input name="phone" v-model="recipientPhone" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center rounded-md border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Save recipient</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
