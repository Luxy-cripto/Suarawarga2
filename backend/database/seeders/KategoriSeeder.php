<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = [
            ['nama' => 'Jalan Rusak', 'icon' => '🛣️', 'warna' => 'orange', 'deskripsi' => 'Jalan berlubang atau mengalami kerusakan'],
            ['nama' => 'Sampah', 'icon' => '🗑️', 'warna' => 'green', 'deskripsi' => 'Sampah menumpuk atau belum terangkut'],
            ['nama' => 'Lampu Jalan', 'icon' => '💡', 'warna' => 'yellow', 'deskripsi' => 'Lampu penerangan mati atau rusak'],
            ['nama' => 'Selokan', 'icon' => '🌊', 'warna' => 'blue', 'deskripsi' => 'Saluran air tersumbat atau bermasalah'],
            ['nama' => 'Fasilitas Umum', 'icon' => '🏞️', 'warna' => 'purple', 'deskripsi' => 'Fasilitas publik rusak atau tidak layak'],
        ];

        foreach ($kategoris as $kategori) {
            Kategori::updateOrCreate(['nama' => $kategori['nama']], $kategori);
        }
    }
}
