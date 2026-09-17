<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    // GET /auth/google
    public function redirect()
    {
        return Socialite::driver('google')
            // Parameter ini bikin Google SELALU tampilkan halaman
            // pilih akun, walaupun session Google masih aktif.
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    // GET /auth/google/callback
    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->first();

        if ($user) {
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->id]);
            }
        } else {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'password' => Hash::make(Str::random(24)),
                'role' => 'warga',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        return redirect()->away(
            $frontendUrl . '/login/google-callback?token=' . $token
        );
    }
}
