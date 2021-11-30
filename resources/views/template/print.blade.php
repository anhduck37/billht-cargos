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
            font-family: arial;
            padding: 0;
        }
        .col {
            padding-right: 0;
        }
        .custom-row {
            border: 1px solid;
            margin-left: 10px;
            margin-right: 10px;
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
            font-size: 0.8rem;
            color: black;
            font-weight: normal;
        }
        .size-text {
            font-weight: 500;
        }
        .card-title {
            margin: 0;
            font-weight: bold;
            color: black;
        }
        p {
            margin-bottom: 0;
        }
        .card {
            margin-left: 10px;
            margin-right: 10px;
        }
        @media print {
            .page {page-break-after: always;}
        }
    </style>
    <script type="text/javascript" src="{{asset('/js/renderCode.js')}}"></script>
</head>
<body
    onload="window.print()"
>

<div class="main-content">
    @foreach($orders as $order)
        <div class="page">
    <div class="card">
        <div class="card-body">
            <div class="row custom-row">
                <div class="col-5 mt-4" >
                    <div class="card-body">
                        <img width="300" src="{{asset('image/logo_print.png')}}">
                    </div>
                </div>
                <div class="col-3 mt-4">
                    <div class="card-body">
                        <h2 class="card-title mt-2 size-text">Hotline: <b>1900 633 656</b></h2>
                        <p class="card-text size-text">Website: www.ht-cargos.com</p>
                        <p class="card-text size-text">Email: info@ht-cargos.com</p>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card-body text-center">
                        <p class="size-text"><svg id="{{$order->order_code}}"></svg></p>
                    </div>
                </div>
            </div>


            <div class="row custom-row">
                <div class="col custom-col">
                    <div class="card-body">
                        <h4 class="card-title">Họ tên, địa chỉ người gửi: </h4>
                        <p class="size-text">{{isset($order->sender) ? $order->sender->sender_name : '.....'}}</p>
                        <p class="size-text" >{{isset($order->sender) ? (isset($order->sender->address) ? $order->sender->address . ', ' : '') . (isset($order->sender->ward) ? $order->sender->ward->ward_name . ', ' : '')  . (isset($order->sender->district) ? $order->sender->district->district_name . ', ' : '') . (isset($order->sender->city) ? $order->sender->city->city_name : '') : '.....'}}</p>
                        <p class="card-text"><h4 class="card-title">Phòng ban:</h4> {{ $order->department }}</p>
                        <p class="size-text"><h4 class="card-title">Điện thoại:</h4> {{isset($order->sender) ? $order->sender->sender_phone : '.....'}}</p>
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
                        <p class="card-text size-text">{{isset($order->receiver) ? $order->receiver->receiver_name : '.....'}}</p>

                        <p class="card-text size-text"><small class="text-muted">{{isset($order->receiver) ? (isset($order->receiver->address) ? $order->receiver->address . ', ' : '' ). (isset($order->receiver->ward) ? $order->receiver->ward->ward_name . ', ' : '')  . (isset($order->receiver->district) ? $order->receiver->district->district_name . ', ' : '') . (isset($order->receiver->city) ? $order->receiver->city->city_name : '') : '.....'}} </small></p>

                        <p class="card-text size-text"><h4 class="card-title">Điện thoại:</h4> {{isset($order->receiver) ? $order->receiver->receiver_phone : '.....'}}</p>
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
                                <p class="size-text">{{$order->note}}</p>
                            </div>
                        </div>
                        <p style="margin-top: 20px" class="size-text">Giá trị hàng hóa: {{$order->total}}</p>
                    </div>
                </div>
            </div>

            <div class="row custom-row">
                <div class="col custom-col">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-7">
                                <h4 class="card-title">Ký xác nhận người gửi hàng: </h4>
                                <p class="card-text size-text"><b>Ngày gửi:</b> {{$order->converDate($order->order_date)}} </p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text size-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người gửi </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="card-text size-text">Dấu ngày gửi</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h4 class="card-title">Thông tin giao nhận</h4>
                                <p class="card-text size-text">Ngày gửi:..............</p>
                                <p class="card-text size-text">Nv phát:..............</p>
                                <p class="card-text size-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người nhận</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-7">
                                <p class="card-text size-text">NV-Chấp nhận:..............</p>
                            </div>
                            <div class="col-md-5">
                                <p class="card-text size-text">Bộ phận:..............</p>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col">
                    <div class="card-body">

                        <div class="row">
                            <div class="col-md-7">
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title">Thông tin hàng hóa</h4>
                                        <div class="row" style="margin-bottom: 70px">
                                            <div class="col-md-4 size-text">Số kiện</div>
                                            <div class="col-md-8 size-text">Trọng lượng thực tế <p class="col-5 text-center"> {{($order->weight ? $order->weight : 0) . ' g'}}</p></div>

                                        </div>
                                        <p class="size-text" style="margin-left: 30px">Kích thước ({{($order->height ? $order->height : 0) . ' x '. ($order->long ? $order->long : 0) . ' x '. ($order->width ? $order->width : 0) .' cm'}})</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title" >Hình thức thanh toán</h4>
                                        <div class="row">
                                            @foreach(\App\Models\Order::PAYMENT_METHOD_MAP as $key => $item )
                                                <div class="col-md-4" style="margin-left: 20px">
                                                    <input type="checkbox" @if($order->payment_method == $key) checked @endif class="form-check-input">
                                                    <label class="size-text">{{$item}}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <h4 class="card-title size-text">Trọng lượng thanh toán</h4>
                                <p class="size-text">Trọng lượng tính cước</p>
                                <p>...........................................</p>
                                <p class="size-text">Cước phí:.........................</p>
                                <p class="size-text">Phí khác:..........................</p>
                                <p class="size-text">VAT:...................................</p>
                                <p class="size-text">Bảo hiểm:........................</p>
                                <p class="size-text">Tổng cộng:......................</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        @if($level == \App\User::LEVEL_ADMIN)
            <div class="card" style="margin-top: 70px;margin-bottom: 20px;">
                <div class="card-body">
                    <div class="row custom-row">
                        <div class="col-5 mt-4" >
                            <div class="card-body">
                                <img width="350" src="{{asset('image/logo_print.png')}}">
                            </div>
                        </div>
                        <div class="col-3 mt-4">
                            <div class="card-body">
                                <h2 class="card-title size-text">Hotline: <b>1900 633 656</b></h2>
                                <p class="card-text size-text">Website: www.ht-cargos.com</p>
                                <p class="card-text size-text">Email: info@ht-cargos.com</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card-body text-center">
                                <p class="size-text"><svg id="{{$order->order_code . \App\User::LEVEL_ADMIN}}"></svg></p>
                            </div>
                        </div>
                    </div>


                    <div class="row custom-row">
                        <div class="col custom-col">
                            <div class="card-body">
                                <h4 class="card-title">Họ tên, địa chỉ người gửi: </h4>
                                <p class="size-text" >{{isset($order->sender) ? $order->sender->sender_name : '.....'}}</p>
                                <p class="size-text" >{{isset($order->sender) ? (isset($order->sender->address) ? $order->sender->address . ', ' : '') . (isset($order->sender->ward) ? $order->sender->ward->ward_name . ', ' : '')  . (isset($order->sender->district) ? $order->sender->district->district_name . ', ' : '')  . (isset($order->sender->city) ? $order->sender->city->city_name : '') : '.....'}}</p>
                                <p class="card-text"><h4 class="card-title">Phòng ban:</h4> {{ $order->department }}</p>
                                <p class="size-text"><h4 class="card-title">Điện thoại:</h4> {{isset($order->sender) ? $order->sender->sender_phone : '.....'}}</p>
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
                                <p class="card-text size-text">{{isset($order->receiver) ? $order->receiver->receiver_name : '.....'}}</p>
                                <p class="card-text"><small class="text-muted size-text">{{isset($order->receiver) ? (isset($order->receiver->address) ? $order->receiver->address . ', ' : '') . (isset($order->receiver->ward) ? $order->receiver->ward->ward_name . ', ' : '')  . (isset($order->receiver->district) ? $order->receiver->district->district_name . ', ' : '')  . (isset($order->receiver->city) ? $order->receiver->city->city_name : '') : '.....'}} </small></p>

                                <p class="card-text"><h4 class="card-title">Điện thoại:</h4> {{isset($order->receiver) ? $order->receiver->receiver_phone : '.....'}}</p>
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
                                        <p class="size-text">{{$order->note}}</p>
                                    </div>
                                </div>
                                <p class="size-text" style="margin-top: 20px">Giá trị hàng hóa: {{$order->total}}</p>
                            </div>
                        </div>
                    </div>

                    <div class="row custom-row">
                        <div class="col custom-col">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-7">
                                        <h4 class="card-title">Ký xác nhận người gửi hàng: </h4>
                                        <p class="card-text size-text"><b>Ngày gửi:</b> {{$order->converDate($order->order_date)}} </p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="card-text size-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người gửi </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="card-text size-text">Dấu ngày gửi</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <h4 class="card-title">Thông tin giao nhận</h4>
                                        <p class="card-text size-text">Ngày gửi:..............</p>
                                        <p class="card-text size-text">Nv phát:..............</p>
                                        <p class="card-text size-text" style="margin-bottom: 100px">Ký ghi rõ họ tên người nhận</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-7">
                                        <p class="card-text size-text">NV-Chấp nhận:..............</p>
                                    </div>
                                    <div class="col-md-5">
                                        <p class="card-text size-text">Bộ phận:..............</p>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col">
                            <div class="card-body">

                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="row">
                                            <div class="col">
                                                <h4 class="card-title">Thông tin hàng hóa</h4>
                                                <div class="row" style="margin-bottom: 70px">
                                                    <div class="col-md-4 size-text">Số kiện</div>
                                                    <div class="col-md-8 size-text">Trọng lượng thức tế <p class="col-5 text-center"> {{($order->weight ? $order->weight : 0) . ' g'}}</p></div>

                                                </div>
                                                <p class="size-text" style="margin-left: 30px">Kích thước ({{($order->height ? $order->height : 0) . ' x '. ($order->long ? $order->long : 0) . ' x '. ($order->width ? $order->width : 0) .' cm'}})</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <h4 class="card-title" >Hình thức thanh toán</h4>
                                                <div class="row">
                                                    @foreach(\App\Models\Order::PAYMENT_METHOD_MAP as $key => $item )
                                                        <div class="col-md-4" style="margin-left: 20px">
                                                            <input type="checkbox" @if($order->payment_method == $key) checked @endif class="form-check-input">
                                                            <label class="size-text">{{$item}}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <h4 class="card-title size-text">Trọng lượng thanh toán</h4>
                                        <p class="size-text">Trọng lượng tính cước</p>
                                        <p>...........................................</p>
                                        <p class="size-text">Cước phí:.........................</p>
                                        <p class="size-text">Phí khác:..........................</p>
                                        <p class="size-text">VAT:...................................</p>
                                        <p class="size-text">Bảo hiểm:........................</p>
                                        <p class="size-text">Tổng cộng:......................</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
            </div>
    @endforeach
</div>
<script type="text/javascript">
    let orders = {!! json_encode($orders) !!};
    let level = {!! json_encode($level) !!};
    let levelAdmin = {!! json_encode(\App\User::LEVEL_ADMIN) !!};
    orders && orders.length > 0 && orders.forEach(order => {
        let idRender = '#'+ order.order_code;
        JsBarcode(idRender, order.order_code, {
            fontOptions: "bold",
            height: 90
        });
        if(level == levelAdmin) {
            let idRenderAdmin = '#'+ order.order_code + levelAdmin;
            JsBarcode(idRenderAdmin, order.order_code, {
                fontOptions: "bold",
                height: 90
            });
        }
    })
</script>
</body>
</html>
