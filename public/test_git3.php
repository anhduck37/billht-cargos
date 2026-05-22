<?php
$output = shell_exec('cd /home/bill.ht-cargos.com/html && git log -S "createWebhook" --oneline app/Services/EmsService.php');
echo "Output: \n" . $output;
