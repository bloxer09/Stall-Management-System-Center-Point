<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', function () {
        \App\Services\StallApplicationAccess::rememberStall(request()->query('stall'));
        return redirect()->route('home', ['register' => 1]);
    })->name('register');

    Route::get('login', function () {
        \App\Services\StallApplicationAccess::rememberStall(request()->query('stall'));
        return redirect()->route('home', ['login' => 1]);
    })->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function () {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
