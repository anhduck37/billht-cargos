<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>HTEXPRESS - Hệ thống quản lý vận đơn</title>
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
        .text-center
        {
            text-align: center !important;
        }
        .header-custom {
            background-color: #333537;
        }
        .container
        {
            width: 100%;
            margin-right: auto;
            margin-left: auto;
            /*padding-right: 15px;*/
            /*padding-left: 15px;*/
            background-color: white;
            border: 1px solid #333537;
        }
        .container
        {
            min-width: 992px !important;
        }

        .mt-7
        {
            margin-top: 6rem !important;
        }
        .btn
        {
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.5;

            display: inline-block;

            padding: .625rem 1.25rem;

            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;

            border: 1px solid transparent;
            border-radius: .375rem;
        }
        .btn-primary
        {
            color: #fff;
            border-color: #f6821f;
            background-color: #f6821f;
            box-shadow: 0 4px 6px rgba(50, 50, 93, .11), 0 1px 3px rgba(0, 0, 0, .08);
        }
        .btn-primary:hover
        {
            color: #fff;
            border-color: #cf7224;
            background-color: #cf7224;
        }
        .btn-primary:focus,
        .btn-primary.focus
        {
            box-shadow: 0 4px 6px rgba(50, 50, 93, .11), 0 1px 3px rgba(0, 0, 0, .08), 0 0 0 0 rgba(94, 114, 228, .5);
        }
        .btn-primary.disabled,
        .btn-primary:disabled
        {
            color: #fff;
            border-color: #cf7224;
            background-color: #cf7224;
        }
        .btn-primary:not(:disabled):not(.disabled):active,
        .btn-primary:not(:disabled):not(.disabled).active,
        .show > .btn-primary.dropdown-toggle
        {
            color: #fff;
            border-color: #cf7224;
            background-color: #cf7224;
        }
        .btn-primary:not(:disabled):not(.disabled):active:focus,
        .btn-primary:not(:disabled):not(.disabled).active:focus,
        .show > .btn-primary.dropdown-toggle:focus
        {
            box-shadow: none, 0 0 0 0 rgba(94, 114, 228, .5);
        }
        .mt-3,
        .my-3
        {
            margin-top: 1rem !important;
        }
        @media screen and (prefers-reduced-motion: reduce)
        {
            .btn
            {
                transition: none;
            }
        }
        .custom-padding {
            padding-right: 15px;
            padding-left: 15px;
        }


    </style>
    <script type="text/javascript" src="{{asset('/js/renderCode.js')}}"></script>
</head>
<body>
<div class="container">

    <div class="text-center header-custom">
        <img width="400" src="{{asset('/image/order_manager.png')}}">
    </div>
    <div class="custom-padding">
    <div class="text-center row mt-4"><div class="col"><h1 style="color: #f6821f">@yield('title')</h1></div></div>
    <div class="mt-4">
        <label>Xin chào {{isset($order->sender) ? $order->sender->sender_name : '' }}</label>
    </div>
    <div class="row mt-4">
        <div class="col"><label>HTEXPRESS đã tiếp nhận đơn hàng của bạn. Chúng tôi đang sắp xếp để chuyển đơn hàng của bạn đi. <br> <b>Mã vận đơn của bạn là {{$order->order_code}}</b></label></div>
    </div>
    @yield('table')
    <div class="text-center mt-7">
        <h3>CÔNG TY CPTM DVVC HH BẰNG ĐƯỜNG HK HTEXPRESS</h3>
        <p>Hà Nội: Số 27, ngõ 71 Hoàng Văn Thái, Khương Trung, Thanh Xuân,Hà Nội</p>
        <p>HCM: A51A Bạch Đằng, Phường 2, Q.Tân Bình, HCM</p>
        <p>Hotline: 1900.633.656 | Email: info@ht-cargo.com</p>
    </div>
    </div>
</div>
</body>
</html>
