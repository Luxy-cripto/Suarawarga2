<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index()
    {
        return response()->json(Kategori::withCount('laporans')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'icon' => 'nullable|string|max:10',
            'warna' => 'nullable|string|max:20',
            'deskripsi' => 'nullable|string',
        ]);

        $kategori = Kategori::create($validated);
        return response()->json($kategori, 201);
    }

    public function show(Kategori $kategori)
    {
        return response()->json($kategori);
    }

    public function update(Request $request, Kategori $kategori)
    {
        $validated = $request->validate([
            'nama' => 'sometimes|string|max:100',
            'icon' => 'nullable|string|max:10',
            'warna' => 'nullable|string|max:20',
            'deskripsi' => 'nullable|string',
        ]);

        $kategori->update($validated);
        return response()->json($kategori);
    }

    public function destroy(Kategori $kategori)
    {
        $kategori->delete();
        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}
