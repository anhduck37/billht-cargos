<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Argon Dashboard') }}</title>
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
            margin-bottom: -0.5rem;
        }
        body {
            font-size: 0.9rem;
            color: black;
        }
        .card-title {
            margin: 0;
        }
        p {
            margin-bottom: 0;
        }

    </style>
</head>
<body
    onload="window.print()"
>

<div class="main-content">
    @foreach($orders as $order)
    <div class="card" style="margin-bottom: 20px">
        <div class="card-body">
            <div class="row custom-row">
                <div class="col" >
                    <div class="card-body">
                        <img width="150" src="{{asset('image/order_manager.png')}}">
                    </div>
                </div>
                <div class="col" style="margin-left: 10px">
                    <div class="card-body">
                        <h2 class="card-title">Hotline: <b>1900 633 656</b></h2>
                        <p class="card-text">Website: www.ht-cargos.com</p>
                        <p class="card-text">Email: info@ht-cargos.com</p>
                    </div>
                </div>
                <div class="col">
                    <div class="card-body text-center">
                        <p><i class="fa fa-barcode fa-5x" aria-hidden="true" ></i><i class="fa fa-barcode fa-5x" aria-hidden="true" ></i></p>
                        <label>{{$order->order_code}}</label>
                    </div>
                </div>
            </div>


            <div class="row custom-row">
                <div class="col custom-col">
                    <div class="card-body">
                        <h4 class="card-title">Họ tên, địa chỉ người gửi: </h4>
                        <p >{{isset($order->sender) ? $order->sender->sender_name : '.....'}}</p>
                        <p >{{isset($order->sender) ? $order->sender->address . ', ' . (isset($order->sender->ward) ? $order->sender->ward->ward_name : '') . ', ' . (isset($order->sender->district) ? $order->sender->district->district_name : '') . ', ' . (isset($order->sender->city) ? $order->sender->city->city_name : '') : '.....'}}</p>
                        <p><b>Điện thoại:</b> {{isset($order->sender) ? $order->sender->sender_phone : '.....'}}</p>
                    </div>
                </div>
                <div class="col">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-8">
                                <h4 class="card-title">{{\App\Service::SERVICE_MAP[\App\Service::SERVICE_DOMESTIC]['name']}}</h4>
                                <div class="row">
                                    @foreach(\App\Service::SERVICE_MAP[\App\Service::SERVICE_DOMESTIC]['value'] as $key => $item )
                                    <div class="col-md-4" style="margin-left: 20px">
                                            <input type="checkbox" @if(in_array($key, $order->serviceArray($order->id))) checked @endif class="form-check-input">
                                            <label for="check1">{{$item}}</label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-4">
                                <h4 class="card-title">{{\App\Service::SERVICE_MAP[\App\Service::SERVICE_INTERNATIONAL]['name']}}</h4>
                                <div class="row">
                                    @foreach(\App\Service::SERVICE_MAP[\App\Service::SERVICE_INTERNATIONAL]['value'] as $key => $item )
                                        <div class="col-md-12" style="margin-left: 20px">
                                            <input type="checkbox" @if(in_array($key, $order->serviceArray($order->id))) checked @endif class="form-check-input">
                                            <label>{{$item}}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row custom-row">
                <div class="col custom-col">
                    <div class="card-body">
                        <h4 class="card-title">Họ tên, địa chỉ người nhận: </h4>
                        <p class="card-text">{{isset($order->receiver) ? $order->receiver->receiver_name : '.....'}}</p>
                        <p class="card-text"><small class="text-muted">{{isset($order->receiver) ? $order->receiver->address . ', ' . (isset($order->receiver->ward) ? $order->receiver->ward->ward_name : '') . ', ' . (isset($order->sender->district) ? $order->sender->district->district_name : '') . ', ' . (isset($order->sender->city) ? $order->sender->city->city_name : '') : '.....'}} </small></p>
                        <p class="card-text"><b>Điện thoại:</b> {{isset($order->receiver) ? $order->receiver->receiver_phone : '.....'}}</p>
                    </div>
                </div>
                <div class="col">
                    <div class="card-body">
                        <div class="row">
                            <div class="col">
                                <h4 class="card-title">{{\App\Service::SERVICE_MAP[\App\Service::SERVICE_EXTRA]['name']}}</h4>
                                <div class="row">
                                    @foreach(\App\Service::SERVICE_MAP[\App\Service::SERVICE_EXTRA]['value'] as $key => $item )
                                        <div class="col-md-3" style="margin-left: 20px">
                                            <input type="checkbox" @if(in_array($key, $order->serviceArray($order->id))) checked @endif class="form-check-input">
                                            <label>{{$item}}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <h4 class="card-title">Khai báo nội dung và số lượng gửi:</h4>
                                <p>{{$order->department}}</p>
                            </div>
                        </div>
                        <p style="margin-top: 20px">Giá trị hàng hóa:................</p>
                    </div>
                </div>
            </div>

            <div class="row custom-row">
                <div class="col custom-col">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="card-title">Ký xác nhận người gửi hàng: </h4>
                                <p class="card-text"><b>Ngày gửi:</b> </p>
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="card-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người gửi </p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="card-text">Dấu ngày gửi</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h4 class="card-title">Thông tin giao nhận</h4>
                                <p class="card-text">Ngày gửi:..............</p>
                                <p class="card-text">Nv phát:..............</p>
                                <p class="card-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người nhận</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <p class="card-text">NV-Chấp nhận:..............</p>
                            </div>
                            <div class="col-md-4">
                                <p class="card-text">Bộ phận:..............</p>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title">Thông tin hàng hóa</h4>
                                        <div class="row" style="margin-bottom: 100px">
                                            <div class="col-md-4">Số kiện</div>
                                            <div class="col-md-8">Trọng lượng thức tế</div>
                                        </div>
                                        <p style="margin-left: 30px">Kích thước (dài x rộng x cao) cm</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title" >Hình thức thanh toán</h4>
                                        <div class="row">
                                            @foreach(\App\Models\Order::PAYMENT_METHOD_MAP as $key => $item )
                                                <div class="col-md-4" style="margin-left: 20px">
                                                    <input type="checkbox" @if($order->payment_method == $key) checked @endif class="form-check-input">
                                                    <label>{{$item}}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h4 class="card-title">Trọng lượng thanh toán</h4>
                                <p>Trọng lượng tính cước</p>
                                <p>...................................</p>
                                <p>Cước phí:.......................</p>
                                <p>Phí khác:.......................</p>
                                <p>VAT:............................</p>
                                <p>Bảo hiểm:.....................</p>
                                <p>Tổng cộng:.....................</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        @if($level == \App\User::LEVEL_ADMIN)
            <div class="card" style="margin-bottom: 20px">
                <div class="card-body">
                    <div class="row custom-row">
                        <div class="col" >
                            <div class="card-body">
                                <img width="150" src="{{asset('image/order_manager.png')}}">
                            </div>
                        </div>
                        <div class="col" style="margin-left: 10px">
                            <div class="card-body">
                                <h2 class="card-title">Hotline: <b>1900 633 656</b></h2>
                                <p class="card-text">Website: www.ht-cargos.com</p>
                                <p class="card-text">Email: info@ht-cargos.com</p>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card-body text-center">
                                <p><i class="fa fa-barcode fa-5x" aria-hidden="true" ></i><i class="fa fa-barcode fa-5x" aria-hidden="true" ></i></p>
                                <label>{{$order->order_code}}</label>
                            </div>
                        </div>
                    </div>


                    <div class="row custom-row">
                        <div class="col custom-col">
                            <div class="card-body">
                                <h4 class="card-title">Họ tên, địa chỉ người gửi: </h4>
                                <p >{{isset($order->sender) ? $order->sender->sender_name : '.....'}}</p>
                                <p >{{isset($order->sender) ? $order->sender->address . ', ' . (isset($order->sender->ward) ? $order->sender->ward->ward_name : '') . ', ' . (isset($order->sender->district) ? $order->sender->district->district_name : '') . ', ' . (isset($order->sender->city) ? $order->sender->city->city_name : '') : '.....'}}</p>
                                <p><b>Điện thoại:</b> {{isset($order->sender) ? $order->sender->sender_phone : '.....'}}</p>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-8">
                                        <h4 class="card-title">{{\App\Service::SERVICE_MAP[\App\Service::SERVICE_DOMESTIC]['name']}}</h4>
                                        <div class="row">
                                            @foreach(\App\Service::SERVICE_MAP[\App\Service::SERVICE_DOMESTIC]['value'] as $key => $item )
                                                <div class="col-md-4" style="margin-left: 20px">
                                                    <input type="checkbox" @if(in_array($key, $order->serviceArray($order->id))) checked @endif class="form-check-input">
                                                    <label for="check1">{{$item}}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="card-title">{{\App\Service::SERVICE_MAP[\App\Service::SERVICE_INTERNATIONAL]['name']}}</h4>
                                        <div class="row">
                                            @foreach(\App\Service::SERVICE_MAP[\App\Service::SERVICE_INTERNATIONAL]['value'] as $key => $item )
                                                <div class="col-md-12" style="margin-left: 20px">
                                                    <input type="checkbox" @if(in_array($key, $order->serviceArray($order->id))) checked @endif class="form-check-input">
                                                    <label>{{$item}}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row custom-row">
                        <div class="col custom-col">
                            <div class="card-body">
                                <h4 class="card-title">Họ tên, địa chỉ người nhận: </h4>
                                <p class="card-text">{{isset($order->receiver) ? $order->receiver->receiver_name : '.....'}}</p>
                                <p class="card-text"><small class="text-muted">{{isset($order->receiver) ? $order->receiver->address . ', ' . (isset($order->receiver->ward) ? $order->receiver->ward->ward_name : '') . ', ' . (isset($order->sender->district) ? $order->sender->district->district_name : '') . ', ' . (isset($order->sender->city) ? $order->sender->city->city_name : '') : '.....'}} </small></p>
                                <p class="card-text"><b>Điện thoại:</b> {{isset($order->receiver) ? $order->receiver->receiver_phone : '.....'}}</p>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title">{{\App\Service::SERVICE_MAP[\App\Service::SERVICE_EXTRA]['name']}}</h4>
                                        <div class="row">
                                            @foreach(\App\Service::SERVICE_MAP[\App\Service::SERVICE_EXTRA]['value'] as $key => $item )
                                                <div class="col-md-3" style="margin-left: 20px">
                                                    <input type="checkbox" @if(in_array($key, $order->serviceArray($order->id))) checked @endif class="form-check-input">
                                                    <label>{{$item}}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title">Khai báo nội dung và số lượng gửi:</h4>
                                        <p>{{$order->department}}</p>
                                    </div>
                                </div>
                                <p style="margin-top: 20px">Giá trị hàng hóa:................</p>
                            </div>
                        </div>
                    </div>

                    <div class="row custom-row">
                        <div class="col custom-col">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 class="card-title">Ký xác nhận người gửi hàng: </h4>
                                        <p class="card-text"><b>Ngày gửi:</b> </p>
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p class="card-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người gửi </p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="card-text">Dấu ngày gửi</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h4 class="card-title">Thông tin giao nhận</h4>
                                        <p class="card-text">Ngày gửi:..............</p>
                                        <p class="card-text">Nv phát:..............</p>
                                        <p class="card-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người nhận</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="card-text">NV-Chấp nhận:..............</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="card-text">Bộ phận:..............</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col">
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col">
                                                <h4 class="card-title">Thông tin hàng hóa</h4>
                                                <div class="row" style="margin-bottom: 100px">
                                                    <div class="col-md-4">Số kiện</div>
                                                    <div class="col-md-8">Trọng lượng thức tế</div>
                                                </div>
                                                <p style="margin-left: 30px">Kích thước (dài x rộng x cao) cm</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <h4 class="card-title" >Hình thức thanh toán</h4>
                                                <div class="row">
                                                    @foreach(\App\Models\Order::PAYMENT_METHOD_MAP as $key => $item )
                                                        <div class="col-md-4" style="margin-left: 20px">
                                                            <input type="checkbox" @if($order->payment_method == $key) checked @endif class="form-check-input">
                                                            <label>{{$item}}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h4 class="card-title">Trọng lượng thanh toán</h4>
                                        <p>Trọng lượng tính cước</p>
                                        <p>...................................</p>
                                        <p>Cước phí:.......................</p>
                                        <p>Phí khác:.......................</p>
                                        <p>VAT:............................</p>
                                        <p>Bảo hiểm:.....................</p>
                                        <p>Tổng cộng:.....................</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

</body>
</html>
