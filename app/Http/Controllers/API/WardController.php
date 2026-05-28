<?php

namespace App\Http\Controllers\API;

use App\District;
use App\Http\Controllers\Controller;
use App\Ward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WardController extends Controller
{
    public function getWards(Request $request) {
        $district_id = $request->district_id;
        $districts = Ward::where('district_id', $district_id)->get();
        return response()->json($districts);
    }

    public function getNewWards(Request $request, $province_id) {
        try {
            $wards = \App\NewWard::query()
                ->select('id', 'name')
                ->where('new_province_id', $province_id)
                ->where('is_active', 1)
                ->orderBy('name')
                ->get()
                ->map(function ($ward) {
                    return [
                        'id' => $ward->id,
                        'name' => trim((string)$ward->name),
                    ];
                })
                ->values();

            return response()->json($wards, 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('Cannot load new wards', [
                'province_id' => $province_id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Không tải được danh sách xã/phường. Vui lòng thử lại hoặc liên hệ quản trị.',
                'data' => [],
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }
}
