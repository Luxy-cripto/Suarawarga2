<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    // GET /api/laporans - liat semua laporan
    public function index()
    {
        $laporans = Laporan::with('user')->latest()->get();
        return response()->json($laporans);
    }

    // POST /api/laporans - warga bikin laporan baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'kategori' => 'required|string|max:100',
            'lokasi' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'baru';

        $laporan = Laporan::create($validated);

        return response()->json($laporan, 201);
    }

    // GET /api/laporans/{id} - liat detail satu laporan
    public function show(Laporan $laporan)
    {
        $laporan->load('user');
        return response()->json($laporan);
    }

    // PUT/PATCH /api/laporans/{id} - update laporan (biasanya admin update status)
    public function update(Request $request, Laporan $laporan)
    {
        $validated = $request->validate([
            'judul' => 'sometimes|string|max:255',
            'deskripsi' => 'sometimes|string',
            'kategori' => 'sometimes|string|max:100',
            'status' => 'sometimes|in:baru,diproses,selesai,ditolak',
        ]);

        $laporan->update($validated);

        return response()->json($laporan);
    }

    // DELETE /api/laporans/{id} - hapus laporan
    public function destroy(Laporan $laporan)
    {
        $laporan->delete();
        return response()->json(['message' => 'Laporan berhasil dihapus']);
    }
}
