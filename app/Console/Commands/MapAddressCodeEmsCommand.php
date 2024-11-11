<?php

namespace App\Console\Commands;

use App\City;
use App\District;
use App\Services\EmsService;
use App\Ward;
use Illuminate\Console\Command;

use function PHPUnit\Framework\isEmpty;

class MapAddressCodeEmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'address_code_ems';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $emsService = new EmsService();
        $dataCity = $emsService->getCities();
        $mapCitySlug = [];
        foreach ($dataCity as $city) {
            $nameMap = $this->convertStr($city['name'], $emsService);
            $nameMap = str_replace(' ', '', $nameMap);
            $mapCitySlug[$nameMap] = $city['code'];
        }
        $cities = City::whereNull('ems_code')->get();
        if ($cities) {
            foreach ($cities as $item) {
                $nameDb = $this->convertStr($item->city_name, $emsService);
                $item->ems_code = $mapCitySlug[$nameDb] ?? null;
                $item->save();
            }
        }

        $dataDistrict = $emsService->getDistrict();
        foreach ($dataDistrict as $district) {
            $districtDb = District::where('district_name', 'LIKE', '%' . $district['name'] . '%')->first();
            if ($districtDb) {
                $districtDb->ems_code = $district['code'];
                $districtDb->save();
            }
        }

        $dataWard = $emsService->getWard();
        foreach ($dataWard as $ward) {
            $wardDb = Ward::where('ward_name', 'LIKE', '%' . $ward['name'] . '%')->first();
            if ($wardDb) {
                $wardDb->ems_code = $ward['code'];
                $wardDb->save();
            }
        }
    }

    private function convertStr($str, $emsService)
    {
        $nameDb = strtoupper($emsService->stripVN($str));
        $nameDb = str_replace('-', '', $nameDb);
        $nameDb = str_replace(' ', '', $nameDb);
        return $nameDb;
    }
}
