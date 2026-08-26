<?php

namespace App\Http\Controllers;

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
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'phone' => '09' . rand(100000001, 999999999),
                    'google_id' => $googleUser->getId(),
                    'profile_picture' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
            } else if (empty($user->google_id)) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
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
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookCallback(): RedirectResponse
    {
        try {
            $facebookUser = Socialite::driver('facebook')->user();

            $user = User::where('facebook_id', $facebookUser->getId())
                ->orWhere('email', $facebookUser->getEmail())
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $facebookUser->getName() ?? 'Facebook User',
                    'email' => $facebookUser->getEmail() ?? 'fb_' . $facebookUser->getId() . '@stallm.local',
                    'phone' => '09' . rand(100000001, 999999999),
                    'facebook_id' => $facebookUser->getId(),
                    'profile_picture' => $facebookUser->getAvatar(),
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'user',
                    'email_verified_at' => now(),
                ]);
            } else if (empty($user->facebook_id)) {
                $user->update([
                    'facebook_id' => $facebookUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
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
