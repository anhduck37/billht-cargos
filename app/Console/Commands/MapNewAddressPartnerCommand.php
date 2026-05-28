<?php

namespace App\Console\Commands;

use App\NewAddressPartnerMapping;
use App\NewWard;
use Illuminate\Console\Command;

class MapNewAddressPartnerCommand extends Command
{
    protected $signature = 'address:map-new-partner
        {partner : Partner code, for example EMS or VTP}
        {new_ward_id : ID in new_wards}
        {partner_province_code : Partner province code}
        {partner_district_code=0 : Partner district code}
        {partner_ward_code=0 : Partner ward code}
        {--note= : Optional note for this manual mapping}';

    protected $description = 'Create or update partner mapping for one new ward.';

    public function handle()
    {
        $partnerCode = strtoupper(trim((string)$this->argument('partner')));
        if (!in_array($partnerCode, ['EMS', 'VTP'], true)) {
            $this->error('Partner code must be EMS or VTP.');
            return 1;
        }

        $newWard = NewWard::with('newProvince')->find($this->argument('new_ward_id'));
        if (!$newWard) {
            $this->error('New ward not found: ' . $this->argument('new_ward_id'));
            return 1;
        }

        $provinceCode = trim((string)$this->argument('partner_province_code'));
        $districtCode = trim((string)$this->argument('partner_district_code'));
        $wardCode = trim((string)$this->argument('partner_ward_code'));

        if ($provinceCode === '') {
            $this->error('Partner province code is required.');
            return 1;
        }

        $mapping = NewAddressPartnerMapping::updateOrCreate(
            [
                'new_ward_id' => $newWard->id,
                'partner_code' => $partnerCode,
            ],
            [
                'new_province_id' => $newWard->new_province_id,
                'partner_province_code' => $provinceCode,
                'partner_district_code' => $districtCode,
                'partner_ward_code' => $wardCode,
                'mapping_status' => 'mapped',
                'note' => $this->option('note') ?: 'Manual partner mapping',
            ]
        );

        $this->info('Mapping saved.');
        $this->line('Partner: ' . $partnerCode);
        $this->line('New ward: #' . $newWard->id . ' - ' . $newWard->name);
        $this->line('New province: #' . $newWard->new_province_id . ' - ' . optional($newWard->newProvince)->name);
        $this->line('Partner codes: province=' . $mapping->partner_province_code . ', district=' . $mapping->partner_district_code . ', ward=' . $mapping->partner_ward_code);

        return 0;
    }
}
