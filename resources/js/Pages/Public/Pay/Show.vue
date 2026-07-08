<script setup>
const props = defineProps({
    paymentLink: Object,
    minimumAmount: Number,
});
</script>

<template>
    <main class="min-h-screen bg-gray-50 px-4 py-10 text-gray-900">
        <section class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-gray-500">{{ paymentLink.merchant_name }}</p>
            <h1 class="mt-2 text-2xl font-semibold">{{ paymentLink.title }}</h1>
            <p v-if="paymentLink.description" class="mt-3 text-sm text-gray-600">{{ paymentLink.description }}</p>

            <div class="mt-6 rounded-md bg-gray-50 p-4">
                <p class="text-sm text-gray-500">Amount</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ paymentLink.allow_custom_amount ? `Minimum ${paymentLink.currency} ${minimumAmount}` : `${paymentLink.currency} ${paymentLink.amount}` }}
                </p>
            </div>

            <form :action="route('payment-links.pay.submit', { slug: paymentLink.slug })" method="post" class="mt-6 space-y-4">
                <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                <div v-if="paymentLink.allow_custom_amount">
                    <label class="block text-sm font-medium text-gray-700">Amount</label>
                    <input name="amount" inputmode="numeric" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">M-Pesa phone number</label>
                    <input name="phone" inputmode="tel" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500" />
                </div>
                <button type="submit" class="w-full rounded-md bg-violet-600 px-4 py-3 text-sm font-semibold text-white hover:bg-violet-500">Pay with M-Pesa</button>
            </form>
        </section>
    </main>
</template>
