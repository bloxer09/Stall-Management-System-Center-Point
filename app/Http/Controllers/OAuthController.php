<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureAccountActive;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('home', ['login' => 1])
                ->with('error', 'Google Sign-In is not configured yet. Please add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET to your .env file.');
        }

        try {
            return Socialite::driver('google')->redirect();
        } catch (\Exception $e) {
            return redirect()->route('home', ['login' => 1])
                ->with('error', 'Unable to initiate Google Sign-In: ' . $e->getMessage());
        }
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');

            if (empty($clientId) || empty($clientSecret)) {
                return redirect()->route('home', ['login' => 1])
                    ->with('error', 'Google Sign-In credentials are missing.');
            }

            $googleUser = Socialite::driver('google')->user();

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?? 'Google User',
                    'email' => $googleUser->getEmail(),
                    'phone' => null, // added later in Profile; a random number could collide with a real one
                    'google_id' => $googleUser->getId(),
                    'profile_picture' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
            } else if (empty($user->google_id)) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'profile_picture' => $user->profile_picture ?? $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            if ($user->isDeactivated()) {
                return redirect()->route('home')->with('error', EnsureAccountActive::MESSAGE);
            }

            Auth::login($user);

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('user.dashboard');
        } catch (\Exception $e) {
            return redirect()->route('home', ['login' => 1])->with('error', 'Google authentication failed: ' . $e->getMessage());
        }
    }

    public function redirectToFacebook(): RedirectResponse
    {
        $clientId = config('services.facebook.client_id');
        $clientSecret = config('services.facebook.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('home', ['login' => 1])
                ->with('error', 'Facebook Sign-In is not configured yet. Please add FACEBOOK_CLIENT_ID and FACEBOOK_CLIENT_SECRET to your .env file.');
        }

        try {
            return Socialite::driver('facebook')->redirect();
        } catch (\Exception $e) {
            return redirect()->route('home', ['login' => 1])
                ->with('error', 'Unable to initiate Facebook Sign-In: ' . $e->getMessage());
        }
    }

    public function handleFacebookCallback(): RedirectResponse
    {
        try {
            $clientId = config('services.facebook.client_id');
            $clientSecret = config('services.facebook.client_secret');

            if (empty($clientId) || empty($clientSecret)) {
                return redirect()->route('home', ['login' => 1])
                    ->with('error', 'Facebook Sign-In credentials are missing.');
            }

            $facebookUser = Socialite::driver('facebook')->user();

            $user = User::where('facebook_id', $facebookUser->getId())
                ->orWhere('email', $facebookUser->getEmail())
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $facebookUser->getName() ?? 'Facebook User',
                    'email' => $facebookUser->getEmail() ?? 'fb_' . $facebookUser->getId() . '@stallm.local',
                    'phone' => null, // added later in Profile; a random number could collide with a real one
                    'facebook_id' => $facebookUser->getId(),
                    'profile_picture' => $facebookUser->getAvatar(),
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
            } else if (empty($user->facebook_id)) {
                $user->update([
                    'facebook_id' => $facebookUser->getId(),
                    'profile_picture' => $user->profile_picture ?? $facebookUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            if ($user->isDeactivated()) {
                return redirect()->route('home')->with('error', EnsureAccountActive::MESSAGE);
            }

            Auth::login($user);

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            return redirect()->route('user.dashboard');
        } catch (\Exception $e) {
            return redirect()->route('home', ['login' => 1])->with('error', 'Facebook authentication failed: ' . $e->getMessage());
        }
    }
}
