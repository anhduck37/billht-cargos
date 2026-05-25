<?php
$c = file_get_contents('https://docws.ems.com.vn/tieng-viet/danh-muc/quan-huyen');
preg_match('/http:\/\/ws\.ems\.com\.vn\/api\/v1\/address[^\s<]*/', $c, $m);
echo "District API: " . ($m[0] ?? 'not found') . "\n";

$c2 = file_get_contents('https://docws.ems.com.vn/tieng-viet/danh-muc/phuong-xa');
preg_match('/http:\/\/ws\.ems\.com\.vn\/api\/v1\/address[^\s<]*/', $c2, $m2);
echo "Ward API: " . ($m2[0] ?? 'not found') . "\n";
