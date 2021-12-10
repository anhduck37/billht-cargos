@extends('template.layout.email')
@section('title')
    Đơn hàng của bạn đã giao thành công!
@endsection
@section('table')
    <table class="table mt-4" style="width: 100%; border-spacing:0">
        <tr style="background-color: #f6821f">
            <td><p style="color: white">Thông tin giao hàng chi tiết</p></td>
            <td></td>
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
            <td style="background-color: #d9e0e7;">

            </td>
        </tr>
        <tr>
            <td style="background-color: #d9e0e7;">
                <p>Người kí nhận:</p>
                <label>
                    {{$order->signator}}
                </label>
            </td>
            <td style="background-color: #d9e0e7;">
                <p>Thời gian giao hàng:</p>
                <label>
                    {{$order->updated_at}}
                </label>
            </td>

        </tr>
    </table>
    <div class="text-center mt-3">
        <a href="{{route('tracking').'?order_code='.$order->order_code}}" class="btn btn-primary" role="button" aria-pressed="true">Theo dõi đơn hàng</a>
    </div>
@endsection
