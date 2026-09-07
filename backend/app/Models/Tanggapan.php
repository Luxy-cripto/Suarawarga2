<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Laporan;
use App\Models\User;
use App\Models\TanggapanReaction;

class Tanggapan extends Model
{
    protected $fillable = [
        'laporan_id',
        'user_id',
        'parent_id',
        'pesan',
    ];

    protected $appends = [
        'likes_count',
        'dislikes_count',
        'user_reaction',
    ];

    public function laporan()
    {
        return $this->belongsTo(Laporan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Tanggapan::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Tanggapan::class, 'parent_id')
            ->with('user')
            ->latest();
    }

    public function reactions()
    {
        return $this->hasMany(TanggapanReaction::class);
    }

    public function getLikesCountAttribute()
    {
        return $this->reactions()
            ->where('type', 'like')
            ->count();
    }

    public function getDislikesCountAttribute()
    {
        return $this->reactions()
            ->where('type', 'dislike')
            ->count();
    }

    public function getUserReactionAttribute()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return null;
        }

        $reaction = $this->reactions()
            ->where('user_id', $user->id)
            ->first();

        return $reaction?->type;
    }
}
