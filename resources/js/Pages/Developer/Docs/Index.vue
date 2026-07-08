<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const authExamples = [
    'x-api-key: pay_sk_test_xxxxxxxxx',
    'Authorization: Bearer pay_sk_test_xxxxxxxxx',
    'Authorization: ApiKey pay_sk_test_xxxxxxxxx',
];

const endpoints = [
    ['POST', '/api/v1/transactions/push-stk', 'Initiate an M-Pesa STK Push.'],
    ['GET', '/api/v1/transactions', 'List transactions with status, type, date_from, and date_to filters.'],
    ['GET', '/api/v1/transactions/{public_id}', 'Retrieve one transaction.'],
    ['POST', '/api/v1/payment-links', 'Create a fixed or custom amount payment link.'],
    ['GET', '/api/v1/payment-links', 'List payment links with optional status filter.'],
    ['GET', '/api/v1/payment-links/{public_id}', 'Retrieve one payment link.'],
    ['PATCH', '/api/v1/payment-links/{public_id}', 'Update a payment link.'],
    ['DELETE', '/api/v1/payment-links/{public_id}', 'Delete a payment link.'],
    ['POST', '/api/v1/invoices', 'Create an invoice with line items.'],
    ['GET', '/api/v1/invoices', 'List invoices with status and search filters.'],
    ['GET', '/api/v1/invoices/{public_id}', 'Retrieve one invoice.'],
    ['PATCH', '/api/v1/invoices/{public_id}', 'Update a draft or open invoice.'],
    ['POST', '/api/v1/invoices/{public_id}/send', 'Open an invoice and set sent_at.'],
    ['POST', '/api/v1/invoices/{public_id}/mark-paid', 'Manually mark an invoice paid.'],
    ['POST', '/api/v1/invoices/{public_id}/void', 'Void an invoice and deactivate payment.'],
    ['DELETE', '/api/v1/invoices/{public_id}', 'Delete a draft invoice.'],
    ['GET', '/api/v1/payouts', 'List payouts with optional status filter.'],
    ['POST', '/api/v1/payouts', 'Request a payout to phone or recipient.'],
    ['GET', '/api/v1/payouts/{public_id}', 'Retrieve one payout.'],
    ['GET', '/api/v1/payout-recipients', 'List payout recipients.'],
    ['POST', '/api/v1/payout-recipients', 'Create a payout recipient.'],
    ['PATCH', '/api/v1/payout-recipients/{public_id}', 'Update a payout recipient.'],
    ['DELETE', '/api/v1/payout-recipients/{public_id}', 'Delete a payout recipient.'],
];

const webhookEvents = [
    'transaction.pending',
    'transaction.success',
    'transaction.failed',
    'transaction.cancelled',
    'transaction.timeout',
    'payout.pending',
    'payout.processing',
    'payout.success',
    'payout.failed',
    'payout.reversed',
];

const code = {
    stkBody: `{
  "phone": "254716933897",
  "amount": 800,
  "reference": "ORDER-001",
  "description": "Ticket payment",
  "metadata": {
    "order_id": "ORDER-001"
  }
}`,
    success: `{
  "transaction_id": "txn_xxxxx",
  "status": "pending",
  "checkout_request_id": "ws_CO_xxxxx",
  "merchant_request_id": "xxxxx",
  "customer_message": "Success. Request accepted for processing"
}`,
    error: `{
  "message": "The amount field is required.",
  "errors": {
    "amount": ["The amount field is required."]
  }
}`,
    curl: `curl -X POST https://your-domain.test/api/v1/transactions/push-stk \\
  -H "Content-Type: application/json" \\
  -H "x-api-key: pay_sk_test_xxxxxxxxx" \\
  -H "Idempotency-Key: order-001" \\
  -d '{"phone":"254716933897","amount":800,"reference":"ORDER-001"}'`,
    js: `await fetch('/api/v1/transactions/push-stk', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer pay_sk_test_xxxxxxxxx',
    'Idempotency-Key': 'order-001'
  },
  body: JSON.stringify({ phone: '254716933897', amount: 800, reference: 'ORDER-001' })
});`,
    php: `$ch = curl_init('https://your-domain.test/api/v1/transactions/push-stk');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: ApiKey pay_sk_test_xxxxxxxxx',
        'Idempotency-Key: order-001',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'phone' => '254716933897',
        'amount' => 800,
        'reference' => 'ORDER-001',
    ]),
    CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);`,
    fixedLink: `{
  "title": "Ticket payment",
  "amount": 800,
  "allow_custom_amount": false,
  "success_redirect_url": "https://example.com/thanks"
}`,
    linkCurl: `curl -X POST https://your-domain.test/api/v1/payment-links \\
  -H "Content-Type: application/json" \\
  -H "x-api-key: pay_sk_test_xxxxxxxxx" \\
  -d '{"title":"Ticket payment","amount":800,"allow_custom_amount":false}'`,
    customLink: `{
  "title": "Donation",
  "amount": null,
  "allow_custom_amount": true
}`,
    invoice: `{
  "customer_name": "Amina Otieno",
  "customer_email": "amina@example.com",
  "tax_rate": 16,
  "discount_amount": 100,
  "items": [
    { "description": "Consulting", "quantity": 2, "unit_price": 1500 }
  ]
}`,
    recipient: `{
  "name": "Amina Otieno",
  "phone": "0716933897"
}`,
    payoutPhone: `{
  "phone": "254716933897",
  "amount": 1000,
  "metadata": { "batch": "JULY-01" }
}`,
    payoutRecipient: `{
  "recipient_public_id": "rec_xxxxx",
  "amount": 1000
}`,
    webhookPayload: `{
  "event": "transaction.success",
  "transaction": {
    "public_id": "txn_xxxxx",
    "amount": 800,
    "status": "success",
    "phone": "2547****897",
    "mpesa_receipt": "TGL123ABC",
    "reference": "ORDER-001",
    "metadata": { "order_id": "ORDER-001" }
  }
}`,
    phpVerify: `$rawBody = file_get_contents('php://input');
$timestamp = $_SERVER['HTTP_X_PAYGATE_TIMESTAMP'] ?? '';
$signature = $_SERVER['HTTP_X_PAYGATE_SIGNATURE'] ?? '';
$expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $webhookSecret);
if (! hash_equals($expected, $signature)) {
    http_response_code(401);
    exit;
}`,
    nodeVerify: `const rawBody = req.rawBody;
const timestamp = req.header('X-PayGate-Timestamp');
const signature = req.header('X-PayGate-Signature');
const expected = crypto
  .createHmac('sha256', webhookSecret)
  .update(timestamp + '.' + rawBody)
  .digest('hex');
if (!crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(signature))) {
  return res.sendStatus(401);
}`,
};
</script>

<template>
    <AppLayout title="API Docs">
        <div class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Developer API Reference</h2>
                <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-400">Use secret API keys from your server only. Never expose secret keys in browser, mobile, or frontend code.</p>
            </section>

            <section class="grid gap-6 lg:grid-cols-[320px_1fr]">
                <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Authentication</h3>
                    <div class="mt-4 space-y-2">
                        <code v-for="example in authExamples" :key="example" class="block rounded-md bg-gray-100 px-3 py-2 text-xs text-gray-800 dark:bg-[#0d111a] dark:text-gray-200">{{ example }}</code>
                    </div>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Sandbox keys create sandbox activity. Production keys require the merchant account to be live-enabled. Send `Idempotency-Key` on payment creation requests so retries do not duplicate work.</p>
                </div>

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-[#232837] dark:bg-[#11141b]">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-[#232837]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Endpoints</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-[#232837]">
                        <div v-for="endpoint in endpoints" :key="endpoint.join('-')" class="grid gap-2 px-5 py-3 text-sm sm:grid-cols-[84px_280px_1fr]">
                            <span class="font-semibold text-violet-700 dark:text-violet-300">{{ endpoint[0] }}</span>
                            <code class="text-gray-900 dark:text-white">{{ endpoint[1] }}</code>
                            <span class="text-gray-500 dark:text-gray-400">{{ endpoint[2] }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">STK Push</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">`POST /api/v1/transactions/push-stk` creates a pending transaction, sends the phone prompt, and queues `transaction.pending` webhooks. Final wallet crediting happens after the Safaricom callback.</p>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <pre class="overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.stkBody }}</code></pre>
                    <pre class="overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.success }}</code></pre>
                </div>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Statuses include pending, success, failed, cancelled, timeout, processing, reversed. Validation failures return HTTP 422 with an errors object.</p>
                <pre class="mt-4 overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.error }}</code></pre>
                <div class="mt-4 grid gap-4 lg:grid-cols-3">
                    <pre class="overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.curl }}</code></pre>
                    <pre class="overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.js }}</code></pre>
                    <pre class="overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.php }}</code></pre>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Payment Links</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Fixed links require amount. Custom amount links collect amount on the public `/pay/{slug}` page. Inactive links show an unavailable page.</p>
                    <pre class="mt-4 overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.fixedLink }}</code></pre>
                    <pre class="mt-3 overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.customLink }}</code></pre>
                    <pre class="mt-3 overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.linkCurl }}</code></pre>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Invoices</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Totals are calculated server-side from integer KES line items. Every invoice gets a backing payment link. Statuses are draft, open, paid, and void.</p>
                    <pre class="mt-4 overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.invoice }}</code></pre>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-[#232837] dark:bg-[#11141b]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Payouts</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Payout statuses are pending, processing, success, failed, reversed, and cancelled. Payouts debit the wallet once and reverse failed payouts once. Fake/local B2C mode is the safe default until real credentials are configured.</p>
                    <pre class="mt-4 overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.recipient }}</code></pre>
                    <pre class="mt-3 overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.payoutPhone }}</code></pre>
                    <pre class="mt-3 overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.payoutRecipient }}</code></pre>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 dark:border-[#232837] dark:bg-[#11141b]">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Webhooks</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Verify signatures using the raw JSON body and a timing-safe comparison. Headers are `X-PayGate-Signature`, `X-PayGate-Event`, and `X-PayGate-Timestamp`.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span v-for="event in webhookEvents" :key="event" class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-[#252b3a] dark:text-gray-200">{{ event }}</span>
                </div>
                <div class="mt-4 grid gap-4 lg:grid-cols-3">
                    <pre class="overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.webhookPayload }}</code></pre>
                    <pre class="overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.phpVerify }}</code></pre>
                    <pre class="overflow-x-auto rounded-md bg-gray-100 p-4 text-xs dark:bg-[#0d111a]"><code>{{ code.nodeVerify }}</code></pre>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
