<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $notifikasi = Notifikasi::where(
            'user_id',
            $request->user()->id
        )
            ->with('laporan')
            ->latest()
            ->get();

        $unreadCount = $notifikasi
            ->where('is_read', false)
            ->count();

        return response()->json([
            'data' => $notifikasi,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(
        Request $request,
        Notifikasi $notifikasi
    ) {
        if (
            (int) $notifikasi->user_id !==
            (int) $request->user()->id
        ) {
            return response()->json([
                'message' => 'Tidak memiliki akses.'
            ], 403);
        }

        $notifikasi->update([
            'is_read' => true
        ]);

        return response()->json([
            'message' => 'Notifikasi sudah dibaca.',
            'data' => $notifikasi
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        Notifikasi::where(
            'user_id',
            $request->user()->id
        )
            ->where('is_read', false)
            ->update([
                'is_read' => true
            ]);

        return response()->json([
            'message' => 'Semua notifikasi sudah dibaca.'
        ]);
    }
}
