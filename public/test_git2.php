<?php
$output = shell_exec('cd /home/bill.ht-cargos.com/html && git show ab78a45 app/Services/EmsService.php');
echo "<pre>" . htmlspecialchars($output) . "</pre>";
