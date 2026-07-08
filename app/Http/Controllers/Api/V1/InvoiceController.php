<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\Payments\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoices)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchant');
        $status = $request->query('status');
        $search = $request->query('search');

        $invoices = $merchant->invoices()
            ->with('paymentLink')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return $this->success('Invoices retrieved.', [
            'items' => collect($invoices->items())->map(fn (Invoice $invoice) => $this->serialize($invoice))->values(),
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
            ],
        ]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoices->create(
            $request->attributes->get('merchant'),
            $request->validated()
        );

        return $this->success('Invoice created.', $this->serialize($invoice), 201);
    }

    public function show(Request $request, string $publicId): JsonResponse
    {
        $invoice = $this->findForMerchant($request, $publicId);

        if (! $invoice) {
            return $this->error('Invoice not found.', 404);
        }

        return $this->success('Invoice retrieved.', $this->serialize($invoice->load('items', 'paymentLink')));
    }

    public function update(UpdateInvoiceRequest $request, string $publicId): JsonResponse
    {
        $invoice = $this->findForMerchant($request, $publicId);

        if (! $invoice) {
            return $this->error('Invoice not found.', 404);
        }

        $invoice = $this->invoices->update($invoice->load('items', 'paymentLink'), $request->validated());

        return $this->success('Invoice updated.', $this->serialize($invoice));
    }

    public function send(Request $request, string $publicId): JsonResponse
    {
        return $this->transition($request, $publicId, 'send', 'Invoice sent.');
    }

    public function markPaid(Request $request, string $publicId): JsonResponse
    {
        return $this->transition($request, $publicId, 'markPaid', 'Invoice marked paid.');
    }

    public function void(Request $request, string $publicId): JsonResponse
    {
        return $this->transition($request, $publicId, 'void', 'Invoice voided.');
    }

    public function destroy(Request $request, string $publicId): JsonResponse
    {
        $invoice = $this->findForMerchant($request, $publicId);

        if (! $invoice) {
            return $this->error('Invoice not found.', 404);
        }

        $this->invoices->delete($invoice->load('paymentLink'));

        return $this->success('Invoice deleted.');
    }

    protected function transition(Request $request, string $publicId, string $method, string $message): JsonResponse
    {
        $invoice = $this->findForMerchant($request, $publicId);

        if (! $invoice) {
            return $this->error('Invoice not found.', 404);
        }

        $invoice = $this->invoices->{$method}($invoice->load('paymentLink'));

        return $this->success($message, $this->serialize($invoice));
    }

    protected function findForMerchant(Request $request, string $publicId): ?Invoice
    {
        return $request->attributes->get('merchant')
            ->invoices()
            ->where('public_id', $publicId)
            ->first();
    }

    protected function serialize(Invoice $invoice): array
    {
        return [
            'public_id' => $invoice->public_id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'customer_phone' => $invoice->customer_phone,
            'currency' => $invoice->currency,
            'subtotal' => $invoice->subtotal,
            'tax_rate' => $invoice->tax_rate,
            'tax_amount' => $invoice->tax_amount,
            'discount_amount' => $invoice->discount_amount,
            'total' => $invoice->total,
            'status' => $invoice->status,
            'due_date' => $invoice->due_date,
            'notes' => $invoice->notes,
            'payment_url' => $invoice->paymentLink ? route('payment-links.pay.show', $invoice->paymentLink->slug) : null,
            'items' => $invoice->relationLoaded('items') ? $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ])->values() : [],
            'created_at' => $invoice->created_at,
        ];
    }

    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ], $status);
    }

    protected function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => [],
        ], $status);
    }
}
