<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
    public function callback(Request $request)
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');

        // Kalau user cancel/tolak izin di halaman consent Google,
        // atau ada error lain, Google kirim ?error=... bukan ?code=...
        // Cek dulu sebelum coba tukar code jadi token, supaya tidak crash.
        if ($request->has('error') || !$request->has('code')) {
            return redirect()->away(
                $frontendUrl . '/login?error=google_auth_cancelled'
            );
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            // Kalau tetap gagal (misal code sudah kedaluwarsa/dipakai ulang),
            // jangan crash, arahkan balik ke login dengan pesan error.
            return redirect()->away(
                $frontendUrl . '/login?error=google_auth_failed'
            );
        }

        $user = User::where('google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->first();

        if ($user) {

            $updates = [];

            if (!$user->google_id) {
                $updates['google_id'] = $googleUser->id;
            }

            // Hanya isi foto dari Google kalau user belum punya foto
            // sama sekali (misal belum pernah upload foto manual).
            // Ini supaya foto yang sudah di-upload manual tidak ketimpa
            // setiap kali login pakai Google.
            if (empty($user->foto) && $googleUser->avatar) {
                $updates['foto'] = $googleUser->avatar;
            }

            if (!empty($updates)) {
                $user->update($updates);
            }

        } else {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'google_id' => $googleUser->id,
                'foto' => $googleUser->avatar,
                'password' => Hash::make(Str::random(24)),
                'role' => 'warga',
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect()->away(
            $frontendUrl . '/login/google-callback?token=' . $token
        );
    }
}
