<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained()->onDelete('cascade');
            $table->string('path');
            // 'sebelum' = foto laporan awal dari warga, 'sesudah' = foto bukti
            // sudah ditangani (dipakai nanti di fitur before/after).
            $table->enum('tipe', ['sebelum', 'sesudah'])->default('sebelum');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_files');
    }
};
