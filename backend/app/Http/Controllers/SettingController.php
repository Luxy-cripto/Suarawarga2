<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // ======================================================
    // GET /api/settings
    // ======================================================

    public function index()
    {
        $logo = Setting::getValue('site_logo', null);

        return response()->json([
            'site_name' => Setting::getValue(
                'site_name',
                'SUARAWARGA'
            ),

            'site_tagline' => Setting::getValue(
                'site_tagline',
                'Suara masyarakat, perubahan nyata.'
            ),

            'site_description' => Setting::getValue(
                'site_description',
                'Platform pelaporan masalah lingkungan dan fasilitas umum untuk warga.'
            ),

            'site_logo' => $logo
                ? asset('storage/' . $logo)
                : null,
        ]);
    }

    // ======================================================
    // PUT /api/settings
    // ======================================================

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'sometimes|string|max:100',

            'site_tagline' => 'sometimes|string|max:255',

            'site_description' => 'sometimes|string|max:1000',

            'site_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ]);

        // ==================================================
        // PENGATURAN TEKS
        // ==================================================

        foreach ([
            'site_name',
            'site_tagline',
            'site_description',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                Setting::setValue(
                    $key,
                    $validated[$key]
                );
            }
        }

        // ==================================================
        // LOGO
        // ==================================================

        if ($request->hasFile('site_logo')) {
            $logoPath = $request
                ->file('site_logo')
                ->store('site', 'public');

            Setting::setValue(
                'site_logo',
                $logoPath
            );
        }

        return response()->json([
            'message' => 'Pengaturan berhasil disimpan',

            'site_logo' => Setting::getValue(
                'site_logo',
                null
            )
                ? asset(
                    'storage/' .
                    Setting::getValue(
                        'site_logo',
                        null
                    )
                )
                : null,
        ]);
    }
}
