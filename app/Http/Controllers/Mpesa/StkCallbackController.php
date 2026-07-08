<?php

namespace App\Http\Controllers\Mpesa;

use App\Http\Controllers\Controller;
use App\Services\Mpesa\CallbackParser;
use App\Services\Payments\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class StkCallbackController extends Controller
{
    public function __construct(
        protected CallbackParser $parser,
        protected TransactionService $transactionService
    ) {
    }

    public function handle(Request $request, string $secret): JsonResponse
    {
        $configuredSecret = config('mpesa.callback_secret');

        if (! $configuredSecret || $secret !== $configuredSecret) {
            return response()->json(['message' => 'Invalid callback secret.'], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        $payload = $request->json()->all();

        try {
            $parsed = $this->parser->parse($payload);
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'Invalid callback payload.'], SymfonyResponse::HTTP_BAD_REQUEST);
        }

        $this->transactionService->processStkCallback($parsed);

        return response()->json(['message' => 'Callback received.'], SymfonyResponse::HTTP_OK);
    }
}
