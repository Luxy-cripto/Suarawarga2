<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Middleware untuk mengecek role pengguna.
     *
     * Contoh penggunaan:
     * ->middleware('role:admin')
     *
     * Untuk beberapa role:
     * ->middleware('role:admin,petugas')
     */
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {
        $user = $request->user();

        // Pastikan user sudah login
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Cek apakah role user sesuai dengan role yang diizinkan
        if (!in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'Kamu tidak punya akses untuk melakukan ini.',
            ], 403);
        }

        return $next($request);
    }
}
