<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // ======================================================
    // POST /api/register
    // ======================================================

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'warga',
            'is_active' => true,
        ]);

        // ==================================================
        // SINGLE LOGIN DEVICE
        // ==================================================

        $user->tokens()->delete();

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // ======================================================
    // POST /api/login
    // ======================================================

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        // ==================================================
        // CEK EMAIL DAN PASSWORD
        // ==================================================

        if (
            !$user ||
            !Hash::check($validated['password'], $user->password)
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Email atau password salah.'
                ],
            ]);
        }

        // ==================================================
        // CEK AKUN AKTIF
        // ==================================================

        if (
            isset($user->is_active) &&
            !$user->is_active
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'Akun kamu sedang dinonaktifkan.'
                ],
            ]);
        }

        // ==================================================
        // SINGLE LOGIN DEVICE
        // ==================================================
        // Hapus semua token lama milik akun ini.
        // Jadi hanya login terbaru yang tetap aktif.
        // ==================================================

        $user->tokens()->delete();

        // ==================================================
        // BUAT TOKEN BARU
        // ==================================================

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    // ======================================================
    // POST /api/logout
    // ======================================================

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            if ($token) {
                $token->delete();
            }
        }

        return response()->json([
            'message' => 'Berhasil logout'
        ]);
    }

    // ======================================================
    // GET /api/profile
    // ======================================================

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Kamu belum login.'
            ], 401);
        }

        $user->loadCount('laporans');

        return response()->json($user);
    }

    // ======================================================
    // POST /api/profile
    // ======================================================

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Kamu belum login.'
            ], 401);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',

            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],

            'current_password' => 'required_with:password|string',

            'password' => 'nullable|string|min:6|confirmed',

            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // ==================================================
        // GANTI PASSWORD
        // ==================================================

        if (!empty($validated['password'])) {
            if (
                empty($validated['current_password']) ||
                !Hash::check(
                    $validated['current_password'],
                    $user->password
                )
            ) {
                throw ValidationException::withMessages([
                    'current_password' => [
                        'Password saat ini salah.'
                    ],
                ]);
            }
        }

        // ==================================================
        // FOTO PROFIL
        // ==================================================

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request
                ->file('foto')
                ->store('foto-profil', 'public');
        }

        // ==================================================
        // BERSIHKAN DATA PASSWORD
        // ==================================================

        unset($validated['current_password']);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make(
                $validated['password']
            );
        }

        // ==================================================
        // UPDATE USER
        // ==================================================

        $user->update($validated);

        // ==================================================
        // REFRESH DATA USER
        // ==================================================

        $user->refresh();

        $user->loadCount('laporans');

        return response()->json($user);
    }
}
