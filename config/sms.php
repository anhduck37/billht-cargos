<?php

    return [
        'apiKey' => env('SMS_API_KEY'),
        'secretKey' => env('SMS_SECRET_KEY'),
        'textSendSMS' => env('TEXT_SEND_SMS'),
        'url' => env('SMS_URL', 'http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post_json/'),
        'brandName' => env('SMS_BRAND_NAME')
    ];
