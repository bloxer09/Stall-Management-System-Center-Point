<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', function () {
        return redirect()->route('home', ['register' => 1]);
    })->name('register');

    Route::get('login', function () {
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
