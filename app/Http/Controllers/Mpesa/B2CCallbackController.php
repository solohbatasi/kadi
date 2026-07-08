<?php

namespace App\Http\Controllers\Mpesa;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\Payments\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class B2CCallbackController extends Controller
{
    public function __construct(protected PayoutService $payouts)
    {
    }

    public function result(Request $request, string $secret): JsonResponse
    {
        if (! $this->validSecret($secret)) {
            return response()->json(['message' => 'Invalid callback secret.'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        $payload = $request->json()->all();
        $result = $payload['Result'] ?? $payload;
        $conversationId = $result['ConversationID'] ?? null;
        $originatorId = $result['OriginatorConversationID'] ?? null;

        $payout = Payout::where('provider_conversation_id', $conversationId)
            ->orWhere('provider_originator_conversation_id', $originatorId)
            ->first();

        if (! $payout) {
            return response()->json(['message' => 'Callback received.']);
        }

        $providerData = [
            'conversation_id' => $conversationId,
            'originator_conversation_id' => $originatorId,
            'result_code' => $result['ResultCode'] ?? null,
            'result_description' => $result['ResultDesc'] ?? null,
            'metadata' => ['b2c_callback' => $payload],
        ];

        if ((string) ($result['ResultCode'] ?? '') === '0') {
            $this->payouts->markSuccess($payout, $providerData);
        } else {
            $this->payouts->markFailed($payout, $result['ResultDesc'] ?? 'B2C payout failed.', $providerData);
        }

        return response()->json(['message' => 'Callback received.']);
    }

    public function timeout(Request $request, string $secret): JsonResponse
    {
        if (! $this->validSecret($secret)) {
            return response()->json(['message' => 'Invalid callback secret.'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        $payload = $request->json()->all();
        $conversationId = $payload['ConversationID'] ?? $payload['Result']['ConversationID'] ?? null;
        $originatorId = $payload['OriginatorConversationID'] ?? $payload['Result']['OriginatorConversationID'] ?? null;

        $payout = Payout::where('provider_conversation_id', $conversationId)
            ->orWhere('provider_originator_conversation_id', $originatorId)
            ->first();

        if ($payout) {
            $this->payouts->markFailed($payout, 'B2C payout timed out.', [
                'conversation_id' => $conversationId,
                'originator_conversation_id' => $originatorId,
                'metadata' => ['b2c_timeout' => $payload],
            ]);
        }

        return response()->json(['message' => 'Callback received.']);
    }

    protected function validSecret(string $secret): bool
    {
        $configured = config('mpesa.callback_secret');

        return $configured && hash_equals((string) $configured, $secret);
    }
}
