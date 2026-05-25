<?php
$c = file_get_contents('C:\Users\MAC\.gemini\antigravity\brain\0dc95e66-5f57-4741-9af9-92bea3d68872\.system_generated\steps\2120\content.md');
$c = strip_tags($c);
$pos = strpos($c, 'Authentication');
if ($pos !== false) {
    echo substr($c, $pos, 1000);
} else {
    echo "Authentication not found";
}
