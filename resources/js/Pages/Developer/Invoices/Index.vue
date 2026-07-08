<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    invoices: Array,
});

const customerName = ref('');
const customerEmail = ref('');
const customerPhone = ref('');
const dueDate = ref('');
const notes = ref('');
const taxRate = ref('0');
const discountAmount = ref('0');
const firstDescription = ref('');
const firstQuantity = ref(1);
const firstUnitPrice = ref('');
</script>

<template>
    <AppLayout title="Invoices">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Invoices</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Create one-off invoices with hosted M-Pesa payment links.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-[#232837]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Existing invoices</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                        <div v-for="invoice in invoices" :key="invoice.public_id" class="px-6 py-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <a :href="route('developer.invoices.show', { invoice: invoice.public_id })" class="font-medium text-gray-900 hover:text-violet-600 dark:text-white">
                                        {{ invoice.invoice_number }} · {{ invoice.customer_name }}
                                    </a>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ invoice.currency }} {{ invoice.total }} · {{ invoice.status }}</p>
                                    <p class="mt-1 break-all text-xs text-gray-500 dark:text-gray-400">{{ invoice.payment_url || 'No payment link' }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Due {{ invoice.due_date || 'Anytime' }} · Created {{ new Date(invoice.created_at).toLocaleDateString() }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a :href="route('developer.invoices.show', { invoice: invoice.public_id })" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">View</a>
                                    <form v-if="['draft', 'open'].includes(invoice.status)" :action="route('developer.invoices.send', { invoice: invoice.public_id })" method="post">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                        <button type="submit" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Send</button>
                                    </form>
                                    <form v-if="['draft', 'open'].includes(invoice.status)" :action="route('developer.invoices.mark-paid', { invoice: invoice.public_id })" method="post">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                        <button type="submit" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Mark paid</button>
                                    </form>
                                    <form v-if="['draft', 'open'].includes(invoice.status)" :action="route('developer.invoices.void', { invoice: invoice.public_id })" method="post">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                        <button type="submit" class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-[#2a3040] dark:text-gray-200">Void</button>
                                    </form>
                                    <form v-if="invoice.status === 'draft'" :action="route('developer.invoices.destroy', { invoice: invoice.public_id })" method="post">
                                        <input type="hidden" name="_method" value="delete" />
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:border-red-500/20 dark:text-red-300">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div v-if="invoices.length === 0" class="px-6 py-6 text-sm text-gray-500 dark:text-gray-400">
                            No invoices yet.
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Create invoice</h3>
                    <form :action="route('developer.invoices.store')" method="post" class="mt-4 space-y-4">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                        <input type="hidden" name="items[0][description]" :value="firstDescription" />
                        <input type="hidden" name="items[0][quantity]" :value="firstQuantity" />
                        <input type="hidden" name="items[0][unit_price]" :value="firstUnitPrice" />

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer name</label>
                            <input name="customer_name" v-model="customerName" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input name="customer_email" v-model="customerEmail" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                <input name="customer_phone" v-model="customerPhone" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Line item</label>
                            <input v-model="firstDescription" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                                <input v-model="firstQuantity" inputmode="numeric" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit price</label>
                                <input v-model="firstUnitPrice" inputmode="numeric" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tax rate</label>
                                <input name="tax_rate" v-model="taxRate" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Discount</label>
                                <input name="discount_amount" v-model="discountAmount" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Due date</label>
                            <input name="due_date" type="date" v-model="dueDate" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea name="notes" v-model="notes" rows="3" class="mt-2 block w-full rounded-md border-gray-300 bg-white text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-[#232837] dark:bg-[#0f1320] dark:text-gray-100"></textarea>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">Create invoice</button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
