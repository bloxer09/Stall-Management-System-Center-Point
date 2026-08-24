<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\PayMongoWebhookController;
use App\Http\Controllers\ReceiptVerificationController;
use App\Models\Stall;
use App\Models\StallLocation;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::get('/verify-receipt/{id}', [ReceiptVerificationController::class, 'verify'])->name('receipt.verify');

Route::get('/privacy-policy', function () {
    return view('pages.privacy');
})->name('privacy');

Route::get('/terms-and-conditions', function () {
    return view('pages.terms');
})->name('terms');

/*
|--------------------------------------------------------------------------
| OAuth & Webhooks
|--------------------------------------------------------------------------
*/
Route::get('/auth/google', [OAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::get('/auth/facebook', [OAuthController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('/auth/facebook/callback', [OAuthController::class, 'handleFacebookCallback'])->name('auth.facebook.callback');

Route::post('/payment/paymongo-webhook', [PayMongoWebhookController::class, 'handleWebhook'])->name('paymongo.webhook');

/*
|--------------------------------------------------------------------------
| Authenticated Downloads (IDOR Guarded)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/receipt/{id}/pdf', [ReceiptVerificationController::class, 'downloadPdf'])->name('receipt.pdf');
    Route::get('/files/contract/{id}', [FileController::class, 'downloadContract'])->name('file.contract');
    Route::get('/files/ticket-attachment/{id}', [FileController::class, 'downloadTicketAttachment'])->name('file.ticket-attachment');
});

/*
|--------------------------------------------------------------------------
| Tenant / User Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:user'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.user.dashboard');
    })->name('dashboard');

    Route::get('/leases', function () {
        return view('pages.user.leases');
    })->name('leases');

    Route::get('/payments', function () {
        return view('pages.user.payments');
    })->name('payments');

    Route::get('/tickets', function () {
        return view('pages.user.tickets');
    })->name('tickets');

    Route::get('/documents', function () {
        return view('pages.user.documents');
    })->name('documents');
});

/*
|--------------------------------------------------------------------------
| Admin & Staff Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.admin.dashboard');
    })->name('dashboard');

    Route::get('/stalls', function () {
        return view('pages.admin.stalls');
    })->name('stalls');

    Route::get('/floors', function () {
        return view('pages.admin.floors');
    })->name('floors');

    Route::get('/grid-editor', function () {
        return view('pages.admin.grid-editor');
    })->name('grid-editor');

    Route::get('/mappings', function () {
        return view('pages.admin.mappings');
    })->name('mappings');

    Route::get('/payments', function () {
        return view('pages.admin.payments');
    })->name('payments');

    Route::get('/tickets', function () {
        return view('pages.admin.tickets');
    })->name('tickets');

    Route::get('/analytics', function () {
        return view('pages.admin.analytics');
    })->name('analytics');

    Route::get('/reports', function () {
        return view('pages.admin.reports');
    })->name('reports');

    Route::get('/audit-logs', function () {
        return view('pages.admin.audit-logs');
    })->name('audit-logs');

    Route::get('/error-logs', function () {
        return view('pages.admin.error-logs');
    })->name('error-logs');

    Route::get('/settings', function () {
        return view('pages.admin.settings');
    })->name('settings');
});

require __DIR__.'/auth.php';

