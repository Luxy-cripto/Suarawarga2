<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\TanggapanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TanggapanReactionController;
use App\Http\Controllers\FeedbackController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// ======================================================
// AUTH
// ======================================================

// Register
Route::post('/register', [AuthController::class, 'register']);

// Login
Route::post('/login', [AuthController::class, 'login']);

// Lupa password
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);

// Reset password
Route::post('/reset-password', [ForgotPasswordController::class, 'reset']);


// ======================================================
// PUBLIC
// Tidak perlu login
// ======================================================

// ------------------------------------------------------
// FEEDBACK PUBLIC / TESTIMONI
// ------------------------------------------------------

Route::get(
    '/feedbacks/public',
    [FeedbackController::class, 'publicTestimonials']
);


// ------------------------------------------------------
// LAPORAN PUBLIC
// ------------------------------------------------------

// Semua laporan
Route::get(
    '/laporans',
    [LaporanController::class, 'index']
);

// Detail laporan
Route::get(
    '/laporans/{laporan}',
    [LaporanController::class, 'show']
);


// ------------------------------------------------------
// KATEGORI PUBLIC
// ------------------------------------------------------

// Semua kategori
Route::get(
    '/kategoris',
    [KategoriController::class, 'index']
);

// Detail kategori
Route::get(
    '/kategoris/{kategori}',
    [KategoriController::class, 'show']
);


// ------------------------------------------------------
// TANGGAPAN / KOMENTAR PUBLIC
// ------------------------------------------------------

// Semua komentar pada laporan
Route::get(
    '/laporans/{laporan}/tanggapans',
    [TanggapanController::class, 'index']
);


// ======================================================
// ROUTE YANG MEMBUTUHKAN LOGIN
// ======================================================

Route::middleware('auth:sanctum')->group(function () {

    // ==================================================
    // USER LOGIN
    // ==================================================

    // Data user yang sedang login
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


    // ==================================================
    // PROFIL
    // ==================================================

    // Lihat profil
    Route::get(
        '/profile',
        [AuthController::class, 'profile']
    );

    // Update profil
    Route::post(
        '/profile',
        [AuthController::class, 'updateProfile']
    );


    // ==================================================
    // LAPORAN
    // ==================================================

    // Membuat laporan
    Route::post(
        '/laporans',
        [LaporanController::class, 'store']
    );

    // Update laporan menggunakan PUT
    Route::put(
        '/laporans/{laporan}',
        [LaporanController::class, 'update']
    );

    // Update laporan menggunakan PATCH
    Route::patch(
        '/laporans/{laporan}',
        [LaporanController::class, 'update']
    );

    // Hapus laporan
    Route::delete(
        '/laporans/{laporan}',
        [LaporanController::class, 'destroy']
    );


    // ==================================================
    // KATEGORI
    // ==================================================

    // Tambah kategori
    Route::post(
        '/kategoris',
        [KategoriController::class, 'store']
    );

    // Update kategori menggunakan PUT
    Route::put(
        '/kategoris/{kategori}',
        [KategoriController::class, 'update']
    );

    // Update kategori menggunakan PATCH
    Route::patch(
        '/kategoris/{kategori}',
        [KategoriController::class, 'update']
    );

    // Hapus kategori
    Route::delete(
        '/kategoris/{kategori}',
        [KategoriController::class, 'destroy']
    );


    // ==================================================
    // TANGGAPAN / KOMENTAR
    // ==================================================

    // Membuat komentar
    Route::post(
        '/laporans/{laporan}/tanggapans',
        [TanggapanController::class, 'store']
    );


    // ==================================================
    // LIKE / DISLIKE KOMENTAR
    // ==================================================

    Route::post(
        '/tanggapans/{tanggapan}/reaction',
        [TanggapanReactionController::class, 'react']
    );


    // ==================================================
    // FEEDBACK
    // ==================================================

    // Semua feedback untuk admin
    Route::get(
        '/feedbacks',
        [FeedbackController::class, 'index']
    );

    // Mengirim feedback
    Route::post(
        '/feedbacks',
        [FeedbackController::class, 'store']
    );

    // Update feedback
    Route::put(
        '/feedbacks/{feedback}',
        [FeedbackController::class, 'update']
    );

    // Hapus feedback
    Route::delete(
        '/feedbacks/{feedback}',
        [FeedbackController::class, 'destroy']
    );


    // ==================================================
    // NOTIFIKASI
    // ==================================================

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


    // ==================================================
    // KELOLA PENGGUNA
    // ==================================================

    // Semua pengguna
    Route::get(
        '/users',
        [UserController::class, 'index']
    );

    // Aktif / nonaktifkan pengguna
    Route::put(
        '/users/{user}/toggle-status',
        [UserController::class, 'toggleStatus']
    );

    // Hapus pengguna
    Route::delete(
        '/users/{user}',
        [UserController::class, 'destroy']
    );

});
