<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\NewProvince;
use App\NewWard;
use App\NewAddressPartnerMapping;
use App\Services\Address2025Service;
use Illuminate\Support\Facades\DB;

class SyncEmsFallbackMappingCommand extends Command
{
    protected $signature = 'ems:sync-fallback-mapping';
    protected $description = 'Sync EMS mappings by matching New Wards with old Wards in DB (Fallback)';

    public function handle(Address2025Service $addressService)
    {
        $this->info('Starting EMS Fallback Mapping...');
        $newWards = NewWard::with('newProvince')->get();

        $mappedCount = 0;
        foreach ($newWards as $newWard) {
            if (!$newWard->newProvince) continue;
            
            $provinceName = $newWard->newProvince->name;
            $wardName = $newWard->name;

            $cleanProv = $this->cleanName($provinceName);
            $cleanWard = $this->cleanName($wardName);

            // Find old city
            $oldCity = DB::table('citys')->where('city_name', 'LIKE', '%' . $cleanProv . '%')->first();
            if (!$oldCity) continue;

            // Find old ward
            $oldWardQuery = DB::table('wards')
                ->join('districts', 'wards.district_id', '=', 'districts.id')
                ->select('wards.ems_code as ward_ems', 'districts.ems_code as district_ems')
                ->where('districts.city_id', $oldCity->id)
                ->where('wards.ward_name', 'LIKE', '%' . $cleanWard . '%')
                ->whereNotNull('wards.ems_code')
                ->where('wards.ems_code', '!=', '')
                ->whereNotNull('districts.ems_code')
                ->where('districts.ems_code', '!=', '')
                ->first();

            if ($oldWardQuery) {
                NewAddressPartnerMapping::updateOrCreate(
                    [
                        'new_ward_id' => $newWard->id,
                        'partner_code' => 'EMS'
                    ],
                    [
                        'new_province_id' => $newWard->new_province_id,
                        'partner_province_code' => $oldCity->ems_code,
                        'partner_district_code' => $oldWardQuery->district_ems,
                        'partner_ward_code' => $oldWardQuery->ward_ems,
                        'mapping_status' => 'mapped',
                        'note' => 'Fallback matching EMS'
                    ]
                );
                $mappedCount++;
            }
        }
        
        $this->info("Completed. Mapped $mappedCount / " . $newWards->count() . " wards.");
    }

    private function cleanName($name)
    {
        $prefixes = ['Thành phố ', 'Tỉnh ', 'Quận ', 'Huyện ', 'Thị xã ', 'Phường ', 'Xã ', 'Thị trấn '];
        foreach ($prefixes as $prefix) {
            if (mb_stripos($name, $prefix) === 0) {
                return trim(mb_substr($name, mb_strlen($prefix)));
            }
        }
        return $name;
    }
}
