<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth.apikey', 'throttle:60,1'])->group(function () {
    Route::get('/test-api-key', function (Request $request) {
        return response()->json([
            'merchant_id' => $request->attributes->get('merchant')->id,
            'api_key_id' => $request->attributes->get('apiKey')->id,
            'environment' => $request->attributes->get('apiEnvironment'),
        ]);
    });
});
