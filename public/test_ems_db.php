<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$district = App\District::where('district_name', 'like', '%Đức Trọng%')->first();
echo "District info: " . json_encode($district) . "\n";
