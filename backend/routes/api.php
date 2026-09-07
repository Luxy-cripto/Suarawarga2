<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\TanggapanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TanggapanReactionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// =========================
// AUTH
// =========================

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);

// =========================
// PUBLIC
// Tidak perlu login
// =========================

// Semua laporan
Route::get('/laporans', [LaporanController::class, 'index']);

// Detail laporan
Route::get('/laporans/{laporan}', [LaporanController::class, 'show']);

// Daftar kategori
Route::get('/kategoris', [KategoriController::class, 'index']);

// Detail kategori
Route::get('/kategoris/{kategori}', [KategoriController::class, 'show']);

// Semua komentar pada laporan
Route::get(
    '/laporans/{laporan}/tanggapans',
    [TanggapanController::class, 'index']
);


// =========================
// ROUTE YANG BUTUH LOGIN
// =========================

Route::middleware('auth:sanctum')->group(function () {

    // =========================
    // USER LOGIN
    // =========================

    Route::get('/user', function (Request $request) {
        return response()->json(
            $request->user()
        );
    });

    // Logout
    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    );

    // =========================
    // PROFIL (butuh login)
    // =========================

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);


    // =========================
    // LAPORAN
    // =========================

    // Membuat laporan
    Route::post(
        '/laporans',
        [LaporanController::class, 'store']
    );

    // Update laporan
    Route::put(
        '/laporans/{laporan}',
        [LaporanController::class, 'update']
    );

    // Update laporan PATCH
    Route::patch(
        '/laporans/{laporan}',
        [LaporanController::class, 'update']
    );

    // Hapus laporan
    Route::delete(
        '/laporans/{laporan}',
        [LaporanController::class, 'destroy']
    );


    // =========================
    // KATEGORI
    // =========================

    // Tambah kategori
    Route::post(
        '/kategoris',
        [KategoriController::class, 'store']
    );

    // Update kategori
    Route::put(
        '/kategoris/{kategori}',
        [KategoriController::class, 'update']
    );

    // Update kategori PATCH
    Route::patch(
        '/kategoris/{kategori}',
        [KategoriController::class, 'update']
    );

    // Hapus kategori
    Route::delete(
        '/kategoris/{kategori}',
        [KategoriController::class, 'destroy']
    );


    // =========================
    // TANGGAPAN / KOMENTAR
    // =========================

    // Membuat komentar
    Route::post(
        '/laporans/{laporan}/tanggapans',
        [TanggapanController::class, 'store']
    );


    // =========================
    // LIKE / DISLIKE KOMENTAR
    // =========================

    Route::post(
        '/tanggapans/{tanggapan}/reaction',
        [TanggapanReactionController::class, 'react']
    );


    // =========================
    // NOTIFIKASI
    // =========================

    // Daftar notifikasi
    Route::get(
        '/notifikasis',
        [NotifikasiController::class, 'index']
    );

    // Tandai satu notifikasi sudah dibaca
    Route::put(
        '/notifikasis/{notifikasi}/read',
        [NotifikasiController::class, 'markAsRead']
    );

    // Tandai semua notifikasi sudah dibaca
    Route::put(
        '/notifikasis/read-all',
        [NotifikasiController::class, 'markAllAsRead']
    );


    // =========================
    // KELOLA PENGGUNA
    // =========================

    Route::get(
        '/users',
        [UserController::class, 'index']
    );

    Route::put(
        '/users/{user}/toggle-status',
        [UserController::class, 'toggleStatus']
    );

    Route::delete(
        '/users/{user}',
        [UserController::class, 'destroy']
    );
});
