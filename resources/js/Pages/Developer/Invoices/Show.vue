<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    invoice: Object,
});
</script>

<template>
    <AppLayout title="Invoice">
        <div class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</h2>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ invoice.customer_name }} · {{ invoice.customer_email || 'No email' }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Status: {{ invoice.status }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
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
                    </div>
                </div>
                <p v-if="invoice.payment_url" class="mt-4 break-all text-sm text-gray-500 dark:text-gray-400">{{ invoice.payment_url }}</p>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-[#232837]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Line items</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                    <div v-for="item in invoice.items" :key="item.description" class="grid grid-cols-[1fr_80px_120px_120px] gap-3 px-6 py-4 text-sm">
                        <p class="text-gray-900 dark:text-white">{{ item.description }}</p>
                        <p class="text-gray-600 dark:text-gray-300">{{ item.quantity }}</p>
                        <p class="text-gray-600 dark:text-gray-300">{{ invoice.currency }} {{ item.unit_price }}</p>
                        <p class="text-right font-medium text-gray-900 dark:text-white">{{ invoice.currency }} {{ item.total }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Customer</h3>
                    <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <p>{{ invoice.customer_name }}</p>
                        <p>{{ invoice.customer_email || 'No email' }}</p>
                        <p>{{ invoice.customer_phone || 'No phone' }}</p>
                        <p>Due date: {{ invoice.due_date || 'None' }}</p>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Totals</h3>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between"><span>Subtotal</span><span>{{ invoice.currency }} {{ invoice.subtotal }}</span></div>
                        <div class="flex justify-between"><span>Tax {{ invoice.tax_rate }}%</span><span>{{ invoice.currency }} {{ invoice.tax_amount }}</span></div>
                        <div class="flex justify-between"><span>Discount</span><span>{{ invoice.currency }} {{ invoice.discount_amount }}</span></div>
                        <div class="flex justify-between border-t border-gray-200 pt-3 text-base font-semibold dark:border-[#232837]"><span>Total</span><span>{{ invoice.currency }} {{ invoice.total }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
