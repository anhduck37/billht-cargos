<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>HTEXPRESS - Hệ thống quản lý vận đơn</title>
    <!-- Favicon -->
    <link href="{{ asset('argon/img/brand/favicon.png') }}" rel="icon" type="image/png">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <!-- Extra details for Live View on GitHub Pages -->

    <!-- Icons -->
    <link href="{{ asset('argon/vendor/nucleo/css/nucleo.css') }}" rel="stylesheet">
    <link href="{{ asset('argon/vendor/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <!-- Argon CSS -->
    <link type="text/css" href="{{ asset('argon/css/argon.css?v=1.0.0') }}" rel="stylesheet">
    <style>
        .card-body {
            padding: 0;
        }
        .col {
            padding-right: 0;
        }
        .custom-row {
            border: 1px solid;
        }
        .custom-col {
            border-right: 1px solid;
        }
        label {
            margin-bottom: 0;
        }
        .text-muted {
            color: black !important;
        }
        body {
            font-size: 0.95rem;
            color: black;
            font-weight: normal;
        }
        .size-text {
            font-weight: 400;
        }
        .card-title {
            margin: 0;
            font-weight: bold;
            color: black;
        }
        p {
            margin-bottom: 0;
            font-weight: 550;
        }

    </style>
    <script type="text/javascript" src="{{asset('/js/renderCode.js')}}"></script>
</head>
<body>

<div class="main-content">
    <div class="text-center">
        <img width="400" src="{{asset('/image/logo_print.png')}}">
    </div>
    <div class="row">
        <div class="col-4">
        </div>
        <div class="col-4">
            <div class="row">
                <div class="col-6 text-center">
                    <i class="fas fa-search-location fa-2x" style="color: #f6821f;"></i>
                    <p>Theo dõi đơn hàng</p>
                </div>
                <div class="col-6 text-center">
                    <i class="far fa-question-circle fa-2x" style="color: #f6821f;"></i>
                    <p>Dịch vụ</p>
                </div>
            </div>
        </div>
        <div class="col-4">
        </div>
    </div>
    <div class="text-center"><h1 style="color: #f6821f">Đơn hàng của bạn đã được xác nhận!</h1></div>
</div>
</body>
</html>
