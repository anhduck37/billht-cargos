<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$map = [
    'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
    'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
    'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
    'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
    'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
    'ă' => 'a', 'â' => 'a', 'đ' => 'd', 'ê' => 'e', 'ô' => 'o', 'ơ' => 'o', 'ư' => 'u',
    'ạ' => 'a', 'ả' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
    'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
    'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
    'ỉ' => 'i', 'ị' => 'i',
    'ọ' => 'o', 'ỏ' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
    'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
    'ụ' => 'u', 'ủ' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
    'ỳ' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
];

$normalize = function ($str) use ($map) {
    $str = mb_strtolower(trim($str ?? ''));
    $str = strtr($str, $map);
    $str = preg_replace('/[^a-z0-9\s]/u', '', $str);
    return preg_replace('/\s+/', ' ', trim($str));
};

$cityNorm = $normalize("HA NOI");
$citySearch = $cityNorm;

$city = \App\Models\City::all()->first(function ($c) use ($citySearch, $normalize) {
    $name = $normalize($c->city_name);
    return $name === $citySearch || str_contains($name, $citySearch) || str_contains($citySearch, $name);
});
if ($city) {
    echo "Found City: " . $city->city_name . "\n";
    $districtStr = "YEN HOA";
    $districtNorm = $normalize($districtStr);
    echo "Dist norm: " . $districtNorm . "\n";

    $districts = \App\Models\District::where('city_id', $city->id)->get();
    foreach ($districts as $d) {
        $name = $normalize($d->district_name);
        if ($name === $districtNorm || str_contains($name, $districtNorm) || str_contains($districtNorm, $name)) {
            echo "MATCHED: " . $d->district_name . " (Norm: " . $name . ")\n";
        }
    }
}
