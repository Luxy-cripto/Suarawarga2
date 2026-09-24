<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'laporan_id',
        'path',
        'tipe',
        'urutan',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return $this->path ? asset('storage/' . $this->path) : null;
    }

    public function laporan()
    {
        return $this->belongsTo(Laporan::class);
    }
}
