<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\Tanggapan;
use App\Models\TanggapanReaction;
use Illuminate\Http\Request;

class TanggapanReactionController extends Controller
{
    public function react(
        Request $request,
        Tanggapan $tanggapan
    ) {
        $validated = $request->validate([
            'type' => 'required|in:like,dislike',
        ]);

        $userId = $request->user()->id;

        $reaction = TanggapanReaction::where(
            'tanggapan_id',
            $tanggapan->id
        )
            ->where(
                'user_id',
                $userId
            )
            ->first();

        if ($reaction) {
            if ($reaction->type === $validated['type']) {
                $reaction->delete();

                return response()->json([
                    'message' => 'Reaction dihapus',
                    'likes_count' => $tanggapan->reactions()
                        ->where('type', 'like')
                        ->count(),
                    'dislikes_count' => $tanggapan->reactions()
                        ->where('type', 'dislike')
                        ->count(),
                    'user_reaction' => null,
                ]);
            }

            $reaction->update([
                'type' => $validated['type']
            ]);
        } else {
            TanggapanReaction::create([
                'tanggapan_id' => $tanggapan->id,
                'user_id' => $userId,
                'type' => $validated['type'],
            ]);

            if ($tanggapan->user_id !== $userId) {
                $nama = $request->user()->name;

                Notifikasi::create([
                    'user_id' => $tanggapan->user_id,
                    'laporan_id' => $tanggapan->laporan_id,
                    'judul' => $validated['type'] === 'like'
                        ? 'Komentar Disukai'
                        : 'Komentar Tidak Disukai',
                    'pesan' => $nama .
                        (
                            $validated['type'] === 'like'
                                ? ' menyukai komentar Anda.'
                                : ' tidak menyukai komentar Anda.'
                        ),
                ]);
            }
        }

        return response()->json([
            'message' => 'Reaction berhasil',
            'likes_count' => $tanggapan->reactions()
                ->where('type', 'like')
                ->count(),
            'dislikes_count' => $tanggapan->reactions()
                ->where('type', 'dislike')
                ->count(),
            'user_reaction' => $validated['type'],
        ]);
    }
}
