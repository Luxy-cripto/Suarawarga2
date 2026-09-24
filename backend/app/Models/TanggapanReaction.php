<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TanggapanReaction extends Model
{
    protected $fillable = [
        'tanggapan_id',
        'user_id',
        'type',
    ];

    public function tanggapan(): BelongsTo
    {
        return $this->belongsTo(
            Tanggapan::class,
            'tanggapan_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}
