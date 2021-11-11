<?php

namespace App\Console\Commands;

use App\District;
use App\Ward;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MapWard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map_ward';

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
        District::chunkById(50, function ($districts) {
            foreach ($districts as $item) {
                $urlWard = 'https://partner.viettelpost.vn/v2/categories/listWards?districtId='.$item->district_code;
                $response = Http::get($urlWard);
                $infoWard = array_key_exists('data', $response->json()) ? $response->json()['data'] : [];
                $data = [];
                foreach ($infoWard as $itemWard) {
                    $dataWard = [
                        'district_id' => $item->id,
                        'ward_name' => $itemWard['WARDS_NAME'],
                    ];
                    array_push($data, $dataWard);
                }
                Ward::insert($data);
            }
        });
    }
}
