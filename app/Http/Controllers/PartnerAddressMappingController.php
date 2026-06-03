<?php

namespace App\Http\Controllers;

use App\NewAddressPartnerMapping;
use App\NewProvince;
use App\NewWard;
use App\User;
use App\City;
use App\District;
use App\Ward;
use App\Services\EmsService;
use Flash;
use GuzzleHttp\Client;
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

        $mappings = $query->orderBy('updated_at', 'desc')->paginate(50);
        $newProvinces = NewProvince::where('is_active', 1)->orderBy('name')->get();
        $legacyCities = City::orderBy('city_name')->get();
        $selectedWards = collect();
        if ($request->filled('new_province_id')) {
            $selectedWards = NewWard::where('new_province_id', $request->input('new_province_id'))
                ->where('is_active', 1)
                ->orderBy('name')
                ->get();
        }

        return view('partner_address_mappings.index', compact('mappings', 'newProvinces', 'selectedWards', 'legacyCities'));
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

    public function convertOldToNew(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'ward_id' => 'required|exists:wards,id',
            'partner_code' => 'nullable|string|in:VTP,EMS',
        ]);

        $legacy = $this->legacyAddressByWardId($data['ward_id']);
        if (!$legacy) {
            return response()->json(['message' => 'Không tìm thấy địa chỉ cũ.'], 404);
        }

        $partnerCodes = !empty($data['partner_code']) ? [strtoupper($data['partner_code'])] : ['VTP', 'EMS'];
        $results = [];

        foreach ($partnerCodes as $partnerCode) {
            $codes = $this->legacyCodesForPartner($legacy, $partnerCode);
            if (!$codes['province_code']) {
                continue;
            }

            $query = NewAddressPartnerMapping::with(['newProvince', 'newWard'])
                ->where('partner_code', $partnerCode)
                ->where('partner_province_code', $codes['province_code']);

            if (!empty($codes['district_code'])) {
                $query->where('partner_district_code', $codes['district_code']);
            }

            if (!empty($codes['ward_code'])) {
                $query->where('partner_ward_code', $codes['ward_code']);
            }

            foreach ($query->limit(20)->get() as $mapping) {
                $results[] = $this->formatConversionMapping($mapping, $legacy, $partnerCode);
            }
        }

        $apiError = null;
        if (empty($results)) {
            $converted = $this->convertOldToNewFromDiachiApi($legacy, $partnerCodes);
            $results = $converted['results'];
            $apiError = $converted['error'];
        }

        return response()->json([
            'legacy' => $this->formatLegacyAddress($legacy),
            'results' => $results,
            'api_error' => $apiError,
        ]);
    }

    public function convertNewToOld(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'new_ward_id' => 'required|exists:new_wards,id',
            'partner_code' => 'nullable|string|in:VTP,EMS',
        ]);

        $newWard = NewWard::with('newProvince')->findOrFail($data['new_ward_id']);
        $query = NewAddressPartnerMapping::with(['newProvince', 'newWard'])
            ->where('new_ward_id', $newWard->id)
            ->where('mapping_status', 'mapped');

        if (!empty($data['partner_code'])) {
            $query->where('partner_code', strtoupper($data['partner_code']));
        }

        $results = [];
        foreach ($query->get() as $mapping) {
            $legacy = $this->legacyAddressByPartnerCodes(
                $mapping->partner_code,
                $mapping->partner_province_code,
                $mapping->partner_district_code,
                $mapping->partner_ward_code
            );

            $results[] = $this->formatConversionMapping($mapping, $legacy, $mapping->partner_code);
        }

        $apiError = null;
        if (empty($results)) {
            $partnerCodes = !empty($data['partner_code']) ? [strtoupper($data['partner_code'])] : ['VTP', 'EMS'];
            $converted = $this->convertNewToOldFromDiachiApi($newWard, $partnerCodes);
            $results = $converted['results'];
            $apiError = $converted['error'];
        }

        return response()->json([
            'new_address' => [
                'province_id' => $newWard->new_province_id,
                'province_name' => optional($newWard->newProvince)->name,
                'ward_id' => $newWard->id,
                'ward_name' => $newWard->name,
            ],
            'results' => $results,
            'api_error' => $apiError,
        ]);
    }

    public function missing(Request $request)
    {
        $this->authorizeAccess();

        $data = $request->validate([
            'partner_code' => 'nullable|string|in:VTP,EMS',
            'error_only' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:500',
            'scan_date' => 'nullable|date_format:Y-m-d',
        ]);

        $partners = !empty($data['partner_code']) ? [strtoupper($data['partner_code'])] : ['VTP', 'EMS'];
        $limit = $data['limit'] ?? 100;
        $errorOnly = !empty($data['error_only']);
        $scanDate = $data['scan_date'] ?? null;
        $usage = $this->newAddressUsage($errorOnly, $scanDate, $partners);
        $wardIds = $usage->pluck('new_ward_id')->filter()->unique()->values()->all();

        if (empty($wardIds)) {
            return response()->json([
                'total' => 0,
                'results' => [],
                'scan_date' => $scanDate,
                'summary' => $this->missingMappingSummary([]),
            ]);
        }

        $existingMappings = NewAddressPartnerMapping::whereIn('new_ward_id', $wardIds)
            ->whereIn('partner_code', $partners)
            ->get()
            ->keyBy(function ($mapping) {
                return $mapping->new_ward_id . ':' . $mapping->partner_code;
            });

        $results = [];
        foreach ($usage as $item) {
            $missingPartners = [];
            foreach ($partners as $partnerCode) {
                $mapping = $existingMappings->get($item['new_ward_id'] . ':' . $partnerCode);
                if (!$this->isCompletePartnerMapping($mapping, $partnerCode)) {
                    $missingPartners[] = $partnerCode;
                }
            }

            if (empty($missingPartners)) {
                continue;
            }

            $results[] = [
                'new_province_id' => $item['new_province_id'],
                'new_province_name' => $item['new_province_name'],
                'new_ward_id' => $item['new_ward_id'],
                'new_ward_name' => $item['new_ward_name'],
                'missing_partners' => $missingPartners,
                'sender_count' => $item['sender_count'],
                'receiver_count' => $item['receiver_count'],
                'order_count' => $item['order_count'],
                'sample_order_dates' => array_slice($item['order_dates'], 0, 5),
                'sample_order_codes' => array_slice($item['order_codes'], 0, 5),
            ];
        }

        usort($results, function ($a, $b) {
            if ($a['order_count'] === $b['order_count']) {
                return strcmp($a['new_province_name'] . $a['new_ward_name'], $b['new_province_name'] . $b['new_ward_name']);
            }

            return $b['order_count'] <=> $a['order_count'];
        });

        $limitedResults = array_slice($results, 0, $limit);

        return response()->json([
            'total' => count($results),
            'results' => $limitedResults,
            'scan_date' => $scanDate,
            'summary' => $this->missingMappingSummary($results, count($limitedResults)),
        ]);
    }

    private function missingMappingSummary(array $results, $displayedCount = 0)
    {
        $summary = [
            'ward_count' => count($results),
            'displayed_count' => $displayedCount,
            'order_count' => 0,
            'sender_count' => 0,
            'receiver_count' => 0,
            'missing_vtp_count' => 0,
            'missing_ems_count' => 0,
        ];

        foreach ($results as $item) {
            $summary['order_count'] += (int)($item['order_count'] ?? 0);
            $summary['sender_count'] += (int)($item['sender_count'] ?? 0);
            $summary['receiver_count'] += (int)($item['receiver_count'] ?? 0);
            $missingPartners = $item['missing_partners'] ?? [];

            if (in_array('VTP', $missingPartners, true)) {
                $summary['missing_vtp_count']++;
            }

            if (in_array('EMS', $missingPartners, true)) {
                $summary['missing_ems_count']++;
            }
        }

        return $summary;
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

    private function newAddressUsage($errorOnly = false, $scanDate = null, array $partners = ['VTP', 'EMS'])
    {
        $rows = collect()
            ->merge($this->newAddressUsageRows('senders', 'sender', $errorOnly, $scanDate, $partners))
            ->merge($this->newAddressUsageRows('receivers', 'receiver', $errorOnly, $scanDate, $partners));

        return $rows->groupBy('new_ward_id')->map(function ($group) {
            $first = $group->first();
            $orderCodes = collect();
            $orderIds = collect();
            $orderDates = collect();
            $senderCount = 0;
            $receiverCount = 0;

            foreach ($group as $row) {
                $codes = array_filter(array_map('trim', explode(',', (string)$row->order_codes)));
                $ids = array_filter(array_map('trim', explode(',', (string)$row->order_ids)));
                $dates = array_filter(array_map('trim', explode(',', (string)$row->order_dates)));
                $orderCodes = $orderCodes->merge($codes);
                $orderIds = $orderIds->merge($ids);
                $orderDates = $orderDates->merge($dates);

                if ($row->address_role === 'sender') {
                    $senderCount += (int)$row->address_count;
                } else {
                    $receiverCount += (int)$row->address_count;
                }
            }

            return [
                'new_province_id' => $first->new_province_id,
                'new_province_name' => $first->new_province_name,
                'new_ward_id' => $first->new_ward_id,
                'new_ward_name' => $first->new_ward_name,
                'sender_count' => $senderCount,
                'receiver_count' => $receiverCount,
                'order_count' => $orderIds->unique()->count(),
                'order_codes' => $orderCodes->unique()->values()->all(),
                'order_dates' => $orderDates->unique()->values()->all(),
            ];
        })->values();
    }

    private function newAddressUsageRows($addressTable, $role, $errorOnly, $scanDate = null, array $partners = ['VTP', 'EMS'])
    {
        $logPartnerCodes = $this->logPartnerCodesForMissingScan($partners);

        $query = DB::table('orders')
            ->join($addressTable, $addressTable . '.id', '=', 'orders.' . rtrim($addressTable, 's') . '_id')
            ->join('new_wards', 'new_wards.id', '=', $addressTable . '.new_ward_id')
            ->leftJoin('new_provinces', 'new_provinces.id', '=', 'new_wards.new_province_id')
            ->where($addressTable . '.address_scheme', 'new')
            ->whereNotNull($addressTable . '.new_ward_id')
            ->whereExists(function ($existsQuery) use ($logPartnerCodes) {
                $existsQuery->select(DB::raw(1))
                    ->from('order_partner_logs')
                    ->whereColumn('order_partner_logs.order_id', 'orders.id')
                    ->whereIn('order_partner_logs.partner_code', $logPartnerCodes);
            });

        if ($scanDate) {
            $query->where(function ($q) use ($scanDate) {
                $q->whereDate('orders.order_date', $scanDate)
                    ->orWhere(function ($fallbackQuery) use ($scanDate) {
                        $fallbackQuery->whereNull('orders.order_date')
                            ->whereDate('orders.created_at', $scanDate);
                    });
            });
        }

        if ($errorOnly) {
            $query->where(function ($q) {
                $q->where('orders.push_error', 'LIKE', '%mapping%')
                    ->orWhere('orders.push_error', 'LIKE', '%Mapping%')
                    ->orWhere('orders.push_error', 'LIKE', '%xã%')
                    ->orWhere('orders.push_error', 'LIKE', '%phường%')
                    ->orWhere('orders.push_error', 'LIKE', '%Tỉnh%')
                    ->orWhere('orders.push_error', 'LIKE', '%Thành phố%');
            });
        }

        return $query
            ->groupBy(
                $addressTable . '.new_ward_id',
                'new_wards.new_province_id',
                'new_wards.name',
                'new_provinces.name'
            )
            ->select([
                DB::raw("'" . $role . "' as address_role"),
                $addressTable . '.new_ward_id',
                'new_wards.new_province_id',
                'new_wards.name as new_ward_name',
                'new_provinces.name as new_province_name',
                DB::raw('COUNT(DISTINCT orders.id) as address_count'),
                DB::raw('GROUP_CONCAT(DISTINCT orders.id ORDER BY orders.id DESC SEPARATOR ",") as order_ids'),
                DB::raw('GROUP_CONCAT(DISTINCT orders.order_code ORDER BY orders.id DESC SEPARATOR ",") as order_codes'),
                DB::raw('GROUP_CONCAT(DISTINCT COALESCE(orders.order_date, DATE(orders.created_at)) ORDER BY orders.id DESC SEPARATOR ",") as order_dates'),
            ])
            ->get();
    }

    private function logPartnerCodesForMissingScan(array $partners)
    {
        $codes = [];

        foreach ($partners as $partner) {
            if ($partner === 'VTP') {
                $codes[] = 'VTP';
                $codes[] = 'VIETTEL_POST';
                continue;
            }

            if ($partner === 'EMS') {
                $codes[] = 'EMS';
            }
        }

        return array_values(array_unique($codes));
    }

    private function isCompletePartnerMapping($mapping, $partnerCode)
    {
        if (!$mapping || $mapping->mapping_status !== 'mapped' || empty($mapping->partner_province_code)) {
            return false;
        }

        if ($partnerCode === 'VTP') {
            return !empty($mapping->partner_district_code) && !empty($mapping->partner_ward_code);
        }

        return true;
    }

    private function legacyAddressByWardId($wardId)
    {
        return DB::table('wards')
            ->join('districts', 'districts.id', '=', 'wards.district_id')
            ->join('citys', 'citys.id', '=', 'districts.city_id')
            ->where('wards.id', $wardId)
            ->select([
                'citys.id as city_id',
                'districts.id as district_id',
                'wards.id as ward_id',
                'citys.city_name',
                'districts.district_name',
                'wards.ward_name',
                'citys.city_code as vtp_province_code',
                'districts.district_code as vtp_district_code',
                'wards.ward_code as vtp_ward_code',
                'citys.ems_code as ems_province_code',
                'districts.ems_code as ems_district_code',
                'wards.ems_code as ems_ward_code',
            ])
            ->first();
    }

    private function legacyAddressByPartnerCodes($partnerCode, $provinceCode, $districtCode, $wardCode)
    {
        if (!$provinceCode) {
            return null;
        }

        if ($partnerCode === 'EMS') {
            $city = City::where('ems_code', $provinceCode)->first();
            $district = $districtCode ? District::where('ems_code', $districtCode)->first() : null;
            $ward = $wardCode ? Ward::where('ems_code', $wardCode)->first() : null;
        } else {
            $city = City::where('city_code', $provinceCode)->first();
            $district = $districtCode ? District::where('district_code', $districtCode)->first() : null;
            $ward = $wardCode ? Ward::where('ward_code', $wardCode)->first() : null;
        }

        return (object)[
            'city_id' => $city->id ?? null,
            'district_id' => $district->id ?? null,
            'ward_id' => $ward->id ?? null,
            'city_name' => $city->city_name ?? null,
            'district_name' => $district->district_name ?? null,
            'ward_name' => $ward->ward_name ?? null,
            'vtp_province_code' => $city->city_code ?? null,
            'vtp_district_code' => $district->district_code ?? null,
            'vtp_ward_code' => $ward->ward_code ?? null,
            'ems_province_code' => $city->ems_code ?? null,
            'ems_district_code' => $district->ems_code ?? null,
            'ems_ward_code' => $ward->ems_code ?? null,
        ];
    }

    private function legacyCodesForPartner($legacy, $partnerCode)
    {
        if ($partnerCode === 'EMS') {
            return [
                'province_code' => $legacy->ems_province_code,
                'district_code' => $legacy->ems_district_code,
                'ward_code' => $legacy->ems_ward_code,
            ];
        }

        return [
            'province_code' => $legacy->vtp_province_code,
            'district_code' => $legacy->vtp_district_code,
            'ward_code' => $legacy->vtp_ward_code,
        ];
    }

    private function formatLegacyAddress($legacy)
    {
        if (!$legacy) {
            return null;
        }

        return [
            'city_id' => $legacy->city_id ?? null,
            'district_id' => $legacy->district_id ?? null,
            'ward_id' => $legacy->ward_id ?? null,
            'city_name' => $legacy->city_name ?? null,
            'district_name' => $legacy->district_name ?? null,
            'ward_name' => $legacy->ward_name ?? null,
            'text' => trim(($legacy->ward_name ?? '') . ', ' . ($legacy->district_name ?? '') . ', ' . ($legacy->city_name ?? ''), ' ,'),
            'vtp' => [
                'province_code' => $legacy->vtp_province_code ?? null,
                'district_code' => $legacy->vtp_district_code ?? null,
                'ward_code' => $legacy->vtp_ward_code ?? null,
            ],
            'ems' => [
                'province_code' => $legacy->ems_province_code ?? null,
                'district_code' => $legacy->ems_district_code ?? null,
                'ward_code' => $legacy->ems_ward_code ?? null,
            ],
        ];
    }

    private function formatConversionMapping($mapping, $legacy, $partnerCode)
    {
        return [
            'partner_code' => $partnerCode,
            'new_address' => [
                'province_id' => $mapping->new_province_id,
                'province_name' => optional($mapping->newProvince)->name,
                'ward_id' => $mapping->new_ward_id,
                'ward_name' => optional($mapping->newWard)->name,
            ],
            'legacy_address' => $this->formatLegacyAddress($legacy),
            'partner_codes' => [
                'province_code' => $mapping->partner_province_code,
                'district_code' => $mapping->partner_district_code,
                'ward_code' => $mapping->partner_ward_code,
            ],
            'mapping_status' => $mapping->mapping_status,
            'note' => $mapping->note,
        ];
    }

    private function convertOldToNewFromDiachiApi($legacy, array $partnerCodes)
    {
        $diachiOldAddress = $this->findDiachiOldAddress($legacy);
        if (!$diachiOldAddress) {
            return [
                'results' => [],
                'error' => $this->diachiError('DIACHI_OLD_CODE_NOT_FOUND', 'Không tìm thấy địa chỉ cũ tương ứng trong danh mục diachi.io. Vui lòng kiểm tra lại Tỉnh/Huyện/Xã cũ.'),
            ];
        }

        $payload = [
            'direction' => 'old-to-new',
            'provinceCode' => (string)($diachiOldAddress['province_code'] ?? ''),
            'districtCode' => (string)($diachiOldAddress['district_code'] ?? ''),
            'wardCode' => (string)($diachiOldAddress['ward_code'] ?? ''),
            'detailAddress' => '',
        ];

        $response = $this->requestDiachiConvertAddress($payload);
        if (!empty($response['error'])) {
            return ['results' => [], 'error' => $response['error']];
        }

        $data = $response['data']['data'] ?? [];
        $newAddress = $data['newAddress'] ?? null;
        if (!$newAddress) {
            return ['results' => [], 'error' => $this->diachiError('NO_RESULT', 'Diachi.io không trả về địa chỉ mới tương ứng.')];
        }

        $newProvince = $this->findNewProvinceFromDiachi($newAddress);
        $newWard = $newProvince ? $this->findNewWardFromDiachi($newAddress, $newProvince->id) : null;
        $results = [];

        foreach ($partnerCodes as $partnerCode) {
            $codes = $this->legacyCodesForPartner($legacy, $partnerCode);
            if (!$codes['province_code']) {
                continue;
            }

            $results[] = [
                'partner_code' => $partnerCode,
                'new_address' => [
                    'province_id' => $newProvince->id ?? null,
                    'province_name' => $newProvince->name ?? ($newAddress['province'] ?? null),
                    'ward_id' => $newWard->id ?? null,
                    'ward_name' => $newWard->name ?? ($newAddress['ward'] ?? null),
                ],
                'legacy_address' => $this->formatLegacyAddress($legacy),
                'partner_codes' => [
                    'province_code' => $codes['province_code'],
                    'district_code' => $codes['district_code'],
                    'ward_code' => $codes['ward_code'],
                ],
                'mapping_status' => 'suggested',
                'note' => ($data['notSure'] ?? false)
                    ? 'Gợi ý từ diachi.io, kết quả chưa chắc chắn. Cần kiểm tra rồi lưu mapping.'
                    : 'Gợi ý từ diachi.io. Cần kiểm tra rồi lưu mapping.',
                'source' => 'diachi.io',
                'description' => $data['note'] ?? null,
                'not_sure' => (bool)($data['notSure'] ?? false),
            ];
        }

        return ['results' => $results, 'error' => null];
    }

    private function convertNewToOldFromDiachiApi(NewWard $newWard, array $partnerCodes)
    {
        $newProvince = $newWard->newProvince;
        $payload = [
            'direction' => 'new-to-old',
            'provinceCode' => (string)($newProvince->official_code ?? ''),
            'wardCode' => (string)($newWard->official_code ?? ''),
            'detailAddress' => '',
        ];

        $response = $this->requestDiachiConvertAddress($payload);
        if (!empty($response['error'])) {
            return ['results' => [], 'error' => $response['error']];
        }

        $data = $response['data']['data'] ?? [];
        $oldAddress = $data['oldAddress'] ?? null;
        if (!$oldAddress) {
            return ['results' => [], 'error' => $this->diachiError('NO_RESULT', 'Diachi.io không trả về địa chỉ cũ tương ứng.')];
        }

        $legacy = $this->legacyAddressByDiachiOldAddress($oldAddress);
        if (!$legacy) {
            return [
                'results' => $this->formatDiachiRawNewToOldResults($newWard, $oldAddress, $data, $partnerCodes),
                'error' => null,
            ];
        }

        $results = [];
        foreach ($partnerCodes as $partnerCode) {
            $codes = $this->legacyCodesForPartner($legacy, $partnerCode);
            if (!$codes['province_code']) {
                continue;
            }

            $results[] = [
                'partner_code' => $partnerCode,
                'new_address' => [
                    'province_id' => $newWard->new_province_id,
                    'province_name' => $newProvince->name ?? ($data['newAddress']['province'] ?? null),
                    'ward_id' => $newWard->id,
                    'ward_name' => $newWard->name,
                ],
                'legacy_address' => $this->formatLegacyAddress($legacy),
                'partner_codes' => [
                    'province_code' => $codes['province_code'],
                    'district_code' => $codes['district_code'],
                    'ward_code' => $codes['ward_code'],
                ],
                'mapping_status' => 'suggested',
                'note' => ($data['notSure'] ?? false)
                    ? 'Gợi ý từ diachi.io, kết quả chưa chắc chắn. Cần kiểm tra rồi lưu mapping.'
                    : 'Gợi ý từ diachi.io. Cần kiểm tra rồi lưu mapping.',
                'source' => 'diachi.io',
                'description' => $data['note'] ?? null,
                'not_sure' => (bool)($data['notSure'] ?? false),
            ];
        }

        if (empty($results)) {
            return ['results' => [], 'error' => $this->diachiError('PARTNER_CODE_NOT_FOUND', 'Địa chỉ cũ đã tìm thấy nhưng thiếu mã đối tác VTP/EMS trong dữ liệu local.')];
        }

        return ['results' => $results, 'error' => null];
    }

    private function formatDiachiRawNewToOldResults(NewWard $newWard, array $oldAddress, array $data, array $partnerCodes)
    {
        $newProvince = $newWard->newProvince;
        $results = [];

        foreach ($partnerCodes as $partnerCode) {
            $results[] = [
                'partner_code' => $partnerCode,
                'new_address' => [
                    'province_id' => $newWard->new_province_id,
                    'province_name' => $newProvince->name ?? ($data['newAddress']['province'] ?? null),
                    'ward_id' => $newWard->id,
                    'ward_name' => $newWard->name,
                ],
                'legacy_address' => [
                    'city_id' => null,
                    'district_id' => null,
                    'ward_id' => null,
                    'city_name' => $oldAddress['province'] ?? null,
                    'district_name' => $oldAddress['district'] ?? null,
                    'ward_name' => $oldAddress['ward'] ?? null,
                    'text' => $oldAddress['fullAddress'] ?? trim(($oldAddress['ward'] ?? '') . ', ' . ($oldAddress['district'] ?? '') . ', ' . ($oldAddress['province'] ?? ''), ' ,'),
                    'vtp' => [
                        'province_code' => null,
                        'district_code' => null,
                        'ward_code' => null,
                    ],
                    'ems' => [
                        'province_code' => null,
                        'district_code' => null,
                        'ward_code' => null,
                    ],
                    'diachi' => [
                        'province_code' => $oldAddress['provinceCode'] ?? null,
                        'district_code' => $oldAddress['districtCode'] ?? null,
                        'ward_code' => $oldAddress['wardCode'] ?? null,
                    ],
                ],
                'partner_codes' => [
                    'province_code' => null,
                    'district_code' => null,
                    'ward_code' => null,
                ],
                'mapping_status' => 'local_code_missing',
                'note' => 'Diachi.io có trả địa chỉ cũ nhưng BillHT chưa tìm thấy mã VTP/EMS local tương ứng.',
                'source' => 'diachi.io',
                'description' => $data['note'] ?? null,
                'not_sure' => (bool)($data['notSure'] ?? false),
            ];
        }

        return $results;
    }

    private function requestDiachiConvertAddress(array $payload)
    {
        try {
            $response = $this->diachiClient()->post('api/convert-address', [
                'json' => $payload,
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            $body = $e->hasResponse() ? (string)$e->getResponse()->getBody() : null;
            return [
                'data' => null,
                'error' => $this->diachiError('HTTP_ERROR', $this->formatDiachiHttpError($statusCode, $body, $e->getMessage()), $statusCode, $body),
            ];
        } catch (\Exception $e) {
            return [
                'data' => null,
                'error' => $this->diachiError('CONNECTION_ERROR', 'Không kết nối được diachi.io: ' . $e->getMessage()),
            ];
        }

        $body = (string)$response->getBody();
        $data = json_decode($body, true);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 429 || (!empty($data['rateLimited']))) {
            return [
                'data' => null,
                'error' => $this->diachiError('RATE_LIMIT', $data['error'] ?? 'Diachi.io đang giới hạn số lần tra cứu. Vui lòng thử lại sau.', $statusCode, $body),
            ];
        }

        if ($statusCode >= 400) {
            return [
                'data' => null,
                'error' => $this->diachiError('HTTP_ERROR', $this->formatDiachiHttpError($statusCode, $body, 'Diachi.io trả lỗi HTTP.'), $statusCode, $body),
            ];
        }

        if (empty($data['success'])) {
            return [
                'data' => null,
                'error' => $this->diachiError('API_ERROR', $data['error'] ?? 'Diachi.io không chuyển đổi được địa chỉ này.', $statusCode, $body),
            ];
        }

        return ['data' => $data, 'error' => null];
    }

    private function findDiachiOldAddress($legacy)
    {
        $province = $this->findDiachiProvinceByName($legacy->city_name ?? '');
        if (!$province) {
            return null;
        }

        $district = $this->findDiachiDistrictByName($province['code'] ?? null, $legacy->district_name ?? '');
        if (!$district) {
            return null;
        }

        $ward = $this->findDiachiWardByName($province['code'] ?? null, $district['code'] ?? null, $legacy->ward_name ?? '', 'old');
        if (!$ward) {
            return null;
        }

        return [
            'province_code' => $province['code'] ?? null,
            'district_code' => $district['code'] ?? null,
            'ward_code' => $ward['code'] ?? null,
        ];
    }

    private function findDiachiProvinceByName($name)
    {
        $response = $this->requestDiachiCatalog('api/provinces');
        if (empty($response['data']['data'])) {
            return null;
        }

        $target = $this->normalizeAddressName($name);
        foreach ($response['data']['data'] as $province) {
            if ($this->normalizeAddressName($province['name'] ?? '') === $target) {
                return $province;
            }
        }

        return null;
    }

    private function findDiachiDistrictByName($provinceCode, $name)
    {
        if (!$provinceCode) {
            return null;
        }

        $response = $this->requestDiachiCatalog('api/districts', ['provinceCode' => $provinceCode]);
        if (empty($response['data']['data'])) {
            return null;
        }

        $target = $this->normalizeAddressName($name);
        foreach ($response['data']['data'] as $district) {
            if ($this->normalizeAddressName($district['name'] ?? '') === $target) {
                return $district;
            }
        }

        return null;
    }

    private function findDiachiWardByName($provinceCode, $districtCode, $name, $status)
    {
        if (!$provinceCode || !$districtCode) {
            return null;
        }

        $response = $this->requestDiachiCatalog('api/wards', [
            'provinceCode' => $provinceCode,
            'districtCode' => $districtCode,
            'status' => $status,
        ]);
        if (empty($response['data']['data'])) {
            return null;
        }

        $target = $this->normalizeAddressName($name);
        foreach ($response['data']['data'] as $ward) {
            if ($this->normalizeAddressName($ward['name'] ?? '') === $target) {
                return $ward;
            }
        }

        return null;
    }

    private function requestDiachiCatalog($path, array $query = [])
    {
        try {
            $response = $this->diachiClient()->get($path, [
                'query' => $query,
            ]);
        } catch (\Exception $e) {
            return [
                'data' => null,
                'error' => $this->diachiError('CONNECTION_ERROR', 'Không kết nối được diachi.io: ' . $e->getMessage()),
            ];
        }

        $body = (string)$response->getBody();
        $data = json_decode($body, true);

        if ($response->getStatusCode() >= 400 || empty($data['success'])) {
            return [
                'data' => null,
                'error' => $this->diachiError('API_ERROR', $data['error'] ?? 'Không tải được danh mục diachi.io.', $response->getStatusCode(), $body),
            ];
        }

        return ['data' => $data, 'error' => null];
    }

    private function diachiClient()
    {
        return new Client([
            'base_uri' => 'https://diachi.io/',
            'timeout' => 30,
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Origin' => 'https://diachi.io',
                'Referer' => 'https://diachi.io/',
                'User-Agent' => 'Mozilla/5.0 BillHT Address Mapping',
            ],
        ]);
    }

    private function diachiError($code, $message, $httpStatus = null, $body = null)
    {
        return [
            'code' => $code,
            'message' => $message,
            'http_status' => $httpStatus,
            'body_preview' => $body ? mb_substr(strip_tags((string)$body), 0, 300, 'UTF-8') : null,
        ];
    }

    private function formatDiachiHttpError($statusCode, $body, $fallback)
    {
        if ((int)$statusCode === 403) {
            return 'Diachi.io từ chối truy cập API (403). Có thể cần API key chính thức hoặc bị chặn theo tần suất/IP.';
        }

        if ((int)$statusCode === 404) {
            $data = json_decode((string)$body, true);
            if (!empty($data['error'])) {
                return 'Diachi.io không tìm thấy địa chỉ tương ứng: ' . $data['error'];
            }

            return 'Không tìm thấy endpoint diachi.io (404). Có thể API frontend đã thay đổi.';
        }

        if ((int)$statusCode === 429) {
            return 'Diachi.io đang giới hạn tần suất truy cập (429). Vui lòng thử lại sau.';
        }

        return $fallback;
    }

    private function findNewProvinceFromDiachi(array $address)
    {
        if (!empty($address['provinceCode'])) {
            $province = NewProvince::where('official_code', $address['provinceCode'])->first();
            if ($province) {
                return $province;
            }
        }

        return app(\App\Services\Address2025Service::class)->findProvince($address['province'] ?? '');
    }

    private function findNewWardFromDiachi(array $address, $newProvinceId)
    {
        if (!empty($address['wardCode'])) {
            $ward = NewWard::where('new_province_id', $newProvinceId)
                ->where('official_code', $address['wardCode'])
                ->first();
            if ($ward) {
                return $ward;
            }
        }

        return app(\App\Services\Address2025Service::class)->findWard($address['ward'] ?? '', $newProvinceId);
    }

    private function legacyAddressByDiachiOldAddress(array $oldAddress)
    {
        $external = [
            'province_code' => $oldAddress['provinceCode'] ?? null,
            'district_code' => $oldAddress['districtCode'] ?? null,
            'ward_code' => $oldAddress['wardCode'] ?? null,
            'province_name' => $oldAddress['province'] ?? null,
            'district_name' => $oldAddress['district'] ?? null,
            'ward_name' => $oldAddress['ward'] ?? null,
        ];

        return $this->legacyAddressByExternalOldAddress($external);
    }

    private function convertOldToNewFromExternalApi($legacy, array $partnerCodes)
    {
        $oldAddress = $this->findExternalOldAddress($legacy);
        if (!$oldAddress) {
            return [];
        }

        $client = $this->externalAddressClient();
        try {
            $response = $client->post('convert-address', [
                'json' => [
                    'province_code' => $oldAddress['province_code'],
                    'district_code' => $oldAddress['district_code'],
                    'ward_code' => $oldAddress['ward_code'],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::warning('Address conversion API failed', [
                'legacy' => $this->formatLegacyAddress($legacy),
                'message' => $e->getMessage(),
            ]);
            return [];
        }

        $payload = json_decode($response->getBody()->getContents(), true);
        $newAddresses = $payload['data']['new_addresses'] ?? [];
        $results = [];

        foreach ($newAddresses as $newAddress) {
            $newProvince = $this->findNewProvinceFromExternal($newAddress);
            $newWard = $newProvince ? $this->findNewWardFromExternal($newAddress, $newProvince->id) : null;

            foreach ($partnerCodes as $partnerCode) {
                $codes = $this->legacyCodesForPartner($legacy, $partnerCode);
                if (!$codes['province_code']) {
                    continue;
                }

                $results[] = [
                    'partner_code' => $partnerCode,
                    'new_address' => [
                        'province_id' => $newProvince->id ?? null,
                        'province_name' => $newProvince->name ?? ($newAddress['province_name'] ?? null),
                        'ward_id' => $newWard->id ?? null,
                        'ward_name' => $newWard->name ?? ($newAddress['ward_name'] ?? null),
                    ],
                    'legacy_address' => $this->formatLegacyAddress($legacy),
                    'partner_codes' => [
                        'province_code' => $codes['province_code'],
                        'district_code' => $codes['district_code'],
                        'ward_code' => $codes['ward_code'],
                    ],
                    'mapping_status' => 'suggested',
                    'note' => 'Gợi ý từ API chuyển đổi địa chỉ. Cần kiểm tra rồi lưu mapping.',
                    'source' => 'tracuudiachi',
                    'description' => $newAddress['ward_description'] ?? null,
                ];
            }
        }

        return $results;
    }

    private function convertNewToOldFromExternalApi(NewWard $newWard, array $partnerCodes)
    {
        $newProvince = $newWard->newProvince;
        $provinceCode = $newProvince->official_code ?? null;
        $wardCode = $newWard->official_code ?? null;

        if (!$provinceCode || !$wardCode) {
            return [];
        }

        $oldAddresses = $this->fetchExternalOldAddresses($provinceCode, $wardCode);
        if (empty($oldAddresses)) {
            $oldAddresses = $this->scanExternalOldAddressesForNewWard($newWard);
        }
        $results = [];

        foreach ($oldAddresses as $oldAddress) {
            $legacy = $this->legacyAddressByExternalOldAddress($oldAddress);
            if (!$legacy) {
                continue;
            }

            foreach ($partnerCodes as $partnerCode) {
                $codes = $this->legacyCodesForPartner($legacy, $partnerCode);
                if (!$codes['province_code']) {
                    continue;
                }

                $results[] = [
                    'partner_code' => $partnerCode,
                    'new_address' => [
                        'province_id' => $newWard->new_province_id,
                        'province_name' => $newProvince->name ?? null,
                        'ward_id' => $newWard->id,
                        'ward_name' => $newWard->name,
                    ],
                    'legacy_address' => $this->formatLegacyAddress($legacy),
                    'partner_codes' => [
                        'province_code' => $codes['province_code'],
                        'district_code' => $codes['district_code'],
                        'ward_code' => $codes['ward_code'],
                    ],
                    'mapping_status' => 'suggested',
                    'note' => 'Gợi ý từ API chuyển đổi địa chỉ. Cần kiểm tra rồi lưu mapping.',
                    'source' => 'tracuudiachi',
                    'description' => $oldAddress['ward_description'] ?? null,
                ];
            }
        }

        return $results;
    }

    private function fetchExternalOldAddresses($provinceCode, $wardCode)
    {
        $client = $this->externalAddressClient();
        $oldAddresses = [];

        try {
            $response = $client->get('convert-address', [
                'query' => [
                    'province_code' => $provinceCode,
                    'ward_code' => $wardCode,
                ],
            ]);
            $payload = json_decode($response->getBody()->getContents(), true);
            $oldAddresses = $payload['data']['old_addresses'] ?? $payload['data']['old_address'] ?? [];
        } catch (\Exception $e) {
            \Log::warning('Address new-to-old GET conversion API failed', [
                'province_code' => $provinceCode,
                'ward_code' => $wardCode,
                'message' => $e->getMessage(),
            ]);
        }

        if (empty($oldAddresses)) {
            try {
                $response = $client->post('convert-address', [
                    'json' => [
                        'province_code' => $provinceCode,
                        'ward_code' => $wardCode,
                    ],
                ]);
                $payload = json_decode($response->getBody()->getContents(), true);
                $oldAddresses = $payload['data']['old_addresses'] ?? $payload['data']['old_address'] ?? [];
            } catch (\Exception $e) {
                \Log::warning('Address new-to-old POST conversion API failed', [
                    'province_code' => $provinceCode,
                    'ward_code' => $wardCode,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (isset($oldAddresses['province_name'])) {
            $oldAddresses = [$oldAddresses];
        }

        return is_array($oldAddresses) ? $oldAddresses : [];
    }

    private function scanExternalOldAddressesForNewWard(NewWard $newWard)
    {
        $newProvince = $newWard->newProvince;
        $targetProvinceCode = (string)($newProvince->official_code ?? '');
        $targetWardCode = (string)($newWard->official_code ?? '');

        if ($targetProvinceCode === '' || $targetWardCode === '') {
            return [];
        }

        $client = $this->externalAddressClient();
        $matches = [];
        $checkedCandidates = 0;
        $targetWardName = $this->normalizeAddressName($newWard->name);
        $page = 1;

        do {
            try {
                $response = $client->get('provinces', [
                    'query' => [
                        'page' => $page,
                        'icpp' => 1,
                        'expand' => 'districts,wards',
                    ],
                ]);
            } catch (\Exception $e) {
                \Log::warning('Address reverse scan provinces API failed', [
                    'new_ward_id' => $newWard->id,
                    'message' => $e->getMessage(),
                ]);
                break;
            }

            $payload = json_decode($response->getBody()->getContents(), true);
            $provinces = $payload['data'] ?? [];
            $totalPages = (int)($payload['meta']['totalPages'] ?? 1);

            foreach ($provinces as $province) {
                foreach (($province['districts'] ?? []) as $district) {
                    foreach (($district['wards'] ?? []) as $ward) {
                        $oldWardName = $this->normalizeAddressName($ward['name'] ?? '');
                        if (
                            $oldWardName === '' ||
                            (
                                $oldWardName !== $targetWardName &&
                                strpos($oldWardName, $targetWardName) === false &&
                                strpos($targetWardName, $oldWardName) === false
                            )
                        ) {
                            continue;
                        }

                        $checkedCandidates++;
                        if ($checkedCandidates > 100) {
                            return $matches;
                        }

                        $newAddresses = $this->convertExternalOldAddressToNew(
                            $province['code'] ?? null,
                            $district['code'] ?? null,
                            $ward['code'] ?? null
                        );

                        foreach ($newAddresses as $newAddress) {
                            if (
                                (string)($newAddress['province_code'] ?? '') === $targetProvinceCode &&
                                (string)($newAddress['ward_code'] ?? '') === $targetWardCode
                            ) {
                                $matches[] = [
                                    'province_code' => $province['code'] ?? null,
                                    'district_code' => $district['code'] ?? null,
                                    'ward_code' => $ward['code'] ?? null,
                                    'province_name' => $province['name'] ?? null,
                                    'district_name' => $district['name'] ?? null,
                                    'ward_name' => $ward['name'] ?? null,
                                    'ward_description' => $newAddress['ward_description'] ?? null,
                                ];

                                if (count($matches) >= 20) {
                                    return $matches;
                                }
                            }
                        }
                    }
                }
            }

            $page++;
        } while ($page <= $totalPages);

        return $matches;
    }

    private function convertExternalOldAddressToNew($provinceCode, $districtCode, $wardCode)
    {
        if (!$provinceCode || !$districtCode || !$wardCode) {
            return [];
        }

        try {
            $response = $this->externalAddressClient()->post('convert-address', [
                'json' => [
                    'province_code' => $provinceCode,
                    'district_code' => $districtCode,
                    'ward_code' => $wardCode,
                ],
            ]);
        } catch (\Exception $e) {
            return [];
        }

        $payload = json_decode($response->getBody()->getContents(), true);

        return $payload['data']['new_addresses'] ?? [];
    }

    private function legacyAddressByExternalOldAddress(array $oldAddress)
    {
        return DB::table('wards')
            ->join('districts', 'districts.id', '=', 'wards.district_id')
            ->join('citys', 'citys.id', '=', 'districts.city_id')
            ->where(function ($query) use ($oldAddress) {
                $query->where('citys.city_name', 'LIKE', '%' . $this->stripAddressPrefix($oldAddress['province_name'] ?? '') . '%')
                    ->orWhere('citys.city_code', $oldAddress['province_code'] ?? null);
            })
            ->where(function ($query) use ($oldAddress) {
                $query->where('districts.district_name', 'LIKE', '%' . $this->stripAddressPrefix($oldAddress['district_name'] ?? '') . '%')
                    ->orWhere('districts.district_code', $oldAddress['district_code'] ?? null);
            })
            ->where(function ($query) use ($oldAddress) {
                $query->where('wards.ward_name', 'LIKE', '%' . $this->stripAddressPrefix($oldAddress['ward_name'] ?? '') . '%')
                    ->orWhere('wards.ward_code', $oldAddress['ward_code'] ?? null);
            })
            ->select([
                'citys.id as city_id',
                'districts.id as district_id',
                'wards.id as ward_id',
                'citys.city_name',
                'districts.district_name',
                'wards.ward_name',
                'citys.city_code as vtp_province_code',
                'districts.district_code as vtp_district_code',
                'wards.ward_code as vtp_ward_code',
                'citys.ems_code as ems_province_code',
                'districts.ems_code as ems_district_code',
                'wards.ems_code as ems_ward_code',
            ])
            ->first();
    }

    private function findExternalOldAddress($legacy)
    {
        $client = $this->externalAddressClient();
        $page = 1;

        do {
            try {
                $response = $client->get('provinces', [
                    'query' => [
                        'page' => $page,
                        'icpp' => 1,
                        'expand' => 'districts,wards',
                    ],
                ]);
            } catch (\Exception $e) {
                \Log::warning('Address provinces API failed', [
                    'message' => $e->getMessage(),
                ]);
                return null;
            }

            $payload = json_decode($response->getBody()->getContents(), true);
            $provinces = $payload['data'] ?? [];
            $totalPages = (int)($payload['meta']['totalPages'] ?? 1);

            foreach ($provinces as $province) {
                if ($this->normalizeAddressName($province['name'] ?? '') !== $this->normalizeAddressName($legacy->city_name ?? '')) {
                    continue;
                }

                foreach (($province['districts'] ?? []) as $district) {
                    if ($this->normalizeAddressName($district['name'] ?? '') !== $this->normalizeAddressName($legacy->district_name ?? '')) {
                        continue;
                    }

                    foreach (($district['wards'] ?? []) as $ward) {
                        if ($this->normalizeAddressName($ward['name'] ?? '') === $this->normalizeAddressName($legacy->ward_name ?? '')) {
                            return [
                                'province_code' => $province['code'] ?? null,
                                'district_code' => $district['code'] ?? null,
                                'ward_code' => $ward['code'] ?? null,
                            ];
                        }
                    }
                }
            }

            $page++;
        } while ($page <= $totalPages);

        return null;
    }

    private function externalAddressClient()
    {
        return new Client([
            'base_uri' => 'https://api.tracuudiachi.io.vn/api/v1/',
            'timeout' => 30,
            'verify' => false,
            'http_errors' => false,
        ]);
    }

    private function findNewProvinceFromExternal(array $newAddress)
    {
        if (!empty($newAddress['province_code'])) {
            $province = NewProvince::where('official_code', $newAddress['province_code'])->first();
            if ($province) {
                return $province;
            }
        }

        return app(\App\Services\Address2025Service::class)->findProvince($newAddress['province_name'] ?? '');
    }

    private function findNewWardFromExternal(array $newAddress, $newProvinceId)
    {
        if (!empty($newAddress['ward_code'])) {
            $ward = NewWard::where('new_province_id', $newProvinceId)
                ->where('official_code', $newAddress['ward_code'])
                ->first();
            if ($ward) {
                return $ward;
            }
        }

        return app(\App\Services\Address2025Service::class)->findWard($newAddress['ward_name'] ?? '', $newProvinceId);
    }

    private function normalizeAddressName($value)
    {
        $value = (new EmsService())->stripVN((string)$value);
        $value = strtoupper($value);
        $prefixes = [
            'THANH PHO ', 'TINH ', 'QUAN ', 'HUYEN DAO ', 'HUYEN ',
            'THI XA ', 'PHUONG ', 'XA ', 'THI TRAN ', 'DAO ',
            'TP ', 'TX ', 'TT ', 'P ', 'Q ', 'H ',
            'TP.', 'TX.', 'TT.', 'P.', 'Q.', 'H.',
        ];

        foreach ($prefixes as $prefix) {
            if (strpos($value, $prefix) === 0) {
                $value = substr($value, strlen($prefix));
            }
        }

        $value = str_replace(['-', '.', ',', '/', '(', ')'], '', $value);
        $value = preg_replace('/\s+/', '', trim($value));

        return $value;
    }

    private function stripAddressPrefix($value)
    {
        $value = trim((string)$value);
        $value = preg_replace('/^(tỉnh|tp|tp\.|thành phố|huyện|quận|tx|tx\.|thị xã|xã|phường|tt|tt\.|thị trấn)\s+/iu', '', $value);

        return trim($value);
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
                ->where(function ($q) use ($cityKeyword) {
                    $this->applyLegacyAddressLike($q, 'citys.city_name', $cityKeyword);
                });
        } elseif (count($parts) === 2) {
            $firstKeyword = $this->stripLegacyAddressPrefix($parts[0]);
            $secondKeyword = $this->stripLegacyAddressPrefix($parts[1]);

            $query->where(function ($q) use ($firstKeyword, $secondKeyword) {
                $q->where(function ($subQuery) use ($firstKeyword, $secondKeyword) {
                    $subQuery->where('wards.ward_name', 'LIKE', '%' . $firstKeyword . '%')
                        ->where('districts.district_name', 'LIKE', '%' . $secondKeyword . '%');
                })->orWhere(function ($subQuery) use ($firstKeyword, $secondKeyword) {
                    $subQuery->where('districts.district_name', 'LIKE', '%' . $firstKeyword . '%')
                        ->where(function ($q) use ($secondKeyword) {
                            $this->applyLegacyAddressLike($q, 'citys.city_name', $secondKeyword);
                        });
                })->orWhere(function ($subQuery) use ($firstKeyword, $secondKeyword) {
                    $subQuery->where('wards.ward_name', 'LIKE', '%' . $firstKeyword . '%')
                        ->where(function ($q) use ($secondKeyword) {
                            $this->applyLegacyAddressLike($q, 'citys.city_name', $secondKeyword);
                        });
                });
            });
        } else {
            $singleKeyword = $this->stripLegacyAddressPrefix($keyword);
            $query->where(function ($q) use ($singleKeyword) {
                $this->applyLegacyAddressLike($q, 'citys.city_name', $singleKeyword);
                $q->orWhere('districts.district_name', 'LIKE', '%' . $singleKeyword . '%')
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

    private function applyLegacyAddressLike($query, $column, $keyword)
    {
        $keywords = array_unique(array_filter(array_merge([$keyword], $this->legacyAddressAliases($keyword))));
        foreach ($keywords as $index => $item) {
            if ($index === 0) {
                $query->where($column, 'LIKE', '%' . $item . '%');
            } else {
                $query->orWhere($column, 'LIKE', '%' . $item . '%');
            }
        }
    }

    private function legacyAddressAliases($keyword)
    {
        $normalized = mb_strtolower(trim((string)$keyword), 'UTF-8');
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        $aliases = [];
        if (in_array($normalized, ['ho chi minh', 'hồ chí minh', 'tp ho chi minh', 'tp hồ chí minh', 'thanh pho ho chi minh', 'thành phố hồ chí minh'], true)) {
            $aliases = ['HCM', 'TP HCM', 'TP.HCM', 'Hồ Chí Minh', 'Ho Chi Minh'];
        }

        return $aliases;
    }
}
