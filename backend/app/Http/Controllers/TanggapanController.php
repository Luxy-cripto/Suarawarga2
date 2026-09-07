<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Models\Notifikasi;
use App\Models\Tanggapan;
use Illuminate\Http\Request;

class TanggapanController extends Controller
{
    public function index(Laporan $laporan)
    {
        $tanggapans = $laporan->tanggapans()
            ->whereNull('parent_id')
            ->with([
                'user',
                'replies.user'
            ])
            ->latest()
            ->get();

        return response()->json($tanggapans);
    }

    public function store(Request $request, Laporan $laporan)
    {
        $validated = $request->validate([
            'pesan' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:tanggapans,id',
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Tanggapan::findOrFail(
                $validated['parent_id']
            );

            if ($parent->laporan_id !== $laporan->id) {
                return response()->json([
                    'message' => 'Komentar induk tidak sesuai dengan laporan.'
                ], 422);
            }
        }

        $tanggapan = Tanggapan::create([
            'laporan_id' => $laporan->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'pesan' => $validated['pesan'],
        ]);

        $tanggapan->load('user');

        $penerimaId = $laporan->user_id;
        $judul = 'Komentar Baru';
        $pesan = $request->user()->name .
            ' memberikan komentar pada laporan "' .
            $laporan->judul . '"';

        if (!empty($validated['parent_id'])) {
            $parent = Tanggapan::findOrFail(
                $validated['parent_id']
            );

            $penerimaId = $parent->user_id;
            $judul = 'Balasan Komentar';

            $pesan = $request->user()->name .
                ' membalas komentar Anda.';
        }

        if ($penerimaId !== $request->user()->id) {
            Notifikasi::create([
                'user_id' => $penerimaId,
                'laporan_id' => $laporan->id,
                'judul' => $judul,
                'pesan' => $pesan,
            ]);
        }

        return response()->json(
            $tanggapan,
            201
        );
    }
}
