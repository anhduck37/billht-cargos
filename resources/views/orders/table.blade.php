<div class="table-responsive mt-2">
    <div class="card-header border-0">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0">Quản lý vận đơn</h2>
            </div>
{{--            <div class="col text-right">--}}
{{--                <a class="btn btn-primary float-right"--}}
{{--                   href="{{ route('orders.create') }}">--}}
{{--                    Tạo vận đơn--}}
{{--                </a>--}}
{{--            </div>--}}
        </div>
    </div>
    <table class="table align-items-center">
        <thead style="background-color: #f6821f; color: white" class="thead-light">
        <tr>
            <td></td>
            <td>Ngày gửi</td>
            <td>Mã vận đơn</td>
            <td>Người gửi</td>
            <td>Người nhận</td>
{{--            <th>Phòng ban</th>--}}
            <!-- <th>Trạng thái vận đơn</th> -->
            <td>Trạng thái vận chuyển</td>
            <td>Phương thức thanh toán</td>
{{--            <th>Ngày vận chuyển</th>--}}
            <td>Đối tác vận chuyển</td>
            <!-- <th>Trọng lượng</th>
            <th>Chiều cao</th>
            <th>Chiều dài</th> -->
            @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
            <td>Tài khoản tạo đơn</td>
            @endif
            <td class="text-center" colspan="2">Hành động</td>
        </tr>
        </thead>
        <tbody>
        @foreach($orders as $key => $order)
            <tr>
                <th class="text-center"><input class="printOrder" data-service="{{implode(',',$order->serviceArray($order->id))}}" value="{{$order->id}}" type="checkbox" /></th>
                <td>{{$order->converDate($order->order_date)}}</td>
                <th scope="row">
                    <div class="media align-items-center">
                        <div class="media-body">
                            <span class="mb-0 text-sm">{{$order->order_code}}</span>
                        </div>
                    </div>
                </th>

                <td>
                    <div><label>Tên người gửi: <b>{{isset($order->sender) ? $order->sender->sender_name : ''}}</b> </label></div>
                    <div><label>Số điện thoại: <b>{{isset($order->sender) ? $order->sender->sender_phone : ''}}</b></label></div>
{{--                    <div><label>Email: <b>{{isset($order->sender) ? $order->sender->sender_email : ''}}</b></label></div>--}}
                    <div><label>Tỉnh / Thành phố: <b>{{isset($order->sender) && isset($order->sender->city) ? $order->sender->city->city_name : ''}}</b></label></div>
                    <!-- <div><label>Huyện / Quận: <b>{{isset($order->sender)&& isset($order->sender->district)  ? $order->sender->district->district_name : ''}}</b></label></div>
                    <div><label>Xã / Phường: <b>{{isset($order->sender) && isset($order->sender->ward) ? $order->sender->ward->ward_name : ''}}</b></label></div>
                    <div><label>Địa chỉ: <b>{{isset($order->sender) ? $order->sender->address : ''}}</b></label></div> -->
                </td>
                <td>
                    <div><label>Tên người gửi: <b>{{isset($order->receiver) ? $order->receiver->receiver_name : ''}}</b></label></div>
                    <div><label>Số điện thoại: <b>{{isset($order->receiver) ? $order->receiver->receiver_phone : ''}}</b></label></div>
{{--                    <div><label>Email: <b>{{isset($order->receiver) ? $order->receiver->receiver_email : ''}}</b></label></div>--}}
                    <div><label>Tỉnh / Thành phố: <b>{{isset($order->receiver) && isset($order->receiver->city) ? $order->receiver->city->city_name : ''}}</b></label></div>
                    <!-- <div><label>Huyện / Quận: <b>{{isset($order->receiver)&& isset($order->receiver->district)  ? $order->receiver->district->district_name : ''}}</b></label></div>
                    <div><label>Xã / Phường: <b>{{isset($order->receiver) && isset($order->receiver->ward) ? $order->receiver->ward->ward_name : ''}}</b></label></div>
                    <div><label>Địa chỉ: <b>{{isset($order->receiver) ? $order->receiver->address : ''}}</b></label></div> -->
                </td>
{{--                <td>{{$order->department}}</td>--}}
                <!-- <td>{{$order->order_status_name}}</td> -->
                <td>{{$order->order_delivery_name}}</td>
                <td>{{$order->payment_method_name}}</td>
{{--                <td>{{$order->delivery_date}}</td>--}}
                <td>{{isset($order->getPartner) ? $order->getPartner->name : ''}}</td>
                <!-- <td>{{$order->weight}}</td>
                <td>{{$order->height}}</td>
                <td>{{$order->width}}</td> -->
                @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                <td>{{isset($order->user) ? $order->user->email : ''}}</td>
                @endif
                <td class="text-center">
{{--                    <div class="dropdown">--}}
{{--                        <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">--}}
{{--                            <i class="fas fa-ellipsis-v"></i>--}}
{{--                        </a>--}}
{{--                        <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">--}}
                    <a href="{{ route('orders.edit', [$order->id]) }}"><i style="color: #0ca362;" class="fa fa-print"></i></a>

                            <a href="{{ route('orders.edit', [$order->id]) }}"><i style="color: blue;" class="far fa-edit"></i></a>
                    @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                            <a class="delete" data-id="{{$order->id}}"><i style="color: red;" class="far fa-trash-alt"></i>
                            </a>
                            {!! Form::open(['route' => ['orders.destroy', $order->id], 'method' => 'delete', 'class' => ['removeOrder'.$order->id],'style' => 'display: none']) !!}
                            {!! Form::close() !!}
                    @endif
{{--                        </div>--}}
{{--                    </div>--}}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@section('javascript')
    <script type="text/javascript">
        $(function() {
            $('.delete').on('click',function () {
                let orderId = $(this).attr('data-id');
                let isDelete = confirm('Bạn có chắc muốn xóa vận đơn này?');
                if(isDelete) {
                    $(`.removeOrder${orderId}`).submit()
                }
            })

            let dataPrint = [];
            $('.printOrder').on('change', function (e) {
                let orderId = e.target.value;
                if($(this).is(':checked')) {
                    dataPrint.push(orderId)
                }else {
                    let index = dataPrint.findIndex(item => item == orderId)
                    dataPrint.splice(index, 1);
                }
                console.log(dataPrint)
            })

            $('#print').on('click', function () {
                console.log('hay')
                console.log(dataPrint)
                $.ajax({
                    type: "POST",
                    url: '/api/template/render',
                    data: {'order': dataPrint},
                    success: function (res) {
                        console.log(res);
                        print(res)
                    },
                });
            });

        });
        function print(html) {
            var a = window.open();
            a.document.write(html);
            // a.load();
            a.document.close();
            // a.print();
        }
    </script>
@endsection

