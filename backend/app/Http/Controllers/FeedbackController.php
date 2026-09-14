<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    // GET /api/feedbacks
    // Mengambil semua feedback untuk admin
    public function index()
    {
        $feedbacks = Feedback::with('user')
            ->latest()
            ->get();

        return response()->json($feedbacks);
    }

    // GET /api/feedbacks/public
    // Mengambil feedback dengan rating 4 atau 5
    // Untuk ditampilkan sebagai testimoni
    public function publicTestimonials()
    {
        $feedbacks = Feedback::with('user')
            ->where('rating', '>=', 4)
            ->latest()
            ->limit(6)
            ->get();

        return response()->json($feedbacks);
    }

    // POST /api/feedbacks
    // Menyimpan feedback baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'kategori' => 'required|in:bug,saran,pujian,lainnya',
            'pesan' => 'required|string|max:1000',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'baru';

        $feedback = Feedback::create($validated);

        $feedback->load('user');

        return response()->json($feedback, 201);
    }

    // PUT /api/feedbacks/{feedback}
    // Admin mengubah status/catatan
    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:baru,ditinjau,dikerjakan,selesai',
            'catatan_admin' => 'sometimes|nullable|string',
        ]);

        $feedback->update($validated);

        return response()->json($feedback);
    }

    // DELETE /api/feedbacks/{feedback}
    public function destroy(Feedback $feedback)
    {
        $feedback->delete();

        return response()->json([
            'message' => 'Feedback berhasil dihapus'
        ]);
    }
}
