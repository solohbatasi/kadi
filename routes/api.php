<?php

use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentLinkController;
use App\Http\Controllers\Api\V1\PayoutController;
use App\Http\Controllers\Api\V1\PayoutRecipientController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Mpesa\B2CCallbackController;
use App\Http\Controllers\Mpesa\StkCallbackController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth.apikey', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index'])->name('api.v1.transactions.index');
    Route::post('/transactions/push-stk', [TransactionController::class, 'initiateStkPush'])
        ->name('api.v1.push-stk');
    Route::get('/transactions/{public_id}', [TransactionController::class, 'show'])->name('api.v1.transactions.show');
    Route::get('/payment-links', [PaymentLinkController::class, 'index'])->name('api.v1.payment-links.index');
    Route::post('/payment-links', [PaymentLinkController::class, 'store'])->name('api.v1.payment-links.store');
    Route::get('/payment-links/{public_id}', [PaymentLinkController::class, 'show'])->name('api.v1.payment-links.show');
    Route::patch('/payment-links/{public_id}', [PaymentLinkController::class, 'update'])->name('api.v1.payment-links.update');
    Route::delete('/payment-links/{public_id}', [PaymentLinkController::class, 'destroy'])->name('api.v1.payment-links.destroy');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('api.v1.invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('api.v1.invoices.store');
    Route::get('/invoices/{public_id}', [InvoiceController::class, 'show'])->name('api.v1.invoices.show');
    Route::patch('/invoices/{public_id}', [InvoiceController::class, 'update'])->name('api.v1.invoices.update');
    Route::post('/invoices/{public_id}/send', [InvoiceController::class, 'send'])->name('api.v1.invoices.send');
    Route::post('/invoices/{public_id}/mark-paid', [InvoiceController::class, 'markPaid'])->name('api.v1.invoices.mark-paid');
    Route::post('/invoices/{public_id}/void', [InvoiceController::class, 'void'])->name('api.v1.invoices.void');
    Route::delete('/invoices/{public_id}', [InvoiceController::class, 'destroy'])->name('api.v1.invoices.destroy');
    Route::get('/payouts', [PayoutController::class, 'index'])->name('api.v1.payouts.index');
    Route::post('/payouts', [PayoutController::class, 'store'])->name('api.v1.payouts.store');
    Route::get('/payouts/{public_id}', [PayoutController::class, 'show'])->name('api.v1.payouts.show');
    Route::get('/payout-recipients', [PayoutRecipientController::class, 'index'])->name('api.v1.payout-recipients.index');
    Route::post('/payout-recipients', [PayoutRecipientController::class, 'store'])->name('api.v1.payout-recipients.store');
    Route::patch('/payout-recipients/{public_id}', [PayoutRecipientController::class, 'update'])->name('api.v1.payout-recipients.update');
    Route::delete('/payout-recipients/{public_id}', [PayoutRecipientController::class, 'destroy'])->name('api.v1.payout-recipients.destroy');
});

Route::post('/mpesa/stk-callback/{secret}', [StkCallbackController::class, 'handle'])
    ->name('mpesa.stk-callback');
Route::post('/mpesa/b2c/result/{secret}', [B2CCallbackController::class, 'result'])
    ->name('mpesa.b2c.result');
Route::post('/mpesa/b2c/timeout/{secret}', [B2CCallbackController::class, 'timeout'])
    ->name('mpesa.b2c.timeout');
