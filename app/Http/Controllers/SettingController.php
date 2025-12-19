<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    /**
     * GET settings
     */
    public function show()
    {
        $setting = Setting::first();

        return response()->json([
            'status' => true,
            'data' => $setting
        ]);
    }

    /**
     * UPDATE milk price (nullable)
     */
    public function update(Request $request)
    {
        $request->validate([
            'milk_price' => 'nullable|numeric|min:0',
        ]);

        $setting = Setting::first();

        if (!$setting) {
            $setting = Setting::create([
                'milk_price' => $request->milk_price,
            ]);
        } else {
            $setting->update([
                'milk_price' => $request->milk_price,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Settings updated',
            'data' => $setting,
        ]);
    }
}

