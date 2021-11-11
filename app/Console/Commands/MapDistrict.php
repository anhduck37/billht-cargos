<?php

namespace App\Console\Commands;

use App\City;
use App\District;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MapDistrict extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map_district';

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
        $citys = City::get();
        foreach ($citys as $item) {
            $url = 'https://partner.viettelpost.vn/v2/categories/listDistrict?provinceId='.$item->city_code;
            $response = Http::get($url);
            $infoDistrict = array_key_exists('data', $response->json()) ? $response->json()['data'] : [];
            $data = [];
            foreach ($infoDistrict as $itemDistrict) {
                $dataDistrict = [
                    'district_name' => $itemDistrict['DISTRICT_NAME'],
                    'city_id' => $item->id,
                    'district_code' => $itemDistrict['DISTRICT_ID'],
                ];
                array_push($data, $dataDistrict);
            }
            District::insert($data);
        }
    }
}
