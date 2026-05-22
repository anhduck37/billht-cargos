<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\NewAddressPartnerMapping;
use App\Services\Address2025Service;
use Illuminate\Support\Facades\Log;

class ImportVn2025PartnerMappings extends Command
{
    protected $signature = 'import:vn2025-partner-mappings {--file=}';
    protected $description = 'Import EMS/VTP mapping cho địa danh 2025 từ file CSV';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(Address2025Service $addressService)
    {
        $file = $this->option('file') ?: base_path('database/seeds/data/vn_2025_partner_mappings.csv');

        if (!file_exists($file)) {
            $this->error("Không tìm thấy file: {$file}");
            return;
        }

        $this->info("Đang đọc file: {$file}");

        $handle = fopen($file, "r");
        $header = fgetcsv($handle, 1000, ",");
        
        $errors = [];
        $row = 2;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($header) !== count($data)) {
                $errors[] = "Dòng {$row}: Số cột không khớp.";
                $row++;
                continue;
            }
            
            $row_data = array_combine($header, $data);
            
            $provinceName = $row_data['province_name'] ?? '';
            $wardName = $row_data['ward_name'] ?? '';
            $partnerCode = strtoupper($row_data['partner_code'] ?? '');
            
            if (!in_array($partnerCode, ['VTP', 'EMS'])) {
                $errors[] = "Dòng {$row}: Partner code không hợp lệ ({$partnerCode}).";
                $row++;
                continue;
            }

            $province = $addressService->findProvince($provinceName);
            if (!$province) {
                $errors[] = "Dòng {$row}: Không tìm thấy tỉnh {$provinceName}.";
                $row++;
                continue;
            }

            $ward = $addressService->findWard($wardName, $province->id);
            if (!$ward) {
                $errors[] = "Dòng {$row}: Không tìm thấy xã {$wardName} thuộc tỉnh {$provinceName}.";
                $row++;
                continue;
            }

            NewAddressPartnerMapping::updateOrCreate(
                [
                    'new_ward_id' => $ward->id,
                    'partner_code' => $partnerCode,
                ],
                [
                    'new_province_id' => $province->id,
                    'partner_province_code' => $row_data['partner_province_code'] ?? null,
                    'partner_district_code' => $row_data['partner_district_code'] ?? null,
                    'partner_ward_code' => $row_data['partner_ward_code'] ?? null,
                    'mapping_status' => $row_data['mapping_status'] ?? 'mapped',
                    'note' => $row_data['note'] ?? null,
                ]
            );

            $row++;
        }
        fclose($handle);

        $this->info("Hoàn tất import mapping.");
        
        if (!empty($errors)) {
            $this->error("Có " . count($errors) . " lỗi xảy ra:");
            foreach (array_slice($errors, 0, 10) as $err) {
                $this->line("- $err");
            }
            if (count($errors) > 10) {
                $this->line("... và " . (count($errors) - 10) . " lỗi khác.");
            }
            Log::channel('single')->error('Import Mapping Errors', $errors);
        }
    }
}
