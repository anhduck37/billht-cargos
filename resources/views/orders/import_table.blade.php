<div class="table-responsive mt-2">
    <div class="card-header border-0">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0">Vận đơn đã import</h2>
            </div>
        </div>
    </div>
    <table class="table align-items-center">
        <thead style="background-color: #f6821f; color: white" class="thead-light">
        <tr>
            <td>STT</td>
            <td>Ngày gửi</td>
            <td>Mã vận đơn</td>
            <td>Người gửi</td>
            <td>Người nhận</td>
            @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
            <td>Người phụ trách</td>
            @endif
            <td>Trạng thái</td>
            <td>Ký nhận</td>
            @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
            <td>Người tạo</td>
            @endif
        </tr>
        </thead>
        <tbody>

        @foreach($orders as $key => $order)
            <tr>
                <th>{{ $key + 1 }}</th>
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
                </td>
                <td>
                    <div><label>Tên người nhận: <b>{{isset($order->receiver) ? $order->receiver->receiver_name : ''}}</b></label></div>
                    <div><label>Số điện thoại: <b>{{isset($order->receiver) ? $order->receiver->receiver_phone : ''}}</b></label></div>
                    <div>
                        <label>
                            @if(isset($order->receiver))
                            Địa chỉ: @if($order->receiver->address ) @foreach(explode(',', $order->receiver->address) as $item) <b>{{$item.','}}</b><br> @endforeach @endif
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
                @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                <td>{{isset($order->getPersonCharge) ? $order->getPersonCharge->name : ''}}</td>
                @endif
                <td>{{$order->order_delivery_name}}</td>
                <td>{{$order->signator}}</td>
                @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                <td>{{isset($order->user) ? $order->user->email : ''}}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@section('javascript')
    <script type="text/javascript" src="{{ asset('js/render-print.js') }}"></script>
    <script type="text/javascript">
        $(function() {
            let orders = {!! json_encode($orders) !!};
            let dataPrint = orders.map(item => item.id);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $('.delete').on('click',function () {
                let orderId = $(this).attr('data-id');
                let isDelete = confirm('Bạn có chắc muốn xóa vận đơn này?');
                if(isDelete) {
                    $(`.removeOrder${orderId}`).submit()
                }
            })

            $('.printOrder').on('change', function (e) {
                let orderId = e.target.value;
                if($(this).is(':checked')) {
                    dataPrint.push(orderId)
                }else {
                    let index = dataPrint.findIndex(item => item == orderId)
                    dataPrint.splice(index, 1);
                }
            })
            $('#checkedAll').on('change', function (e) {
                if($(this).is(':checked')) {
                    let data = {!! json_encode($orders) !!}
                    let orders = data.data;
                    dataPrint = orders.map(order => {
                        return order.id
                    })
                    $('.printOrder').attr('checked', true)
                } else {
                    dataPrint = [];
                    $('.printOrder').attr('checked', false)
                }
            })
            $('#print').on('click', function () {
                let number = $('input[name="number"]:checked').val()
                $.ajax({
                    type: "POST",
                    url: '/template/render',
                    data: {'order': dataPrint, number: number},
                    success: function (res) {
                        let html = renderHtml(res)
                        print(html)
                    }
                });
            });

            $('.printIcon').on('click', function (e) {
                let orderId = $(this).attr('data-id');
                dataPrint = [orderId]
                // $.ajax({
                //     type: "POST",
                //     url: '/template/render',
                //     data: {'order': [orderId]},
                //     success: function (res) {
                //         print(res)
                //     },
                // });
            })

            $('#deleteMany').on('click', function () {
                let isDelete = confirm('Bạn có chắc muốn xóa vận đơn này?');
                if(isDelete) {
                    $.ajax({
                        type: "POST",
                        url: '/order/delete-many',
                        data: {'order_ids': dataPrint},
                        success: function (res) {
                            window.location.href = res;
                        },
                    });
                }
            })
            $('#export').on('click', function () {
                    $.ajax({
                        type: "GET",
                        url: '/order/export',
                        data: {'order_ids': dataPrint},
                    }).done((res) => {
                        var bin = atob(res);
                        var ab = s2ab(bin); // from example above
                        let blob = new Blob([res], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;' })
                        var link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = 'demo.xlsx';

                        document.body.appendChild(link);

                        link.click();

                        document.body.removeChild(link);
                    });
            })
            $('#sendEmail').on('click', function () {
                let typeEmail = $('input[name="type_email"]:checked').val()
                if(typeEmail) {
                    $('.isShow').css('display', 'none')
                    $('#isLoading').css('display', '')
                    $.ajax({
                        type: "POST",
                        url: '/order/send-email',
                        data: {'order_ids': dataPrint, type_email: typeEmail},
                        success: function (res) {
                            window.location.href = res;
                        },
                    });
                }
            })
            $('#updateMany').on('click', function () {
                let isUpdate = confirm('Bạn có chắc muốn cập nhật vận đơn này?');
                if(isUpdate) {
                    let deliveryStatus = $('select[name="delivery_status"]').val();
                    $.ajax({
                        type: "POST",
                        url: '/order/update-many',
                        data: {'order_ids': dataPrint, 'delivery_status': deliveryStatus},
                        success: function (res) {
                            window.location.href = res;
                        }
                    });
                }

            })

        });
        function print(html) {
            let printDocument = window.open();
            printDocument.document.write(html);
            printDocument.document.close();
        }
    </script>
@endsection

