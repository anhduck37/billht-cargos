<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\NewProvince;
use App\NewWard;
use App\Services\Address2025Service;
use Exception;

class ImportVn2025FromApi extends Command
{
    protected $signature = 'import:vn2025-from-api';
    protected $description = 'Import danh mục tỉnh/xã mới từ api.tracuudiachi.io.vn';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(Address2025Service $addressService)
    {
        $this->info('Bắt đầu tải dữ liệu địa danh 2025 từ API...');
        
        $client = new Client([
            'base_uri' => 'https://api.tracuudiachi.io.vn/api/v1/',
            'timeout'  => 30.0,
            'verify' => false, // Bypass SSL if needed
        ]);

        try {
            // Lấy danh sách Tỉnh mới
            $this->info('Đang lấy danh sách Tỉnh/TP mới...');
            $response = $client->request('GET', 'new-provinces', ['query' => ['icpp' => 100]]);
            $data = json_decode($response->getBody(), true);
            
            $provinces = $data['data'] ?? [];
            if (empty($provinces)) {
                $this->error('Không lấy được dữ liệu Tỉnh/TP.');
                return;
            }

            $bar = $this->output->createProgressBar(count($provinces));
            
            foreach ($provinces as $prov) {
                $provinceName = $prov['name'];
                $provinceCode = $prov['code'];
                
                $normalizedName = $addressService->normalizeName($provinceName);

                $newProvince = NewProvince::updateOrCreate(
                    ['official_code' => $provinceCode],
                    [
                        'name' => $provinceName,
                        'normalized_name' => $normalizedName,
                        'is_active' => 1
                    ]
                );

                // Lấy danh sách Xã của Tỉnh này
                $this->fetchWardsForProvince($client, $addressService, $newProvince, $provinceCode);
                
                $bar->advance();
            }

            $bar->finish();
            $this->info("\nHoàn tất import địa danh 2025!");

        } catch (Exception $e) {
            $this->error('Lỗi khi gọi API: ' . $e->getMessage());
        }
    }

    private function fetchWardsForProvince(Client $client, Address2025Service $addressService, NewProvince $province, $provinceCode)
    {
        try {
            $page = 1;
            do {
                $response = $client->request('GET', "new-provinces/{$provinceCode}/new-wards", [
                    'query' => ['icpp' => 100, 'page' => $page]
                ]);
                $data = json_decode($response->getBody(), true);
                
                $wards = $data['data'] ?? [];
                
                foreach ($wards as $w) {
                    $wardName = $w['name'];
                    $wardCode = $w['code'];
                    $normalizedName = $addressService->normalizeName($wardName);

                    NewWard::updateOrCreate(
                        [
                            'new_province_id' => $province->id,
                            'official_code' => $wardCode
                        ],
                        [
                            'name' => $wardName,
                            'normalized_name' => $normalizedName,
                            'is_active' => 1
                        ]
                    );
                }

                $totalPages = $data['meta']['totalPages'] ?? 1;
                $page++;
            } while ($page <= $totalPages);

        } catch (Exception $e) {
            $this->error("\nLỗi khi lấy Xã cho tỉnh {$province->name}: " . $e->getMessage());
        }
    }
}
