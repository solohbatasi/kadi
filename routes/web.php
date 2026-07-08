<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Developer\ApiKeyController;
use App\Http\Controllers\Developer\DeveloperDashboardController;
use App\Http\Controllers\Developer\InvoiceController;
use App\Http\Controllers\Developer\PaymentLinkController;
use App\Http\Controllers\Developer\PayoutController;
use App\Http\Controllers\Developer\PayoutRecipientController;
use App\Http\Controllers\Developer\WebhookEndpointController;
use App\Http\Controllers\Developer\WalletController;
use App\Http\Controllers\Public\PaymentLinkPayController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/pay/{slug}', [PaymentLinkPayController::class, 'show'])->name('payment-links.pay.show');
Route::post('/pay/{slug}', [PaymentLinkPayController::class, 'pay'])->name('payment-links.pay.submit');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('developer')->middleware(['throttle:60,1'])->name('developer.')->group(function () {
        Route::get('/', [DeveloperDashboardController::class, 'index'])->name('dashboard');
        Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
        Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
        Route::post('api-keys/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])->name('api-keys.revoke');
        Route::post('api-keys/{apiKey}/rotate', [ApiKeyController::class, 'rotate'])->name('api-keys.rotate');
        Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
        Route::get('wallet', [WalletController::class, 'show'])->name('wallet.overview');
        Route::get('webhooks', [WebhookEndpointController::class, 'show'])->name('webhooks.show');
        Route::put('webhooks', [WebhookEndpointController::class, 'update'])->name('webhooks.update');
        Route::post('webhooks/test', [WebhookEndpointController::class, 'test'])->name('webhooks.test');
        Route::get('payment-links', [PaymentLinkController::class, 'index'])->name('payment-links.index');
        Route::post('payment-links', [PaymentLinkController::class, 'store'])->name('payment-links.store');
        Route::get('payment-links/{paymentLink}', [PaymentLinkController::class, 'show'])->name('payment-links.show');
        Route::put('payment-links/{paymentLink}', [PaymentLinkController::class, 'update'])->name('payment-links.update');
        Route::post('payment-links/{paymentLink}/activate', [PaymentLinkController::class, 'activate'])->name('payment-links.activate');
        Route::post('payment-links/{paymentLink}/deactivate', [PaymentLinkController::class, 'deactivate'])->name('payment-links.deactivate');
        Route::delete('payment-links/{paymentLink}', [PaymentLinkController::class, 'destroy'])->name('payment-links.destroy');
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
        Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::get('payouts', [PayoutController::class, 'index'])->name('payouts.index');
        Route::post('payouts', [PayoutController::class, 'store'])->name('payouts.store');
        Route::get('payout-recipients', [PayoutRecipientController::class, 'index'])->name('payout-recipients.index');
        Route::post('payout-recipients', [PayoutRecipientController::class, 'store'])->name('payout-recipients.store');
        Route::put('payout-recipients/{recipient}', [PayoutRecipientController::class, 'update'])->name('payout-recipients.update');
        Route::post('payout-recipients/{recipient}/activate', [PayoutRecipientController::class, 'activate'])->name('payout-recipients.activate');
        Route::post('payout-recipients/{recipient}/deactivate', [PayoutRecipientController::class, 'deactivate'])->name('payout-recipients.deactivate');
        Route::delete('payout-recipients/{recipient}', [PayoutRecipientController::class, 'destroy'])->name('payout-recipients.destroy');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
        Route::post('users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('users/{user}/terminate', [UserController::class, 'terminate'])->name('users.terminate');

        Route::resource('roles', RoleController::class)->except(['create', 'edit', 'show']);
        Route::resource('permissions', PermissionController::class)->except(['create', 'edit', 'show']);

        Route::get('system-health', SystemHealthController::class)->name('system-health');
        Route::delete('system-health/sessions/{session}', [SystemHealthController::class, 'destroySession'])->name('system-health.sessions.destroy');
        Route::delete('system-health/tokens/{token}', [SystemHealthController::class, 'destroyToken'])->name('system-health.tokens.destroy');
    });
});
