<?php
$output = shell_exec('cd /home/bill.ht-cargos.com/html && git log -S "createWebhook" --oneline app/Services/EmsService.php');
echo "Output: \n" . $output;
if (empty($output)) {
    echo "\nTrying git reflog or grep in history:\n";
    echo shell_exec('cd /home/bill.ht-cargos.com/html && git log -p app/Services/EmsService.php | grep -C 10 "createWebhook" | head -n 50');
}
