<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$city = App\City::where('city_name', 'like', '%Lâm Đồng%')->first();
echo "City: " . ($city ? $city->city_name : 'null') . " | EMS Code: " . ($city ? $city->ems_code : 'null') . "\n";

$district = App\District::where('district_name', 'like', '%Đức Trọng%')->first();
echo "District: " . ($district ? $district->district_name : 'null') . " | EMS Code: " . ($district ? $district->ems_code : 'null') . "\n";

$ward = App\Ward::where('ward_name', 'like', '%Liên Nghĩa%')->where('district_id', $district->id ?? 0)->first();
echo "Ward: " . ($ward ? $ward->ward_name : 'null') . " | EMS Code: " . ($ward ? $ward->ems_code : 'null') . "\n";
