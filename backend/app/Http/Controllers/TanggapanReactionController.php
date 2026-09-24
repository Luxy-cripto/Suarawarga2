<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\Tanggapan;
use App\Models\TanggapanReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TanggapanReactionController extends Controller
{
    public function react(Request $request, Tanggapan $tanggapan)
    {
        // =====================================================
        // AMBIL DAN NORMALISASI TYPE
        // =====================================================

        $type = strtolower(
            trim(
                (string) $request->input('type')
            )
        );

        // =====================================================
        // VALIDASI
        // =====================================================

        $validator = Validator::make(
            [
                'type' => $type,
            ],
            [
                'type' => [
                    'required',
                    'string',
                    'in:like,dislike',
                ],
            ]
        );

        if ($validator->fails()) {
            Log::warning('REACTION VALIDATION ERROR', [
                'tanggapan_id' => $tanggapan->id,
                'user_id' => $request->user()?->id,
                'received_type' => $request->input('type'),
                'normalized_type' => $type,
                'request_data' => $request->all(),
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'message' => 'Data reaction tidak valid.',
                'errors' => $validator->errors(),
                'received_type' => $request->input('type'),
            ], 422);
        }

        // =====================================================
        // USER LOGIN
        // =====================================================

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Kamu harus login terlebih dahulu.',
            ], 401);
        }

        $userId = $user->id;

        // =====================================================
        // CARI REACTION USER
        // =====================================================

        $reaction = TanggapanReaction::where(
            'tanggapan_id',
            $tanggapan->id
        )
            ->where(
                'user_id',
                $userId
            )
            ->first();

        // =====================================================
        // HELPER RESPONSE
        // =====================================================

        $getReactionData = function ($userReaction) use ($tanggapan) {
            return [
                'tanggapan' => [
                    'id' => $tanggapan->id,
                    'likes_count' => $tanggapan->reactions()
                        ->where('type', 'like')
                        ->count(),
                    'dislikes_count' => $tanggapan->reactions()
                        ->where('type', 'dislike')
                        ->count(),
                    'user_reaction' => $userReaction,
                ],
            ];
        };

        // =====================================================
        // USER SUDAH PUNYA REACTION
        // =====================================================

        if ($reaction) {
            // Klik reaction yang sama = hapus reaction
            if ($reaction->type === $type) {
                $reaction->delete();

                return response()->json([
                    'message' => 'Reaction dihapus.',
                    'data' => $getReactionData(null)['tanggapan'],
                ]);
            }

            // Klik reaction berbeda = ubah reaction
            $reaction->update([
                'type' => $type,
            ]);

            // Jangan membuat notifikasi baru ketika hanya
            // mengganti like menjadi dislike atau sebaliknya.
            return response()->json([
                'message' => 'Reaction berhasil diubah.',
                'data' => $getReactionData($type)['tanggapan'],
            ]);
        }

        // =====================================================
        // BUAT REACTION BARU
        // =====================================================

        $newReaction = TanggapanReaction::create([
            'tanggapan_id' => $tanggapan->id,
            'user_id' => $userId,
            'type' => $type,
        ]);

        // =====================================================
        // NOTIFIKASI PEMILIK KOMENTAR
        // =====================================================

        if ($tanggapan->user_id !== $userId) {
            $judul = $type === 'like'
                ? 'Komentar Disukai'
                : 'Komentar Tidak Disukai';

            $pesan = $type === 'like'
                ? $user->name . ' menyukai komentar Anda.'
                : $user->name . ' tidak menyukai komentar Anda.';

            Notifikasi::create([
                'user_id' => $tanggapan->user_id,
                'laporan_id' => $tanggapan->laporan_id,
                'judul' => $judul,
                'pesan' => $pesan,
            ]);
        }

        // =====================================================
        // RESPONSE
        // =====================================================

        return response()->json([
            'message' => 'Reaction berhasil.',
            'data' => [
                'tanggapan' => [
                    'id' => $tanggapan->id,
                    'likes_count' => $tanggapan->reactions()
                        ->where('type', 'like')
                        ->count(),
                    'dislikes_count' => $tanggapan->reactions()
                        ->where('type', 'dislike')
                        ->count(),
                    'user_reaction' => $type,
                ],
                'reaction' => [
                    'id' => $newReaction->id,
                    'type' => $newReaction->type,
                    'user_id' => $newReaction->user_id,
                    'tanggapan_id' => $newReaction->tanggapan_id,
                ],
            ],
        ]);
    }
}
