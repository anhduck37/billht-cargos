<?php
$c = file_get_contents('C:\Users\MAC\.gemini\antigravity\brain\0dc95e66-5f57-4741-9af9-92bea3d68872\.system_generated\steps\2296\content.md');
preg_match('/(GET|POST|PUT)\s+http:\/\/ws\.ems\.com\.vn\/api\/v1\/address\/district/', $c, $m);
print_r($m);
