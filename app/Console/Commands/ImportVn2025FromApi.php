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
            'base_uri' => 'https://partner.viettelpost.vn/v2/categories/',
            'timeout'  => 30.0,
            'verify' => false,
        ]);

        try {
            $this->info('Đang lấy danh sách Tỉnh/TP mới...');
            $response = $client->request('GET', 'listProvinceById?provinceId=-1');
            $data = json_decode($response->getBody(), true);
            
            $provinces = $data['data'] ?? [];
            if (empty($provinces)) {
                $this->error('Không lấy được dữ liệu Tỉnh/TP.');
                return;
            }

            $bar = $this->output->createProgressBar(count($provinces));
            $vpProvinceMap = [];
            
            foreach ($provinces as $prov) {
                $provinceName = $prov['PROVINCE_NAME'];
                $provinceCode = $prov['PROVINCE_CODE'];
                $vpProvId = $prov['PROVINCE_ID'];
                
                $normalizedName = $addressService->normalizeName($provinceName);

                $newProvince = NewProvince::updateOrCreate(
                    ['official_code' => $provinceCode],
                    [
                        'name' => $provinceName,
                        'normalized_name' => $normalizedName,
                        'is_active' => 1
                    ]
                );
                
                $vpProvinceMap[$vpProvId] = $newProvince->id;
                $bar->advance();
            }
            $bar->finish();
            
            $this->info("\nĐang lấy danh sách Quận/Huyện để map Tỉnh...");
            $districtResponse = $client->request('GET', 'listDistrict?provinceId=-1');
            $districtData = json_decode($districtResponse->getBody(), true);
            $districts = $districtData['data'] ?? [];
            
            $vpDistrictMap = []; // DISTRICT_ID -> vpProvId
            foreach ($districts as $d) {
                $vpDistrictMap[$d['DISTRICT_ID']] = $d['PROVINCE_ID'];
            }

            $this->info("\nĐang lấy danh sách Phường/Xã mới...");
            $wardResponse = $client->request('GET', 'listWards?districtId=-1');
            $wardData = json_decode($wardResponse->getBody(), true);
            $wards = $wardData['data'] ?? [];
            
            $wardBar = $this->output->createProgressBar(count($wards));
            foreach ($wards as $w) {
                $vpDistId = $w['DISTRICT_ID'];
                $wardName = $w['WARDS_NAME'];
                $wardCode = $w['WARDS_ID'];
                
                if (!isset($vpDistrictMap[$vpDistId])) {
                    $wardBar->advance();
                    continue;
                }
                $vpProvId = $vpDistrictMap[$vpDistId];
                
                if (!isset($vpProvinceMap[$vpProvId])) {
                    $wardBar->advance();
                    continue;
                }
                
                $myProvId = $vpProvinceMap[$vpProvId];
                $normalizedName = $addressService->normalizeName($wardName);

                NewWard::updateOrCreate(
                    [
                        'new_province_id' => $myProvId,
                        'official_code' => $wardCode
                    ],
                    [
                        'name' => $wardName,
                        'normalized_name' => $normalizedName,
                        'is_active' => 1
                    ]
                );
                
                $wardBar->advance();
            }
            $wardBar->finish();

            $this->info("\nHoàn tất import địa danh 2025!");

        } catch (Exception $e) {
            $this->error('Lỗi khi gọi API: ' . $e->getMessage());
        }
    }
}
