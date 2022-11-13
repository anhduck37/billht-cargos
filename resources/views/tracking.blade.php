@extends('layouts.app')
@section('content')

    @include('layouts.headers.cards')

    <div class="container-fluid mt--4">
        <div class="row mt-5">
            <div class="col-xl-12 mb-5 mb-xl-0">
                @include('flash::message')
                <div class="card shadow">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h2 class="mb-0">Tra cứu đơn hàng</h2>
                            </div>
                        </div>
                    </div>
                    {!! Form::open(['method' => 'GET']) !!}

                    <div class="card-body mt-3">
                        <div class="row">
                            <div class="form-group col-md-5">
                                <label>Vui lòng nhập mã vận đơn, ví dụ: HE000001</label>
                                <!-- {!! Form::label( 'Vui lòng nhập mã vận đơn. Ví dụ: HE000001' ) !!} -->
                                {!! Form::text('order_code', request('order_code', ''), ['class' => 'form-control', 'id' => 'order_code']) !!}
                            </div>
                            <div class="form-group col-md-3">
                                {!! Form::submit('Tìm kiếm', ['class' => 'btn btn-primary', 'style' => 'margin-bottom: -83px;']) !!}
                            </div>
                        </div>
                    </div>
                    {!! Form::close() !!}
                    <div class="card-body text-center">
                        <div class = "container">
                            <div class="centerwrapper">
                                <table class="text-center p-2">
                                    <tr>
                                        <td>
                                            <i class="fas fa-clipboard-list custom-icon-1 @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_PROCESSING, \App\Models\Order::DELIVERY_STATUS_RETURN, \App\Models\Order::DELIVERY_STATUS_OK, \App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE]) ) icon-select @endif margin-custom fa-2x"></i>
                                        </td>
                                        <td><i @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_RETURN, \App\Models\Order::DELIVERY_STATUS_OK, \App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE])) style="color: #f6821f" @endif class="fas fa-long-arrow-alt-right margin-custom fa-2x"></i></td>
                                        <td><i class="fas fa-dolly-flatbed custom-icon @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_RETURN, \App\Models\Order::DELIVERY_STATUS_OK, \App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE]) ) icon-select @endif margin-custom fa-2x"></i></td>
                                        <td><i @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_OK, \App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE])) style="color: #f6821f" @endif class="fas fa-long-arrow-alt-right margin-custom fa-2x"></i></td>
                                        <td><i class="fas fa-shipping-fast custom-icon @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_OK, \App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE]) ) icon-select @endif margin-custom fa-2x"></i></td>
                                        <td><i @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_OK])) style="color: #f6821f" @endif class="fas fa-long-arrow-alt-right margin-custom fa-2x"></i></td>
                                        <td><i class="fas fa-people-carry custom-icon @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_OK]) ) icon-select @endif margin-custom fa-2x"></i></td>
                                    </tr>
                                    <tr>
                                        <td @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_PROCESSING, \App\Models\Order::DELIVERY_STATUS_RETURN, \App\Models\Order::DELIVERY_STATUS_OK, \App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE])) style="color: #f6821f" @endif >
                                            <label class="mt-1">{{array_key_exists(\App\Models\Order::DELIVERY_STATUS_PROCESSING , \App\Models\Order::DELIVERY_MAP) ? \App\Models\Order::DELIVERY_MAP[\App\Models\Order::DELIVERY_STATUS_PROCESSING] : ''}}</label>
                                        </td>
                                        <td></td>
                                        <td @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_RETURN, \App\Models\Order::DELIVERY_STATUS_OK, \App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE])) style="color: #f6821f" @endif>
                                            <label class="mt-1">{{array_key_exists(\App\Models\Order::DELIVERY_STATUS_RETURN , \App\Models\Order::DELIVERY_MAP) ? \App\Models\Order::DELIVERY_MAP[\App\Models\Order::DELIVERY_STATUS_RETURN] : ''}}</label>
                                        </td>
                                        <td></td>
                                        <td @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_OK, \App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE])) style="color: #f6821f" @endif>
                                            <label class="mt-1">{{array_key_exists(\App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE , \App\Models\Order::DELIVERY_MAP) ? \App\Models\Order::DELIVERY_MAP[\App\Models\Order::DELIVERY_STATUS_PERSON_CHARGE] : ''}}</label>
                                        </td>
                                        <td></td>
                                        <td @if(in_array($delivery_status, [\App\Models\Order::DELIVERY_STATUS_OK])) style="color: #f6821f" @endif>
                                            <label class="mt-1">{{array_key_exists(\App\Models\Order::DELIVERY_STATUS_OK , \App\Models\Order::DELIVERY_MAP) ? \App\Models\Order::DELIVERY_MAP[\App\Models\Order::DELIVERY_STATUS_OK] : ''}}</label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                    </div>

                    <div class="table-responsive mt-4">
                        <table class="table align-items-center">
                            <thead style="background-color: #f6821f; color: white" class="thead-light">
                            <tr>
                                <td>Mã vận đơn</td>
                                {{--  <td>Mã vận đơn</td>  --}}
                                <td>Trạng thái vận đơn</td>
                                <td>Người gửi</td>
                                <td>Người nhận</td>
                                <td>Địa chỉ</td>
                                <td>Người ký nhận</td>
                                <!-- <td>Thời gian cập nhật</td> -->
                                <td>Nội dung</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($order_trackings as $item)
                                <tr>
                                    <th scope="row">
                                        <div class="media align-items-center">
                                            <div class="media-body">
                                                <span class="mb-0 text-sm">{{$order->order_code}}</span>
                                            </div>
                                        </div>
                                    </th>
                                    {{--  <th scope="row">
                                        <div class="media align-items-center">
                                            <div class="media-body">
                                                <span class="mb-0 text-sm">{{$item->invoice_code}}</span>
                                            </div>
                                        </div>
                                    </th>  --}}
                                    <td>{{$item->getDeliveryStatusName($item->delivery_status)}}</td>
                                    <td>{{isset($item->order) && isset($item->order->sender) ? $item->order->sender->sender_name : ''}}</td>
                                    <td>{{isset($item->order) && isset($item->order->receiver) ? $item->order->receiver->receiver_name : ''}}</td>
                                    <td>{{isset($item->order->receiver) && isset($item->order->receiver) ? $item->order->receiver->address : ''}}</td>
                                    <td>{{$item->signator}}</td>
                                    <!-- <td>{{$item->updated_at}}</td> -->
                                    <td>{{isset($item->order) ? $item->order->note : ''}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <div class="text-center mt-4">
                            @if (isset($order->image))
                                <img style="max-width: 400px; {{$order->image->type_upload == \App\OrderImage::TYPE_IMAGE_WEBCAM ? 'transform: rotate(270deg);' : ''}}" src="{{asset('uploads/'.$order->image->image)}}" />
                            @endif
                        </div>
                    </div>
                </div>
{{--                <div class="align-content-center" style="margin-top: 20px">--}}
{{--                    {!! $users->links() !!}--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script type="text/javascript">

        $(function() {
            $('#order_code').on('change', function() {
                $('#order_code').val(this.value.toUpperCase())
            });
        })
    </script>
@endsection
