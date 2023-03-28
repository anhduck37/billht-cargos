<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTEXPRESS - Hệ thống quản lý vận đơn</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700" rel="stylesheet" media="screen">
    <style>
        .hover-underline:hover {
            text-decoration: underline !important;
        }
        @media (max-width: 600px) {
            .sm-w-full {
                width: 100% !important;
            }

            .sm-px-24 {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }

            .sm-py-32 {
                padding-top: 32px !important;
                padding-bottom: 32px !important;
            }

            .sm-leading-32 {
                line-height: 32px !important;
            }
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
            text-decoration: none;

            border: 1px solid transparent;
            border-radius: .375rem;
        }
        .text-center {
            text-align: center !important;
        }
        .mt-7
        {
            margin-top: 5rem !important;
        }.btn-primary
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
        .mt-4 {
            margin-top: 50px
        }
    </style>
</head>
<body style="margin: 0; width: 100%; padding: 0; word-break: break-word; -webkit-font-smoothing: antialiased; background-color: #eceff1;">
{{--<div style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; display: none;">Hệ thống quản lý vận đơn</div>--}}
<div role="article" aria-roledescription="email" aria-label="Default email title" lang="en" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly;">
    <table style="width: 100%; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center" style="mso-line-height-rule: exactly; background-color: #eceff1; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;">
                <table class="sm-w-full" style="width: 700px;" cellpadding="0" cellspacing="0" role="presentation">
                    <tr>
                        <td class="sm-py-32 sm-px-24" style="background-color: #333537;border-radius: 5px; mso-line-height-rule: exactly; padding: 48px; text-align: center; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;">
                            <a href="https://1.envato.market/vuexy_admin" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly;">
                                <img src="{{asset('image/order_manager.png')}}" width="300" alt="HTEXPRESS - Hệ thống quản lý vận đơn" style="max-width: 100%; vertical-align: middle; line-height: 100%; border: 0;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" class="sm-px-24" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly;">
                            <table style="width: 100%;" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td><td class="sm-px-24" style="mso-line-height-rule: exactly; border-radius: 4px; background-color: #ffffff; padding: 10px; text-align: left; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; font-size: 16px; line-height: 24px; color: #626262;">
                                        <p class="sm-leading-32" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; margin: 0; margin-bottom: 24px; font-size: 24px; font-weight: 600; color: #f6821f;text-align: center">
                                        @yield('title')
                                        </p>
                                        <div class="mt-4">
                                            <label>Xin chào <b>{{isset($order->sender) ? $order->sender->sender_name : '' }}</b></label>
                                        </div>
                                        <div style="margin-top: 10px;color: black;font-weight: 300">
                                            <label >{{$type_email ==  2 ? 'HTEXPRESS Đã giao thành công đơn hàng của bạn cho người nhận. Theo dõi đơn hàng với thông tin dưới đây: ' . route('tracking', ['order_code' => $order->order_code]) :  'HTEXPRESS đã tiếp nhận đơn hàng của bạn. Chúng tôi đang sắp xếp để chuyển đơn hàng của bạn đi.'}}</label>
                                            <p style="margin-top: 10px"><b>Mã vận đơn của bạn là {{$order->order_code}}</b></p>
                                        </div>
                                        @yield('table')
                                        <table class="mt-7" style="width: 100%;" cellpadding="0" cellspacing="0" role="presentation">
                                            <tr>
                                                <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; padding-top: 32px; padding-bottom: 32px;">
                                                    <div style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; height: 1px; background-color: #eceff1; line-height: 1px;">&zwnj;</div>
                                                </td>
                                            </tr>
                                        </table>

                                        <div class="text-center">
                                            <h3>CÔNG TY CPTM DVVC HH BẰNG ĐƯỜNG HK HTEXPRESS</h3>
                                            <p>Hà Nội: Số 27, ngõ 71 Hoàng Văn Thái, Khương Trung, Thanh Xuân,Hà Nội</p>
                                            <p>HCM: A51A Bạch Đằng, Phường 2, Q.Tân Bình, HCM</p>
                                            <p>Hotline: 1900.633.656 | Email: info@ht-cargo.com</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; height: 20px;"></td>
                                </tr>

                                <tr>
                                    <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; height: 16px;"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>

