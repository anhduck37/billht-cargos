<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$url = $_ENV['EMS_URL'] ?? 'https://mci.emsone.com.vn/Execute';
$access_key = $_ENV['EMS_ACCESS_KEY'] ?? '';
$secret_key = $_ENV['EMS_SECRET_KEY'] ?? '';
$crm_code = $_ENV['EMS_CRM_CODE'] ?? '8006';

$data = [
    "CrmOrPaypostCode" => $crm_code,
    "CustomerToken" => $crm_code,
    "OrderCode" => "HE_TEST_123461",
    "OrderName" => "Hàng hóa",
    "OrderValue" => 0,
    "OrderQuantity" => 1,
    "Note" => "Test",
    "Message" => "Test",
    "Channel" => "WEB",
    "IsTransport" => "Y",
    "IsSendTransport" => "Y",
    "WareHouseID" => 0,
    "BuyerInfo" => [
        "FullName" => "Test",
        "MobileNumber" => "0912345678",
        "ProvinceID" => 90,
        "DistrictID" => 9028,
        "WardID" => 90324,
        "Street" => "Test",
        "IsUpdate" => "N"
    ],
    "SenderInfo" => [
        "FullName" => "Test",
        "MobileNumber" => "0912345678",
        "ProvinceID" => 10,
        "DistrictID" => 1120,
        "WardID" => 11400,
        "Street" => "Test"
    ],
    "ReceiverInfo" => [
        "FullName" => "Test",
        "MobileNumber" => "0912345678",
        "ProvinceID" => 90,
        "DistrictID" => 9028,
        "WardID" => 90324,
        "Street" => "Test"
    ],
    "TransportInfo" => [
        "TransportMainServiceID" => 21,
        "TransportExtraServiceID" => "",
        "CollectionType" => 1,
        "TotalCOD" => "0",
        "TransportPayer" => 1,
        "TransportWeight" => 60
    ]
];

$dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
$code = "PARTNER_ORDER_ADD";
$signatureString = $code . $dataJson . $secret_key;
$signature = hash('sha256', $signatureString);

$body = [
    'Code' => $code,
    'Data' => $dataJson,
    'Signature' => $signature
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_key
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
curl_close($ch);
echo "Response from EMS:\n";
echo $response . "\n";
