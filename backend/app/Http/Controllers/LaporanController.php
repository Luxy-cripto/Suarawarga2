<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    // GET /api/laporans
    public function index()
    {
        $laporans = Laporan::with('user', 'kategoriRelasi')
            ->latest()
            ->get();

        return response()->json($laporans);
    }

    // POST /api/laporans
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'kategori_id' => 'required|exists:kategoris,id',
            'lokasi' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('laporan-foto', 'public');
        }

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'baru';

        $laporan = Laporan::create($validated);

        return response()->json([
            'message' => 'Laporan berhasil dibuat',
            'data' => $laporan->load('user', 'kategoriRelasi'),
        ], 201);
    }

    // GET /api/laporans/{id}
    public function show(Laporan $laporan)
    {
        $laporan->load([
            'user',
            'kategoriRelasi',
            'tanggapans.user'
        ]);

        return response()->json([
            'data' => $laporan
        ]);
    }

    // PUT/PATCH /api/laporans/{id}
    public function update(Request $request, Laporan $laporan)
    {
        $validated = $request->validate([
            'judul' => 'sometimes|string|max:255',
            'deskripsi' => 'sometimes|string',
            'kategori_id' => 'sometimes|exists:kategoris,id',
            'lokasi' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'status' => 'sometimes|in:baru,diproses,selesai,ditolak',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('laporan-foto', 'public');
        }

        $laporan->update($validated);

        return response()->json([
            'message' => 'Laporan berhasil diperbarui',
            'data' => $laporan->load('user', 'kategoriRelasi'),
        ]);
    }

    // DELETE /api/laporans/{id}
    public function destroy(Laporan $laporan)
    {
        $laporan->delete();

        return response()->json([
            'message' => 'Laporan berhasil dihapus'
        ]);
    }
}
