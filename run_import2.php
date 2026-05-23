<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $command = new \App\Console\Commands\ImportVn2025PartnerMappings();
    $command->setLaravel($app);
    $input = new \Symfony\Component\Console\Input\ArrayInput([]);
    $output = new \Symfony\Component\Console\Output\ConsoleOutput();
    
    // Instead of execute, just call handle directly to bypass symfony console issues if any
    $addressService = app(\App\Services\Address2025Service::class);
    // It uses $this->option('file') which requires symfony setup.
    // Better to run it through artisan command properly but capture output
    $exitCode = \Illuminate\Support\Facades\Artisan::call('import:vn2025-partner-mappings', [], $output);
    echo "Exit Code: " . $exitCode . "\n";
    echo \Illuminate\Support\Facades\Artisan::output();
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
