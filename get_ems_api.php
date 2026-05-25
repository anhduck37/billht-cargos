<?php
$token = '63ec92522bc87dc5add15c66119e4ca3'; 

$ch = curl_init('http://ws.ems.com.vn/api/v1/address/district?merchant_token=' . $token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['province_code' => '10']));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
$resp = curl_exec($ch);
echo "POST Result: " . substr((string)$resp, 0, 500) . "\n";
curl_close($ch);
