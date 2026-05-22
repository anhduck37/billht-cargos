<?php
$output = shell_exec('grep "webhook/ems" /usr/local/lsws/logs/access.log | tail -n 50');
if (empty($output)) {
    $output = shell_exec('grep "webhook/ems" /usr/local/lsws/logs/access.log /usr/local/lsws/bill.ht-cargos.com/logs/access.log* 2>&1');
}
if (empty($output)) {
    $output = shell_exec('tail -n 100 /usr/local/lsws/logs/error.log 2>&1');
}
echo "<pre>" . htmlspecialchars($output) . "</pre>";
