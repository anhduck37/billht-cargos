@extends('template.layout.email')
@section('title')
    Đơn hàng của bạn đã được xác nhận!
@endsection
@section('table')
    <div>
    <table class="sm-w-full" style="width: 100%">
        <tr style="background-color: #f6821f">
            <td><p style="color: white">Thông tin giao hàng chi tiết</p></td>
        </tr>
        <tr>
            <td style="background-color: #d9e0e7;">
                <p>Địa chỉ giao hàng:</p>
                <label>
                    @if(isset($order->receiver))
                        @if($order->receiver->address ) @foreach(explode(',', $order->receiver->address) as $item) {{$item.','}} @endforeach @endif
                        @if(isset($order->receiver->ward))
                            {{ $order->receiver->ward->ward_name.',' }}
                        @endif
                        @if(isset($order->receiver->district))
                            {{ $order->receiver->district->district_name.',' }}
                        @endif
                        @if(isset($order->receiver->city))
                            {{$order->receiver->city->city_name}}
                        @endif
                    @endif
                </label>
            </td>
        </tr>
    </table>
    </div>
    <div class="text-center" style="margin-top: 10px">
        <a href="{{route('tracking').'?order_code='.$order->order_code}}" style="color: white;" class="btn btn-primary" role="button" aria-pressed="true">Theo dõi đơn hàng</a>
    </div>
@endsection
