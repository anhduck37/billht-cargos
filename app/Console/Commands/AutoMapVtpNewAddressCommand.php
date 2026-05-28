<?php

namespace App\Console\Commands;

use App\City;
use App\District;
use App\NewAddressPartnerMapping;
use App\NewProvince;
use App\NewWard;
use App\Services\Address2025Service;
use App\Services\EmsService;
use App\Ward;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AutoMapVtpNewAddressCommand extends Command
{
    protected $signature = 'address:auto-map-vtp
        {--limit=0 : Gioi han so xa/phuong cu can quet, 0 la tat ca}
        {--province-code= : Chi quet mot tinh/thanh cu theo ma API tra cuu dia chi, vi du Ha Noi = 1}
        {--dry-run : Chi kiem tra, khong ghi DB}
        {--only-missing=1 : Chi bo sung mapping VTP dang thieu}
        {--write-ambiguous=0 : Cho phep ghi ca mapping co nhieu ung vien ngang diem}';

    protected $description = 'Auto create VTP mappings for new 2025 wards by converting old wards to new wards';

    private $addressService;
    private $oldVtpIndex = [];
    private $newCandidates = [];

    public function handle(Address2025Service $addressService)
    {
        $this->addressService = $addressService;
        $this->oldVtpIndex = $this->buildOldVtpIndex();

        if (empty($this->oldVtpIndex)) {
            $this->error('Chua co danh muc VTP cu trong bang citys/districts/wards. Hay chay map_city, map_district, map_ward truoc.');
            return 1;
        }

        $client = new Client([
            'base_uri' => 'https://api.tracuudiachi.io.vn/api/v1/',
            'timeout' => 30,
            'verify' => false,
            'http_errors' => false,
        ]);

        $limit = (int)$this->option('limit');
        $dryRun = (bool)$this->option('dry-run');
        $onlyMissing = (string)$this->option('only-missing') !== '0';
        $writeAmbiguous = (string)$this->option('write-ambiguous') === '1';

        $provinceCode = $this->option('province-code');
        $checked = $this->collectCandidates($client, $limit, $provinceCode);
        $result = $this->writeMappings($onlyMissing, $dryRun, $writeAmbiguous);

        if (!empty($result['review'])) {
            $path = storage_path('logs/vtp_new_address_mapping_review.json');
            File::put($path, json_encode($result['review'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->warn('Can kiem tra thu cong ' . count($result['review']) . ' xa/phuong. File: ' . $path);
        }

        $this->info(
            'Hoan tat. Checked old wards: ' . $checked .
            '. Candidate new wards: ' . count($this->newCandidates) .
            '. Mapped: ' . $result['mapped'] .
            '. Skipped: ' . $result['skipped'] .
            '. Need review: ' . count($result['review']) . '.'
        );

        if ($dryRun) {
            $this->warn('Dry-run: chua ghi vao DB.');
        }

        return 0;
    }

    private function collectCandidates(Client $client, $limit, $provinceCode = null)
    {
        $checked = 0;
        $page = 1;

        $this->info('Dang lay danh sach tinh/huyen/xa cu tu API tra cuu dia chi...');

        do {
            $response = $client->get('provinces', [
                'query' => [
                    'page' => $page,
                    'icpp' => 1,
                    'expand' => 'districts,wards',
                ],
            ]);

            $payload = json_decode($response->getBody()->getContents(), true);
            $provinces = $payload['data'] ?? [];
            $totalPages = (int)($payload['meta']['totalPages'] ?? 1);

            foreach ($provinces as $province) {
                if ($provinceCode !== null && $provinceCode !== '' && (string)($province['code'] ?? '') !== (string)$provinceCode) {
                    continue;
                }

                foreach (($province['districts'] ?? []) as $district) {
                    foreach (($district['wards'] ?? []) as $ward) {
                        if ($limit > 0 && $checked >= $limit) {
                            return $checked;
                        }

                        $checked++;
                        $this->collectOldWardCandidates($client, $province, $district, $ward);
                    }
                }
            }

            $page++;
        } while ($page <= $totalPages);

        return $checked;
    }

    private function collectOldWardCandidates(Client $client, array $province, array $district, array $ward)
    {
        $oldVtp = $this->findOldVtpAddress($province['name'] ?? '', $district['name'] ?? '', $ward['name'] ?? '');
        if (!$oldVtp) {
            return;
        }

        $response = $client->post('convert-address', [
            'json' => [
                'province_code' => $province['code'] ?? null,
                'district_code' => $district['code'] ?? null,
                'ward_code' => $ward['code'] ?? null,
            ],
        ]);

        $payload = json_decode($response->getBody()->getContents(), true);
        $newAddresses = $payload['data']['new_addresses'] ?? [];

        foreach ($newAddresses as $newAddress) {
            $newProvince = $this->findNewProvince($newAddress);
            if (!$newProvince) {
                continue;
            }

            $newWard = $this->findNewWard($newAddress, $newProvince->id);
            if (!$newWard) {
                continue;
            }

            $score = $this->scoreCandidate($province, $district, $ward, $newAddress);
            $this->newCandidates[$newWard->id][] = [
                'score' => $score,
                'new_province_id' => $newProvince->id,
                'new_ward_id' => $newWard->id,
                'new_province_name' => $newProvince->name,
                'new_ward_name' => $newWard->name,
                'old_province_name' => $province['name'] ?? null,
                'old_district_name' => $district['name'] ?? null,
                'old_ward_name' => $ward['name'] ?? null,
                'old_province_code' => $province['code'] ?? null,
                'old_district_code' => $district['code'] ?? null,
                'old_ward_code' => $ward['code'] ?? null,
                'partner_province_code' => $oldVtp['province_code'],
                'partner_district_code' => $oldVtp['district_code'],
                'partner_ward_code' => $oldVtp['ward_code'],
                'description' => $newAddress['ward_description'] ?? null,
            ];
        }
    }

    private function writeMappings($onlyMissing, $dryRun, $writeAmbiguous)
    {
        $mapped = 0;
        $skipped = 0;
        $review = [];

        foreach ($this->newCandidates as $newWardId => $candidates) {
            $existing = NewAddressPartnerMapping::where('new_ward_id', $newWardId)
                ->where('partner_code', 'VTP')
                ->first();

            if ($existing && $onlyMissing) {
                $skipped++;
                continue;
            }

            usort($candidates, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            $best = $candidates[0];
            $ambiguous = count($candidates) > 1 && $candidates[0]['score'] === $candidates[1]['score'];

            if ($ambiguous && !$writeAmbiguous) {
                $review[] = [
                    'reason' => 'ambiguous_candidates',
                    'new' => [
                        'ward_id' => $best['new_ward_id'],
                        'ward_name' => $best['new_ward_name'],
                        'province_name' => $best['new_province_name'],
                    ],
                    'candidates' => array_slice($candidates, 0, 8),
                ];
                continue;
            }

            if (!$dryRun) {
                NewAddressPartnerMapping::updateOrCreate(
                    [
                        'new_ward_id' => $best['new_ward_id'],
                        'partner_code' => 'VTP',
                    ],
                    [
                        'new_province_id' => $best['new_province_id'],
                        'partner_province_code' => $best['partner_province_code'],
                        'partner_district_code' => $best['partner_district_code'],
                        'partner_ward_code' => $best['partner_ward_code'],
                        'mapping_status' => 'mapped',
                        'note' => $this->buildNote($best, $ambiguous),
                    ]
                );
            }

            $mapped++;
        }

        return compact('mapped', 'skipped', 'review');
    }

    private function scoreCandidate(array $province, array $district, array $ward, array $newAddress)
    {
        $score = 0;
        $oldWardCode = (string)($ward['code'] ?? '');
        $newWardCode = (string)($newAddress['ward_code'] ?? '');
        $oldWardName = $this->normalize($ward['name'] ?? '');
        $newWardName = $this->normalize($newAddress['ward_name'] ?? '');
        $description = $this->normalize($newAddress['ward_description'] ?? '');

        if ($oldWardCode !== '' && $oldWardCode === $newWardCode) {
            $score += 100;
        }

        if ($oldWardName !== '' && $oldWardName === $newWardName) {
            $score += 80;
        }

        if ($oldWardName !== '' && strpos($description, 'DOITENTU' . $oldWardName) !== false) {
            $score += 60;
        }

        if ($oldWardName !== '' && strpos($description, $oldWardName) !== false) {
            $score += 20;
        }

        if ($this->normalize($province['name'] ?? '') === $this->normalize($newAddress['province_name'] ?? '')) {
            $score += 10;
        }

        if ($this->normalize($district['name'] ?? '') !== '') {
            $score += 1;
        }

        return $score;
    }

    private function buildNote(array $candidate, $ambiguous)
    {
        $note = 'Auto mapped VTP from tracuudiachi old->new API';
        $note .= '. Old: ' . $candidate['old_ward_name'] . ', ' . $candidate['old_district_name'] . ', ' . $candidate['old_province_name'];
        $note .= '. Score: ' . $candidate['score'];

        if ($ambiguous) {
            $note .= '. Ambiguous allowed by --write-ambiguous=1';
        }

        return $note;
    }

    private function buildOldVtpIndex()
    {
        $index = [];
        $cities = City::all()->keyBy('id');
        $districts = District::all()->keyBy('id');

        Ward::chunkById(1000, function ($wards) use (&$index, $cities, $districts) {
            foreach ($wards as $ward) {
                $district = $districts->get($ward->district_id);
                if (!$district) {
                    continue;
                }

                $city = $cities->get($district->city_id);
                if (!$city) {
                    continue;
                }

                $key = $this->normalize($city->city_name) . '|' . $this->normalize($district->district_name) . '|' . $this->normalize($ward->ward_name);
                $index[$key] = [
                    'province_code' => $city->city_code,
                    'district_code' => $district->district_code,
                    'ward_code' => $ward->ward_code,
                ];
            }
        });

        return $index;
    }

    private function findOldVtpAddress($provinceName, $districtName, $wardName)
    {
        $key = $this->normalize($provinceName) . '|' . $this->normalize($districtName) . '|' . $this->normalize($wardName);
        if (isset($this->oldVtpIndex[$key])) {
            return $this->oldVtpIndex[$key];
        }

        $provinceKey = $this->normalize($provinceName);
        $districtKey = $this->normalize($districtName);
        $wardKey = $this->normalize($wardName);

        foreach ($this->oldVtpIndex as $vtpKey => $vtpValue) {
            list($vtpProvince, $vtpDistrict, $vtpWard) = explode('|', $vtpKey);
            if ($vtpProvince !== $provinceKey || $vtpDistrict !== $districtKey) {
                continue;
            }

            if ($vtpWard === $wardKey || strpos($vtpWard, $wardKey) !== false || strpos($wardKey, $vtpWard) !== false) {
                return $vtpValue;
            }
        }

        return null;
    }

    private function findNewProvince(array $newAddress)
    {
        if (!empty($newAddress['province_code'])) {
            $province = NewProvince::where('official_code', $newAddress['province_code'])->first();
            if ($province) {
                return $province;
            }
        }

        return $this->addressService->findProvince($newAddress['province_name'] ?? '');
    }

    private function findNewWard(array $newAddress, $newProvinceId)
    {
        if (!empty($newAddress['ward_code'])) {
            $ward = NewWard::where('new_province_id', $newProvinceId)
                ->where('official_code', $newAddress['ward_code'])
                ->first();
            if ($ward) {
                return $ward;
            }
        }

        return $this->addressService->findWard($newAddress['ward_name'] ?? '', $newProvinceId);
    }

    private function normalize($value)
    {
        $value = str_replace('Ä', 'D', (string)$value);
        $value = str_replace('Ä‘', 'd', $value);
        $value = (new EmsService())->stripVN($value);
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

        if (is_numeric($value)) {
            $value = (string)((int)$value);
        }

        return $value;
    }
}
