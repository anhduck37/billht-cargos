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
        District::chunkById(500, function ($districts) {
            foreach ($districts as $item) {
                $urlWard = 'https://partner.viettelpost.vn/v2/categories/listWards?districtId='.$item->district_code;
                $response = Http::get($urlWard);
                $infoWard = array_key_exists('data', $response->json()) ? $response->json()['data'] : [];
                $data = [];
                foreach ($infoWard as $itemWard) {
                    $dataWard = [
                        'district_id' => $item->id,
                        'ward_name' => $itemWard['WARDS_NAME'],
                        'ward_code' => $itemWard['WARDS_ID'],
                    ];
                    $ward = Ward::where('district_id', $item->id)->where('ward_name',$itemWard['WARDS_NAME'])->first();
                    if($ward) {
                        $ward->ward_code = $dataWard['ward_code'];
                        $ward->save();
                    } else {
                        array_push($data, $dataWard);
                    }
                    
                }
                if(!empty($data)) {
                    Ward::insert($data);
                }
            }
        });
        Ward::whereNull('ward_code')->delete();
    }
}
