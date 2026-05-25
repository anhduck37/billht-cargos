<?php
$c = file_get_contents('C:\Users\MAC\.gemini\antigravity\brain\0dc95e66-5f57-4741-9af9-92bea3d68872\.system_generated\steps\2120\content.md');
$c = strip_tags($c);
if (preg_match_all('/GET\s+.*?api.*?vn[^\s]*/i', $c, $m)) {
    print_r($m[0]);
} else {
    echo "No GET API found\n";
}
if (preg_match_all('/api.*?ems.*?\.vn[^\s]*/i', $c, $m2)) {
    print_r($m2[0]);
} else {
    echo "No api ems vn found\n";
}
