<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\TanggapanController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TanggapanReactionController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\SettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ======================================================
// SETTING
// ======================================================

Route::get('/settings', [
    SettingController::class,
    'index'
]);

Route::put('/settings', [
    SettingController::class,
    'update'
]);

// ======================================================
// AUTH
// ======================================================

// Register & Login biasa
Route::post('/register', [
    AuthController::class,
    'register'
]);

Route::post('/login', [
    AuthController::class,
    'login'
]);

// ======================================================
// GOOGLE LOGIN
// ======================================================

Route::get('/auth/google', [
    AuthController::class,
    'redirectToGoogle'
]);

Route::get('/auth/google/callback', [
    AuthController::class,
    'handleGoogleCallback'
]);

// ======================================================
// FORGOT PASSWORD
// ======================================================

Route::post('/forgot-password', [
    ForgotPasswordController::class,
    'sendResetLink'
]);

Route::post('/reset-password', [
    ForgotPasswordController::class,
    'reset'
]);

// ======================================================
// PUBLIC ROUTES
// Tidak membutuhkan login
// ======================================================

// Feedback / Testimoni
Route::get('/feedbacks/public', [
    FeedbackController::class,
    'publicTestimonials'
]);

// ======================================================
// LAPORAN PUBLIC
// ======================================================

Route::get('/laporans', [
    LaporanController::class,
    'index'
]);

// ======================================================
// EXPORT LAPORAN
// ADMIN
// ======================================================

// HARUS sebelum /laporans/{laporan}
Route::get('/laporans/export', [
    LaporanController::class,
    'export'
])->middleware([
    'auth:sanctum',
    'role:admin'
]);

// ======================================================
// TUGAS PETUGAS
// ======================================================

// HARUS sebelum /laporans/{laporan}
Route::get('/laporans/tugas-saya', [
    LaporanController::class,
    'tugasSaya'
])->middleware([
    'auth:sanctum',
    'role:petugas'
]);

Route::get('/laporans/{laporan}', [
    LaporanController::class,
    'show'
]);

// ======================================================
// KATEGORI PUBLIC
// ======================================================

Route::get('/kategoris', [
    KategoriController::class,
    'index'
]);

Route::get('/kategoris/{kategori}', [
    KategoriController::class,
    'show'
]);

// ======================================================
// TANGGAPAN / KOMENTAR PUBLIC
// ======================================================

Route::get('/laporans/{laporan}/tanggapans', [
    TanggapanController::class,
    'index'
]);

// ======================================================
// ROUTE YANG MEMBUTUHKAN LOGIN
// ======================================================

Route::middleware('auth:sanctum')->group(function () {

    // ==================================================
    // USER LOGIN
    // ==================================================

    Route::get('/user', function (Request $request) {
        return response()->json(
            $request->user()
        );
    });

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

    // ==================================================
    // PROFIL
    // ==================================================

    Route::get('/profile', [
        AuthController::class,
        'profile'
    ]);

    Route::post('/profile', [
        AuthController::class,
        'updateProfile'
    ]);

    // ==================================================
    // LAPORAN
    // ==================================================

    Route::post('/laporans', [
        LaporanController::class,
        'store'
    ]);

    Route::put('/laporans/{laporan}', [
        LaporanController::class,
        'update'
    ]);

    Route::patch('/laporans/{laporan}', [
        LaporanController::class,
        'update'
    ]);

    Route::delete('/laporans/{laporan}', [
        LaporanController::class,
        'destroy'
    ]);

    Route::delete('/laporans/{laporan}/files/{file}', [
        LaporanController::class,
        'destroyFile'
    ]);

    // ==================================================
    // ASSIGN PETUGAS - ADMIN
    // ==================================================

    Route::put('/laporans/{laporan}/assign', [
        LaporanController::class,
        'assignPetugas'
    ])->middleware('role:admin');

    // ==================================================
    // UPDATE PROGRESS - PETUGAS
    // ==================================================

    Route::put('/laporans/{laporan}/progress', [
        LaporanController::class,
        'updateProgress'
    ])->middleware('role:petugas');

    // ==================================================
    // KATEGORI
    // ==================================================

    Route::post('/kategoris', [
        KategoriController::class,
        'store'
    ]);

    Route::put('/kategoris/{kategori}', [
        KategoriController::class,
        'update'
    ]);

    Route::patch('/kategoris/{kategori}', [
        KategoriController::class,
        'update'
    ]);

    Route::delete('/kategoris/{kategori}', [
        KategoriController::class,
        'destroy'
    ]);

    // ==================================================
    // TANGGAPAN / KOMENTAR
    // ==================================================

    Route::post('/laporans/{laporan}/tanggapans', [
        TanggapanController::class,
        'store'
    ]);

    // ==================================================
    // LIKE / DISLIKE KOMENTAR
    // ==================================================

    Route::post('/tanggapans/{tanggapan}/reaction', [
        TanggapanReactionController::class,
        'react'
    ]);

    // ==================================================
    // FEEDBACK
    // ==================================================

    Route::get('/feedbacks', [
        FeedbackController::class,
        'index'
    ]);

    Route::post('/feedbacks', [
        FeedbackController::class,
        'store'
    ]);

    Route::put('/feedbacks/{feedback}', [
        FeedbackController::class,
        'update'
    ]);

    Route::delete('/feedbacks/{feedback}', [
        FeedbackController::class,
        'destroy'
    ]);

    // ==================================================
    // NOTIFIKASI
    // ==================================================

    Route::get('/notifikasis', [
        NotifikasiController::class,
        'index'
    ]);

    Route::put('/notifikasis/{notifikasi}/read', [
        NotifikasiController::class,
        'markAsRead'
    ]);

    Route::put('/notifikasis/read-all', [
        NotifikasiController::class,
        'markAllAsRead'
    ]);

    // ==================================================
    // KELOLA PENGGUNA
    // ==================================================

    Route::get('/users', [
        UserController::class,
        'index'
    ]);

    // Daftar petugas - ADMIN
    Route::get('/users/petugas', [
        UserController::class,
        'petugas'
    ])->middleware('role:admin');

    // Buat akun petugas - ADMIN
    Route::post('/users/petugas', [
        UserController::class,
        'storePetugas'
    ])->middleware('role:admin');

    // Aktif / nonaktif akun
    Route::put('/users/{user}/toggle-status', [
        UserController::class,
        'toggleStatus'
    ]);

    // Hapus user
    Route::delete('/users/{user}', [
        UserController::class,
        'destroy'
    ]);
});
