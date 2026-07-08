<?php

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
});

Route::post('/mpesa/stk-callback/{secret}', [StkCallbackController::class, 'handle'])
    ->name('mpesa.stk-callback');
