<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\NewAddressPartnerMapping::where('partner_code', 'EMS')->count();
echo "EMS count: $count\n";

$vtpCount = \App\NewAddressPartnerMapping::where('partner_code', 'VTP')->count();
echo "VTP count: $vtpCount\n";
