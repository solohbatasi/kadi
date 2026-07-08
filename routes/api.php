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

Route::middleware(['auth.apikey'])->prefix('v1')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index'])->middleware('throttle:payments-api-read')->name('api.v1.transactions.index');
    Route::post('/transactions/push-stk', [TransactionController::class, 'initiateStkPush'])
        ->middleware('throttle:payments-api-stk')
        ->name('api.v1.push-stk');
    Route::get('/transactions/{public_id}', [TransactionController::class, 'show'])->middleware('throttle:payments-api-read')->name('api.v1.transactions.show');
    Route::get('/payment-links', [PaymentLinkController::class, 'index'])->middleware('throttle:payments-api-read')->name('api.v1.payment-links.index');
    Route::post('/payment-links', [PaymentLinkController::class, 'store'])->middleware('throttle:payments-api-write')->name('api.v1.payment-links.store');
    Route::get('/payment-links/{public_id}', [PaymentLinkController::class, 'show'])->middleware('throttle:payments-api-read')->name('api.v1.payment-links.show');
    Route::patch('/payment-links/{public_id}', [PaymentLinkController::class, 'update'])->middleware('throttle:payments-api-write')->name('api.v1.payment-links.update');
    Route::delete('/payment-links/{public_id}', [PaymentLinkController::class, 'destroy'])->middleware('throttle:payments-api-write')->name('api.v1.payment-links.destroy');
    Route::get('/invoices', [InvoiceController::class, 'index'])->middleware('throttle:payments-api-read')->name('api.v1.invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->middleware('throttle:payments-api-write')->name('api.v1.invoices.store');
    Route::get('/invoices/{public_id}', [InvoiceController::class, 'show'])->middleware('throttle:payments-api-read')->name('api.v1.invoices.show');
    Route::patch('/invoices/{public_id}', [InvoiceController::class, 'update'])->middleware('throttle:payments-api-write')->name('api.v1.invoices.update');
    Route::post('/invoices/{public_id}/send', [InvoiceController::class, 'send'])->middleware('throttle:payments-api-write')->name('api.v1.invoices.send');
    Route::post('/invoices/{public_id}/mark-paid', [InvoiceController::class, 'markPaid'])->middleware('throttle:payments-api-write')->name('api.v1.invoices.mark-paid');
    Route::post('/invoices/{public_id}/void', [InvoiceController::class, 'void'])->middleware('throttle:payments-api-write')->name('api.v1.invoices.void');
    Route::delete('/invoices/{public_id}', [InvoiceController::class, 'destroy'])->middleware('throttle:payments-api-write')->name('api.v1.invoices.destroy');
    Route::get('/payouts', [PayoutController::class, 'index'])->middleware('throttle:payments-api-read')->name('api.v1.payouts.index');
    Route::post('/payouts', [PayoutController::class, 'store'])->middleware('throttle:payments-api-payouts')->name('api.v1.payouts.store');
    Route::get('/payouts/{public_id}', [PayoutController::class, 'show'])->middleware('throttle:payments-api-read')->name('api.v1.payouts.show');
    Route::get('/payout-recipients', [PayoutRecipientController::class, 'index'])->middleware('throttle:payments-api-read')->name('api.v1.payout-recipients.index');
    Route::post('/payout-recipients', [PayoutRecipientController::class, 'store'])->middleware('throttle:payments-api-write')->name('api.v1.payout-recipients.store');
    Route::patch('/payout-recipients/{public_id}', [PayoutRecipientController::class, 'update'])->middleware('throttle:payments-api-write')->name('api.v1.payout-recipients.update');
    Route::delete('/payout-recipients/{public_id}', [PayoutRecipientController::class, 'destroy'])->middleware('throttle:payments-api-write')->name('api.v1.payout-recipients.destroy');
});

Route::post('/mpesa/stk-callback/{secret}', [StkCallbackController::class, 'handle'])
    ->middleware('throttle:mpesa-callbacks')
    ->name('mpesa.stk-callback');
Route::post('/mpesa/b2c/result/{secret}', [B2CCallbackController::class, 'result'])
    ->middleware('throttle:mpesa-callbacks')
    ->name('mpesa.b2c.result');
Route::post('/mpesa/b2c/timeout/{secret}', [B2CCallbackController::class, 'timeout'])
    ->middleware('throttle:mpesa-callbacks')
    ->name('mpesa.b2c.timeout');
