<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    // ======================================================
    // REDIRECT KE GOOGLE
    // GET /auth/google
    // ======================================================

    public function redirect()
    {
        return Socialite::driver('google')
            ->stateless()
            ->with([
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    // ======================================================
    // CALLBACK DARI GOOGLE
    // GET /auth/google/callback
    // ======================================================

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            // Cari berdasarkan google_id atau email
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            // ==================================================
            // USER SUDAH ADA
            // ==================================================

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->id,
                    'google_avatar' => $googleUser->avatar,
                ]);
            }

            // ==================================================
            // USER BARU
            // ==================================================

            else {
                $user = User::create([
                    'name' => $googleUser->name ?: 'Pengguna Google',
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'google_avatar' => $googleUser->avatar,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'warga',
                    'is_active' => true,
                ]);
            }

            // ==================================================
            // CEK AKUN AKTIF
            // ==================================================

            if (isset($user->is_active) && !$user->is_active) {
                return redirect()->away(
                    config('app.frontend_url', 'http://localhost:5173') .
                    '/login?error=' .
                    urlencode('Akun kamu sedang dinonaktifkan.')
                );
            }

            // ==================================================
            // BUAT TOKEN SANCTUM
            // ==================================================

            $token = $user
                ->createToken('google-login')
                ->plainTextToken;

            // ==================================================
            // REDIRECT KE FRONTEND
            // ==================================================

            $frontendUrl = config(
                'app.frontend_url',
                'http://localhost:5173'
            );

            return redirect()->away(
                $frontendUrl .
                '/login/google-callback?token=' .
                urlencode($token)
            );

        } catch (Throwable $e) {

            // Simpan error ke log Laravel
            \Log::error('GOOGLE LOGIN ERROR', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $frontendUrl = config(
                'app.frontend_url',
                'http://localhost:5173'
            );

            return redirect()->away(
                $frontendUrl .
                '/login?error=' .
                urlencode('Login dengan Google gagal.')
            );
        }
    }
}
