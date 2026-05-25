<?php
$c = file_get_contents('C:\Users\MAC\.gemini\antigravity\brain\0dc95e66-5f57-4741-9af9-92bea3d68872\.system_generated\steps\2296\content.md');
$c = strip_tags($c);
$pos = strpos($c, '/address/district');
if ($pos !== false) {
    echo substr($c, max(0, $pos - 500), 1000);
}
