<?php

use App\Http\Controllers\Api\V1\PaymentLinkController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Mpesa\StkCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth.apikey', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::post('/transactions/push-stk', [TransactionController::class, 'initiateStkPush'])
        ->name('api.v1.push-stk');
    Route::get('/payment-links', [PaymentLinkController::class, 'index'])->name('api.v1.payment-links.index');
    Route::post('/payment-links', [PaymentLinkController::class, 'store'])->name('api.v1.payment-links.store');
    Route::get('/payment-links/{public_id}', [PaymentLinkController::class, 'show'])->name('api.v1.payment-links.show');
    Route::patch('/payment-links/{public_id}', [PaymentLinkController::class, 'update'])->name('api.v1.payment-links.update');
    Route::delete('/payment-links/{public_id}', [PaymentLinkController::class, 'destroy'])->name('api.v1.payment-links.destroy');
});

Route::post('/mpesa/stk-callback/{secret}', [StkCallbackController::class, 'handle'])
    ->name('mpesa.stk-callback');
