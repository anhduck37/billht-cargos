<?php

namespace App\Console\Commands;

use App\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MapCity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map_city';

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
        $response = Http::get('https://partner.viettelpost.vn/v2/categories/listProvinceById?provinceId=-1');
        $body = $response->json();
        $infoCity = array_key_exists('data', $body) ? $body['data'] : [];
        foreach ($infoCity as $item) {
            $dataCity = [
                'city_name' => $item['PROVINCE_NAME'],
                'city_code' => $item['PROVINCE_ID'],
                'language' => 'vi'
            ];
            $city = City::where('city_name', $dataCity['city_name'])->first();
            if ($city) {
                City::where('id', $city->id)->update($dataCity);
            } else {
                $city = City::create($dataCity);
            }
        }
    }
}
