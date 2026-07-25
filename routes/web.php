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
    $locations = StallLocation::orderBy('sort_order')->get();
    $stalls = Stall::with('location')->where('status', 'available')->get();
    return view('pages.home', compact('locations', 'stalls'));
})->name('home');

Route::get('/reservation', \App\Livewire\Public\StallReservation::class)->name('reservation');

Route::get('/verify-receipt/{id}', [ReceiptVerificationController::class, 'verify'])->name('receipt.verify');

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

use App\Http\Controllers\TwoFactorController;
use App\Http\Middleware\RequiresTwoFactor;

Route::middleware('auth')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
});

/*
|--------------------------------------------------------------------------
| Admin & Staff Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,staff', RequiresTwoFactor::class])->prefix('admin')->name('admin.')->group(function () {
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

    Route::get('/utilities', function () {
        return view('pages.admin.utilities');
    })->name('utilities');

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

    Route::get('/applications', function () {
        return view('pages.admin.applications');
    })->name('applications');

    Route::get('/error-logs', function () {
        return view('pages.admin.error-logs');
    })->name('error-logs');
});

require __DIR__.'/auth.php';
