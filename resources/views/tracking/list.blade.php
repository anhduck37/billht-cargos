<div class="table-responsive mt-2">
    <table class="table align-items-center">
        <thead style="background-color: #f6821f; color: white" class="thead-light">
        <tr>
            <td class="text-center">
                <input id="checkedAll" type="checkbox" />
            </td>
            <td>STT</td>
            <td>Ngày gửi</td>
            <td>Mã vận đơn</td>
            <td>Người gửi</td>
            <td>Người nhận</td>
            <td class="text-center" colspan="2">Chi tiết</td>
        </tr>
        </thead>
        <tbody>

        @foreach($orders as $key => $order)
            <tr>
                <th class="text-center"><input class="printOrder" data-service="{{implode(',',$order->getService($order))}}" value="{{$order->id}}" type="checkbox" /></th>
                <th>{{ ((int)$orders->perPage() * ($orders->currentPage() - 1)) + ($key + 1)}}</th>
                <td>{{$order->converDate($order->order_date)}}</td>
                <th scope="row">
                    <a href="{{ route('orders.edit', [$order->id]) }}">
                    <div class="media align-items-center">
                        <div class="media-body">
                            <span class="mb-0 text-sm">{{$order->order_code}}</span>
                        </div>
                    </div>
                    </a>
                </th>
                <td>
                    <div><label>Tên người gửi: <b>{{isset($order->sender) ? $order->sender->sender_name : ''}}</b> </label></div>
                    <div><label>Số điện thoại: <b>{{isset($order->sender) ? $order->sender->sender_phone : ''}}</b></label></div>
                    <div><label>Tỉnh / Thành phố: </label> <br> <b>{{isset($order->sender) && isset($order->sender->city) ? $order->sender->city->city_name : ''}}</b></div>
                    <!-- <div><label>Huyện / Quận: <b>{{isset($order->sender)&& isset($order->sender->district)  ? $order->sender->district->district_name : ''}}</b></label></div>
                    <div><label>Xã / Phường: <b>{{isset($order->sender) && isset($order->sender->ward) ? $order->sender->ward->ward_name : ''}}</b></label></div>
                    <div><label>Địa chỉ: <b>{{isset($order->sender) ? $order->sender->address : ''}}</b></label></div> -->
                    <div><b>{{\App\Models\Order::MAP_CODE_PARTNER[$order->partner_code] ?? ''}}</b></div>
                </td>
                <td style="max-width: 450px">
                    <div><label>Tên người nhận: <b>{{isset($order->receiver) ? $order->receiver->receiver_name : ''}}</b></label></div>
                    <div><label>Số điện thoại: <b>{{isset($order->receiver) ? $order->receiver->receiver_phone : ''}}</b></label></div>
                    <div>
                        <label>
                            @if(isset($order->receiver))
                            Địa chỉ: <span style="white-space: pre-line">@if($order->receiver->address ) @foreach(explode(',', $order->receiver->address) as $item) <b>{{$item.','}}</b><br> @endforeach @endif</span>
                            @if(isset($order->receiver->ward))
                            <b>{{ $order->receiver->ward->ward_name.',' }}</b><br>
                            @endif
                            @if(isset($order->receiver->district))
                            <b>{{ $order->receiver->district->district_name.',' }}</b><br>
                            @endif
                            @if(isset($order->receiver->city))
                            <b>{{$order->receiver->city->city_name}}</b><br>
                            @endif
                            @endif
                        </label></div>
                </td>
                <td class="text-center" style="min-width: 110px">
                    <a href="{{ route('tracking', ['order_code' => $order->order_code]) }}">Chi tiết</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="align-content-center" style="margin-top: 5px; margin-bottom: 10px">
    {!! $orders->appends(request()->query())->links() !!}
</div>

<style>
    .table td, .table th {
        white-space: normal !important;
    }
</style>