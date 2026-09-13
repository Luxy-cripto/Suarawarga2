<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // GET /api/settings
    public function index()
    {
        return response()->json([
            'site_name' => Setting::getValue('site_name', 'SUARAWARGA'),
            'site_tagline' => Setting::getValue('site_tagline', 'Suara masyarakat, perubahan nyata.'),
            'site_description' => Setting::getValue('site_description', 'Platform pelaporan masalah lingkungan dan fasilitas umum untuk warga.'),
        ]);
    }

    // PUT /api/settings
    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'sometimes|string|max:100',
            'site_tagline' => 'sometimes|string|max:255',
            'site_description' => 'sometimes|string|max:1000',
        ]);

        foreach ($validated as $key => $value) {
            Setting::setValue($key, $value);
        }

        return response()->json(['message' => 'Pengaturan berhasil disimpan']);
    }
}
