<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TanggapanReaction extends Model
{
    protected $fillable = [
        'tanggapan_id',
        'user_id',
        'type',
    ];

    public function tanggapan()
    {
        return $this->belongsTo(Tanggapan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
