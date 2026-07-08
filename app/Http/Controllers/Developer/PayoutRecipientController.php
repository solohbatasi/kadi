<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Developer\StorePayoutRecipientRequest;
use App\Http\Requests\Developer\UpdatePayoutRecipientRequest;
use App\Models\AuditLog;
use App\Models\PayoutRecipient;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class PayoutRecipientController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('developer.payouts.index');
    }

    public function store(StorePayoutRecipientRequest $request): RedirectResponse
    {
        $merchant = $request->user()->merchant()->firstOrFail();
        $recipient = $merchant->payoutRecipients()->create([
            'public_id' => $this->publicId(),
            'name' => $request->input('name'),
            'phone' => PhoneNumber::normalize($request->input('phone')),
            'status' => $request->input('status', 'active'),
        ]);

        $this->audit($recipient, 'payout_recipient.created');

        return back()->with('flash.banner', 'Payout recipient saved.');
    }

    public function update(UpdatePayoutRecipientRequest $request, PayoutRecipient $recipient): RedirectResponse
    {
        $this->authorizeOwnership($recipient);
        $recipient->update([
            'name' => $request->input('name'),
            'phone' => PhoneNumber::normalize($request->input('phone')),
            'status' => $request->input('status', $recipient->status),
        ]);
        $this->audit($recipient, 'payout_recipient.updated');

        return back()->with('flash.banner', 'Payout recipient updated.');
    }

    public function activate(PayoutRecipient $recipient): RedirectResponse
    {
        $this->authorizeOwnership($recipient);
        $recipient->update(['status' => 'active']);
        $this->audit($recipient, 'payout_recipient.activated');

        return back();
    }

    public function deactivate(PayoutRecipient $recipient): RedirectResponse
    {
        $this->authorizeOwnership($recipient);
        $recipient->update(['status' => 'inactive']);
        $this->audit($recipient, 'payout_recipient.deactivated');

        return back();
    }

    public function destroy(PayoutRecipient $recipient): RedirectResponse
    {
        $this->authorizeOwnership($recipient);
        $this->audit($recipient, 'payout_recipient.deleted');
        $recipient->delete();

        return back();
    }

    protected function authorizeOwnership(PayoutRecipient $recipient): void
    {
        $merchant = request()->user()?->merchant()->first();

        if (! $merchant || $recipient->merchant_id !== $merchant->id) {
            abort(403);
        }
    }

    protected function audit(PayoutRecipient $recipient, string $action): void
    {
        AuditLog::create([
            'merchant_id' => $recipient->merchant_id,
            'user_id' => request()->user()?->id,
            'action' => $action,
            'subject_type' => PayoutRecipient::class,
            'subject_id' => $recipient->id,
            'metadata' => ['recipient_public_id' => $recipient->public_id],
            'created_at' => now(),
        ]);
    }

    protected function publicId(): string
    {
        do {
            $id = 'rec_'.Str::random(24);
        } while (PayoutRecipient::where('public_id', $id)->exists());

        return $id;
    }
}
