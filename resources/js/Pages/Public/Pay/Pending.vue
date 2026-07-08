<script setup>
const props = defineProps({
    paymentLink: Object,
    transaction: Object,
    customerMessage: String,
});
</script>

<template>
    <main class="min-h-screen bg-gray-50 px-4 py-10 text-gray-900">
        <section class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">{{ paymentLink.merchant_name }}</p>
            <h1 class="mt-2 text-2xl font-semibold">Payment request sent</h1>
            <p class="mt-3 text-sm text-gray-600">{{ customerMessage || 'Check your phone and enter your M-Pesa PIN to complete payment.' }}</p>
            <div class="mt-6 rounded-md bg-gray-50 p-4 text-sm">
                <p><span class="font-semibold">Transaction:</span> {{ transaction.id }}</p>
                <p class="mt-2"><span class="font-semibold">Amount:</span> {{ transaction.currency }} {{ transaction.amount }}</p>
                <p class="mt-2"><span class="font-semibold">Status:</span> {{ transaction.status }}</p>
            </div>
            <a v-if="paymentLink.success_redirect_url" :href="paymentLink.success_redirect_url" class="mt-6 inline-flex w-full justify-center rounded-md border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Continue</a>
        </section>
    </main>
</template>
