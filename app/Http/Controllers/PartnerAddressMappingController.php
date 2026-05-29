<?php

namespace App\Http\Controllers;

use App\NewAddressPartnerMapping;
use App\NewProvince;
use App\NewWard;
use App\User;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerAddressMappingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $query = NewAddressPartnerMapping::with(['newProvince', 'newWard']);

        if ($request->filled('partner_code')) {
            $query->where('partner_code', strtoupper($request->input('partner_code')));
        }

        if ($request->filled('new_province_id')) {
            $query->where('new_province_id', $request->input('new_province_id'));
        }

        if ($request->filled('new_ward_id')) {
            $query->where('new_ward_id', $request->input('new_ward_id'));
        }

        if ($request->filled('mapping_status')) {
            $query->where('mapping_status', $request->input('mapping_status'));
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string)$request->input('keyword'));
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('newWard', function ($wardQuery) use ($keyword) {
                    $wardQuery->where('name', 'LIKE', '%' . $keyword . '%');
                })->orWhereHas('newProvince', function ($provinceQuery) use ($keyword) {
                    $provinceQuery->where('name', 'LIKE', '%' . $keyword . '%');
                })->orWhere('partner_province_code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('partner_district_code', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('partner_ward_code', 'LIKE', '%' . $keyword . '%');
            });
        }

        $legacyAddressResults = collect();
        if ($request->filled('legacy_keyword')) {
            $legacyAddressResults = $this->searchLegacyAddressCodes($request);
        }

        $mappings = $query->orderBy('updated_at', 'desc')->paginate(50);
        $newProvinces = NewProvince::where('is_active', 1)->orderBy('name')->get();
        $selectedWards = collect();
        if ($request->filled('new_province_id')) {
            $selectedWards = NewWard::where('new_province_id', $request->input('new_province_id'))
                ->where('is_active', 1)
                ->orderBy('name')
                ->get();
        }

        return view('partner_address_mappings.index', compact('mappings', 'newProvinces', 'selectedWards', 'legacyAddressResults'));
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $data = $this->validateMapping($request);
        $newWard = NewWard::findOrFail($data['new_ward_id']);

        NewAddressPartnerMapping::updateOrCreate(
            [
                'new_ward_id' => $newWard->id,
                'partner_code' => $data['partner_code'],
            ],
            [
                'new_province_id' => $newWard->new_province_id,
                'partner_province_code' => $data['partner_province_code'],
                'partner_district_code' => $data['partner_district_code'] ?? null,
                'partner_ward_code' => $data['partner_ward_code'] ?? null,
                'mapping_status' => $data['mapping_status'],
                'note' => $data['note'] ?? null,
            ]
        );

        Flash::success('Lưu mapping API thành công.');
        return redirect()->route('partner_address_mappings.index', $request->query());
    }

    public function update(Request $request, NewAddressPartnerMapping $partnerAddressMapping)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'partner_province_code' => 'required|string|max:50',
            'partner_district_code' => 'nullable|string|max:50',
            'partner_ward_code' => 'nullable|string|max:50',
            'mapping_status' => 'required|in:mapped,missing,manual_review',
            'note' => 'nullable|string|max:1000',
        ]);

        $partnerAddressMapping->update($data);

        Flash::success('Cập nhật mapping API thành công.');
        return back();
    }

    private function validateMapping(Request $request)
    {
        $data = $request->validate([
            'partner_code' => 'required|string|in:VTP,EMS',
            'new_ward_id' => 'required|exists:new_wards,id',
            'partner_province_code' => 'required|string|max:50',
            'partner_district_code' => 'nullable|string|max:50',
            'partner_ward_code' => 'nullable|string|max:50',
            'mapping_status' => 'required|in:mapped,missing,manual_review',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($data['partner_code'] === 'VTP' && (empty($data['partner_district_code']) || empty($data['partner_ward_code']))) {
            throw ValidationException::withMessages([
                'partner_ward_code' => 'Mapping Viettel cần đủ mã Tỉnh/Huyện/Xã.',
            ]);
        }

        return $data;
    }

    private function authorizeAccess()
    {
        if (!auth()->check() || !in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            abort(403, 'Bạn không có quyền truy cập trang Mapping API.');
        }
    }

    private function searchLegacyAddressCodes(Request $request)
    {
        $keyword = trim((string)$request->input('legacy_keyword'));
        if ($keyword === '') {
            return collect();
        }

        $partnerCode = strtoupper((string)$request->input('legacy_partner_code', ''));

        $query = DB::table('wards')
            ->join('districts', 'districts.id', '=', 'wards.district_id')
            ->join('citys', 'citys.id', '=', 'districts.city_id')
            ->select([
                'citys.city_name',
                'districts.district_name',
                'wards.ward_name',
                'citys.city_code as vtp_province_code',
                'districts.district_code as vtp_district_code',
                'wards.ward_code as vtp_ward_code',
                'citys.ems_code as ems_province_code',
                'districts.ems_code as ems_district_code',
                'wards.ems_code as ems_ward_code',
            ]);

        $parts = array_values(array_filter(array_map('trim', explode(',', $keyword))));
        if (count($parts) >= 3) {
            $wardKeyword = $this->stripLegacyAddressPrefix($parts[0]);
            $districtKeyword = $this->stripLegacyAddressPrefix($parts[1]);
            $cityKeyword = $this->stripLegacyAddressPrefix($parts[2]);

            $query->where('wards.ward_name', 'LIKE', '%' . $wardKeyword . '%')
                ->where('districts.district_name', 'LIKE', '%' . $districtKeyword . '%')
                ->where('citys.city_name', 'LIKE', '%' . $cityKeyword . '%');
        } elseif (count($parts) === 2) {
            $firstKeyword = $this->stripLegacyAddressPrefix($parts[0]);
            $secondKeyword = $this->stripLegacyAddressPrefix($parts[1]);

            $query->where(function ($q) use ($firstKeyword, $secondKeyword) {
                $q->where(function ($subQuery) use ($firstKeyword, $secondKeyword) {
                    $subQuery->where('wards.ward_name', 'LIKE', '%' . $firstKeyword . '%')
                        ->where('districts.district_name', 'LIKE', '%' . $secondKeyword . '%');
                })->orWhere(function ($subQuery) use ($firstKeyword, $secondKeyword) {
                    $subQuery->where('districts.district_name', 'LIKE', '%' . $firstKeyword . '%')
                        ->where('citys.city_name', 'LIKE', '%' . $secondKeyword . '%');
                })->orWhere(function ($subQuery) use ($firstKeyword, $secondKeyword) {
                    $subQuery->where('wards.ward_name', 'LIKE', '%' . $firstKeyword . '%')
                        ->where('citys.city_name', 'LIKE', '%' . $secondKeyword . '%');
                });
            });
        } else {
            $singleKeyword = $this->stripLegacyAddressPrefix($keyword);
            $query->where(function ($q) use ($singleKeyword) {
                $q->where('citys.city_name', 'LIKE', '%' . $singleKeyword . '%')
                    ->orWhere('districts.district_name', 'LIKE', '%' . $singleKeyword . '%')
                    ->orWhere('wards.ward_name', 'LIKE', '%' . $singleKeyword . '%');
            });
        }

        if ($partnerCode === 'VTP') {
            $query->whereNotNull('citys.city_code')
                ->whereNotNull('districts.district_code')
                ->whereNotNull('wards.ward_code');
        } elseif ($partnerCode === 'EMS') {
            $query->whereNotNull('citys.ems_code')
                ->whereNotNull('districts.ems_code')
                ->whereNotNull('wards.ems_code');
        }

        return $query->orderBy('citys.city_name')
            ->orderBy('districts.district_name')
            ->orderBy('wards.ward_name')
            ->limit(100)
            ->get();
    }

    private function stripLegacyAddressPrefix($value)
    {
        $value = trim((string)$value);
        $value = preg_replace('/^(tỉnh|tp|tp\.|thành phố|huyện|quận|tx|tx\.|thị xã|xã|phường|tt|tt\.|thị trấn)\s+/iu', '', $value);

        return trim($value);
    }
}
