<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // GET /api/users
    public function index()
    {
        $users = User::withCount('laporans')->orderByDesc('created_at')->get();
        return response()->json($users);
    }

    // GET /api/users/petugas — daftar petugas (buat dropdown assign laporan)
    public function petugas()
    {
        $petugas = User::where('role', 'petugas')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($petugas);
    }

    // POST /api/users/petugas — admin bikin akun petugas baru
    public function storePetugas(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $petugas = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'petugas',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Akun petugas berhasil dibuat',
            'data' => $petugas,
        ], 201);
    }

    // PUT /api/users/{user}/toggle-status
    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return response()->json($user);
    }

    // DELETE /api/users/{user}
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus']);
    }
}
