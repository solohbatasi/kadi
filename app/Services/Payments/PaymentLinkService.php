<?php

namespace App\Services\Payments;

use App\Models\Merchant;
use App\Models\PaymentLink;
use App\Support\Money;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentLinkService
{
    public function create(Merchant $merchant, array $data): PaymentLink
    {
        $data = $this->normalizeData($data);
        $this->validateAmountRules($data);

        return $merchant->paymentLinks()->create([
            'public_id' => $this->generatePublicId(),
            'slug' => $this->generateUniqueSlug($data['slug'] ?? $data['title']),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? config('payments.currency', 'KES'),
            'allow_custom_amount' => $data['allow_custom_amount'] ?? false,
            'success_redirect_url' => $data['success_redirect_url'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);
    }

    public function update(PaymentLink $paymentLink, array $data): PaymentLink
    {
        $data = $this->normalizeData(array_merge($paymentLink->only([
            'title',
            'description',
            'amount',
            'currency',
            'allow_custom_amount',
            'success_redirect_url',
            'status',
        ]), $data));
        $this->validateAmountRules($data);

        $paymentLink->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? config('payments.currency', 'KES'),
            'allow_custom_amount' => $data['allow_custom_amount'] ?? false,
            'success_redirect_url' => $data['success_redirect_url'] ?? null,
            'status' => $data['status'] ?? $paymentLink->status,
        ]);

        if (! empty($data['slug']) && $data['slug'] !== $paymentLink->slug) {
            $paymentLink->slug = $this->generateUniqueSlug($data['slug'], $paymentLink);
        }

        $paymentLink->save();

        return $paymentLink->fresh();
    }

    public function deactivate(PaymentLink $paymentLink): void
    {
        $paymentLink->update(['status' => 'inactive']);
    }

    public function activate(PaymentLink $paymentLink): void
    {
        $this->validateAmountRules($paymentLink->toArray());
        $paymentLink->update(['status' => 'active']);
    }

    public function delete(PaymentLink $paymentLink): void
    {
        $paymentLink->delete();
    }

    public function findActiveBySlug(string $slug): ?PaymentLink
    {
        return PaymentLink::query()
            ->with('merchant')
            ->where('slug', $slug)
            ->where('status', 'active')
            ->first();
    }

    protected function normalizeData(array $data): array
    {
        $data['allow_custom_amount'] = (bool) ($data['allow_custom_amount'] ?? false);
        $data['currency'] = $data['currency'] ?? config('payments.currency', 'KES');

        if (array_key_exists('amount', $data) && $data['amount'] !== null && $data['amount'] !== '') {
            $data['amount'] = Money::toInteger($data['amount']);
        } else {
            $data['amount'] = null;
        }

        return $data;
    }

    protected function validateAmountRules(array $data): void
    {
        if (! ($data['allow_custom_amount'] ?? false) && empty($data['amount'])) {
            throw new InvalidArgumentException('Amount is required for fixed amount payment links.');
        }

        if (($data['amount'] ?? null) !== null && ! Money::isAtLeastMinimum($data['amount'])) {
            throw new InvalidArgumentException(sprintf('Amount must be at least %s.', Money::minimumAmount()));
        }
    }

    protected function generateUniqueSlug(string $value, ?PaymentLink $ignore = null): string
    {
        $base = Str::slug($value) ?: 'payment-link';
        $slug = $base;
        $counter = 2;

        while (PaymentLink::where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    protected function generatePublicId(): string
    {
        do {
            $publicId = 'plink_'.Str::random(24);
        } while (PaymentLink::where('public_id', $publicId)->exists());

        return $publicId;
    }
}
