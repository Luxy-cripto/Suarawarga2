<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{
    use HasFactory;

    protected $casts = [
        'deadline_at' => 'datetime',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'petugas_id',
        'kategori_id',
        'judul',
        'deskripsi',
        'kategori',
        'lokasi',
        'latitude',
        'longitude',
        'foto',
        'status',
        'alasan_ditolak',
        'deadline_at',
        'assigned_at',
        'started_at',
        'completed_at',
    ];

    protected $appends = ['foto_url'];

    public function getFotoUrlAttribute()
    {
        return $this->foto ? asset('storage/' . $this->foto) : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kategoriRelasi()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // Petugas yang ditugaskan menangani laporan ini
    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function tanggapans()
    {
        return $this->hasMany(Tanggapan::class);
    }

    // Semua lampiran foto laporan ini (foto awal dari warga & nanti foto
    // before/after dari admin), diurutkan sesuai urutan upload.
    public function files()
    {
        return $this->hasMany(LaporanFile::class)->orderBy('urutan');
    }

    // Khusus foto "sebelum" (lampiran awal dari warga saat lapor)
    public function fotoSebelum()
    {
        return $this->files()->where('tipe', 'sebelum');
    }

    // Khusus foto "sesudah" (bukti dari admin, dipakai fitur before/after)
    public function fotoSesudah()
    {
        return $this->files()->where('tipe', 'sesudah');
    }
    // Riwayat kejadian laporan ini (dibuat, diassign, status berubah, dst),
    // diurutkan dari yang paling lama ke paling baru untuk tampilan timeline.
    public function logs()
    {
        return $this->hasMany(LaporanLog::class)->orderBy('created_at');
    }
}
