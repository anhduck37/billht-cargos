<!-- Modern Card List Layout for Orders -->
<div class="orders-card-list mt-4">
    <div class="card-header border-0 mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="mb-0">Quản lý vận đơn</h2>
            </div>
        </div>
    </div>
    
    <!-- Desktop Table View -->
    <div class="d-none d-lg-block">
        <div class="table-responsive orders-table">
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
                    <td>Trạng thái</td>
                    <td>Ký nhận</td>
                    @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                    <td>Người tạo</td>
                    @endif
                    <td class="text-center">Hành động</td>
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
                                    <span class="mb-0 text-sm font-weight-bold">{{$order->order_code}}</span>
                                    @if($order->push_error)
                                    <span data-toggle="tooltip" data-placement="right" title="{{$order->push_error}}" style="color:#dc3545; cursor:pointer;">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    @endif
                                    @php
                                        $isMickeyOrder = $order->tracking_provider === \App\Models\Order::TRACKING_PROVIDER_MICKEY;
                                        $showPartnerBadge = auth()->user()->level != \App\User::LEVEL_USER && (($order->partner_code && $order->order_partner_code) || $isMickeyOrder);
                                        $partnerBadgeText = $isMickeyOrder ? 'Q-CPN' : (\App\Models\Order::MAP_CODE_PARTNER[$order->partner_code] ?? '');
                                        $partnerBadgeColor = $isMickeyOrder ? '#2563eb' : ($order->partner_code == \App\Models\Order::CODE_EMS ? '#0f766e' : '#f9731694');
                                    @endphp
                                    @if($showPartnerBadge)
                                    <div class="mt-1">
                                        <span class="badge" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; color: #ffffff; background-color: {{ $partnerBadgeColor }};">{{ $partnerBadgeText }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            </a>
                        </th>
                        <td style="max-width: 200px; font-size: 0.85rem;">
                            <div style="white-space: normal; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><label style="font-size: 0.8rem;">Tên: <b>{{isset($order->sender) ? $order->sender->sender_name : ''}}</b></label></div>
                            <div><label style="font-size: 0.8rem;">SĐT: <b>{{isset($order->sender) ? $order->sender->sender_phone : ''}}</b></label></div>
                            <div><label style="font-size: 0.8rem;">Tỉnh/TP: <b>{{isset($order->sender) ? $order->sender->city_name : ''}}</b></label></div>
                            @if($order->order_print)
                                <div class="mt-1"><span class="badge badge-success" data-toggle="tooltip" data-placement="right" title="Thời gian in: {{$order->order_print->created_at ? $order->order_print->created_at->format('d/m/Y H:i:s') : ''}}" style="font-size: 0.7rem; padding: 0.3rem 0.6rem;">Đã in</span></div>
                            @endif
                        </td>
                        <td style="max-width: 250px; font-size: 0.85rem;">
                            <div style="white-space: normal; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><label style="font-size: 0.8rem;">Tên: <b>{{isset($order->receiver) ? $order->receiver->receiver_name : ''}}</b></label></div>
                            <div><label style="font-size: 0.8rem;">SĐT: <b>{{isset($order->receiver) ? $order->receiver->receiver_phone : ''}}</b></label></div>
                            <div style="font-size: 0.75rem; white-space: normal; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <label>
                                    @if(isset($order->receiver))
                                    @if($order->receiver->address)
                                        <b>{{implode(', ', array_slice(explode(',', $order->receiver->address), 0, 2))}}</b>
                                    @endif
                                    @if(isset($order->receiver->ward) || $order->receiver->address_scheme === 'new')
                                    <b>{{ $order->receiver->ward_name }}</b>
                                    @endif
                                    @if(isset($order->receiver->district))
                                    <b>{{ $order->receiver->district_name }}</b>
                                    @endif
                                    @if(isset($order->receiver->city) || $order->receiver->address_scheme === 'new')
                                    <b>{{$order->receiver->city_name}}</b>
                                    @endif
                                    @endif
                                </label>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $order->delivery_status == \App\Models\Order::DELIVERY_STATUS_OK ? '' : 'badge-info' }}" 
                                  style="{{ $order->delivery_status == \App\Models\Order::DELIVERY_STATUS_OK ? 'color: #000000; background-color: rgb(246 130 31 / 66%);' : '' }}">
                                {{$order->order_delivery_name}}
                            </span>
                        </td>
                        <td>{{$order->signator}}</td>
                        @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                        <td style="font-size: 0.8rem;">{{isset($order->user) ? $order->user->email : ''}}</td>
                        @endif
                        <td class="text-center" style="min-width: 120px; white-space: nowrap;">
                            <a class="printIcon" data-toggle="modal" data-target="#openModalPrint" data-id="{{$order->id}}" title="In"><i style="color: #0ca362; font-size: 1.2rem; margin: 0 0.25rem;" class="fa fa-print"></i></a>
                            <a href="{{ route('orders.edit', [$order->id]) }}" title="Chỉnh sửa"><i style="color: #ff9a56; font-size: 1.2rem; margin: 0 0.25rem;" class="far fa-edit"></i></a>
                            @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]) || (auth()->user()->level == \App\User::LEVEL_USER && $order->user_id && in_array((int)$order->delivery_status, [\App\Models\Order::DELIVERY_STATUS_PROCESSING, \App\Models\Order::DELIVERY_STATUS_BLANK])) )
                                <a class="delete" data-id="{{$order->id}}" title="Xóa"><i style="color: #f5576c; font-size: 1.2rem; margin: 0 0.25rem;" class="far fa-trash-alt"></i></a>
                                {!! Form::open(['route' => ['orders.destroy', $order->id], 'method' => 'DELETE', 'class' => ['removeOrder'.$order->id],'style' => 'display: none']) !!}
                                @csrf
                                {!! Form::close() !!}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Mobile Card View -->
    <div class="d-lg-none orders-list">
        @foreach($orders as $key => $order)
        <div class="order-card">
            <div class="order-card-left-border"></div>
            <div class="order-card-content">
                <div class="order-card-header">
                    <div class="order-card-header-left">
                        <input class="printOrder" data-service="{{implode(',',$order->getService($order))}}" value="{{$order->id}}" type="checkbox" />
                        <span class="order-code">{{$order->order_code}}</span>
                        @if($order->push_error)
                        <span style="color:#dc3545; font-size: 0.8rem; margin-left: 4px;" title="{{$order->push_error}}">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                        @endif
                        @php
                            $isMickeyOrder = $order->tracking_provider === \App\Models\Order::TRACKING_PROVIDER_MICKEY;
                            $showPartnerBadge = auth()->user()->level != \App\User::LEVEL_USER && (($order->partner_code && $order->order_partner_code) || $isMickeyOrder);
                            $partnerBadgeText = $isMickeyOrder ? 'Q-CPN' : (\App\Models\Order::MAP_CODE_PARTNER[$order->partner_code] ?? '');
                            $partnerBadgeColor = $isMickeyOrder ? '#2563eb' : ($order->partner_code == \App\Models\Order::CODE_EMS ? '#57afa8db' : '#f97316');
                        @endphp
                        @if($showPartnerBadge)
                        <div class="mt-1">
                            <span class="badge" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; color: #ffffff; background-color: {{ $partnerBadgeColor }};">{{ $partnerBadgeText }}</span>
                        </div>
                        @endif
                    </div>
                    <div class="order-card-header-right">
                        <span class="order-status-badge" style="{{ $order->delivery_status == \App\Models\Order::DELIVERY_STATUS_OK ? 'color: #000000; background-color: rgb(246 130 31 / 66%);' : '' }}">{{$order->order_delivery_name}}</span>
                    </div>
                </div>
                
                <div class="order-card-body">
                    <div class="order-info-row">
                        <span class="order-info-label">Ngày gửi:</span>
                        <span class="order-info-value">{{$order->converDate($order->order_date)}}</span>
                    </div>
                    <div class="order-info-row">
                        <span class="order-info-label">Người gửi:</span>
                        <span class="order-info-value" style="white-space: normal; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{isset($order->sender) ? $order->sender->sender_name : ''}}</span>
                    </div>
                    <div class="order-info-row">
                        <span class="order-info-label">SĐT gửi:</span>
                        <span class="order-info-value">{{isset($order->sender) ? $order->sender->sender_phone : ''}}</span>
                    </div>
                    <div class="order-info-row">
                        <span class="order-info-label">Người nhận:</span>
                        <span class="order-info-value" style="white-space: normal; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">{{isset($order->receiver) ? $order->receiver->receiver_name : ''}}</span>
                    </div>
                    <div class="order-info-row">
                        <span class="order-info-label">SĐT nhận:</span>
                        <span class="order-info-value">{{isset($order->receiver) ? $order->receiver->receiver_phone : ''}}</span>
                    </div>
                    @if(isset($order->receiver) && $order->receiver->address)
                    <div class="order-info-row">
                        <span class="order-info-label">Địa chỉ:</span>
                        <span class="order-info-value" style="font-size: 0.85rem; white-space: normal; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            @if($order->receiver->address)
                                {{implode(', ', array_slice(explode(',', $order->receiver->address), 0, 2))}}
                            @endif
                            @if(isset($order->receiver->ward) || $order->receiver->address_scheme === 'new')
                            {{ $order->receiver->ward_name }}
                            @endif
                            @if(isset($order->receiver->district))
                            {{ $order->receiver->district_name }}
                            @endif
                            @if(isset($order->receiver->city) || $order->receiver->address_scheme === 'new')
                            {{$order->receiver->city_name}}
                            @endif
                        </span>
                    </div>
                    @endif
                    @if($order->signator)
                    <div class="order-info-row">
                        <span class="order-info-label">Ký nhận:</span>
                        <span class="order-info-value">{{$order->signator}}</span>
                    </div>
                    @endif
                    @if($order->order_print)
                    <div class="order-info-row">
                        <span class="badge badge-success" data-toggle="tooltip" data-placement="right" title="Thời gian in: {{$order->order_print->created_at ? $order->order_print->created_at->format('d/m/Y H:i:s') : ''}}" style="font-size: 0.75rem; padding: 0.3rem 0.6rem;">Đã in</span>
                    </div>
                    @endif
                </div>
                
                <div class="order-card-footer">
                    <a href="{{ route('orders.edit', [$order->id]) }}" class="btn-view">
                        <i class="far fa-eye"></i>
                        <span>Xem</span>
                    </a>
                    <a href="{{ route('orders.edit', [$order->id]) }}" class="btn-edit">
                        <i class="far fa-edit"></i>
                        <span>Sửa</span>
                    </a>
                    <a class="printIcon btn-view" data-toggle="modal" data-target="#openModalPrint" data-id="{{$order->id}}" style="background: #e8f5e9; color: #0ca362;">
                        <i class="fa fa-print"></i>
                        <span>In</span>
                    </a>
                    @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]) || (auth()->user()->level == \App\User::LEVEL_USER && $order->user_id && in_array((int)$order->delivery_status, [\App\Models\Order::DELIVERY_STATUS_PROCESSING, \App\Models\Order::DELIVERY_STATUS_BLANK])) )
                        <a class="delete btn-edit" data-id="{{$order->id}}" style="background: #ffebee; color: #f5576c;">
                            <i class="far fa-trash-alt"></i>
                            <span>Xóa</span>
                        </a>
                        {!! Form::open(['route' => ['orders.destroy', $order->id], 'method' => 'DELETE', 'class' => ['removeOrder'.$order->id],'style' => 'display: none']) !!}
                        @csrf
                        {!! Form::close() !!}
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@section('javascript')
    <script type="text/javascript" src="{{ asset('js/render-print.js') }}?v={{time()}}"></script>
    <script type="text/javascript">
        let paramFilters = {!! json_encode(request()->all()) !!};
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
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

            let dataPrint = [];
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
                    $('.printOrder').prop('checked', true)
                } else {
                    dataPrint = [];
                    $('.printOrder').prop('checked', false)
                }
            })
            $('#print').on('click', function () {
                let number = $('input[name="number"]:checked').val()
                let start_stt = $('input[name="start_stt"]').val()
                let end_stt = $('input[name="end_stt"]').val()
                $.ajax({
                    type: "POST",
                    url: '/template/render',
                    data: {'order': dataPrint, number: number, start: start_stt, end: end_stt},
                    success: function (res) {
                        let html = renderHtml(res)
                        print(html)
                    }
                });
            });

            $('.printIcon').on('click', function (e) {
                let orderId = $(this).attr('data-id');
                dataPrint = [orderId]
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
                    if(paramFilters.length == 0) paramFilters = {}
                    let url = {!! json_encode(route('orders.export')) !!};
                    let search = $("input[name='search'").val();
                    let order_code_from = $("input[name='order_code_from']").val();
                    let order_code_to = $("input[name='order_code_to']").val();
                    let delivery_status = $('select[name="delivery_status"]').val();
                    let partner_code = $('select[name="partner_code"]').val();
                    let order_date = $("#order_date").val();

                    url += '?'
                    if(url.includes('&')) {
                        url += '&'
                    }
                    if(search) url += `search=${search}&`
                    if(order_code_from && order_code_to) {
                        url += `order_code_from=${order_code_from}&order_code_to=${order_code_to}&`
                    }
                    if(delivery_status) url += `delivery_status=${delivery_status}&`
                    if(partner_code) url += `partner_code=${partner_code}&`

                    if(order_date) {
                        let splitOrderDate = order_date.split(' - ')
                        url += `start_date=${splitOrderDate[0]}&end_date=${splitOrderDate[1]}`
                    }
                    console.log('url', url)
                    $('#export').attr('href', url)
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
            $('#sendSMS').on('click', function () {
                $.ajax({
                    type: "POST",
                    url: '/order/send-sms',
                    data: {'order_ids': dataPrint},
                    beforeSend: function() {
                        $('#sendSMS').attr("disabled", true)
                        $('#sendSMS').html(`Gửi SMS <img width="20px" src="{{asset('/image/loading.jpg')}}" >`)
                    },
                    success: function (res) {
                        window.location.href = res;
                    },
                });
            })
            $('#createViettelPost').on('click', function () {
                if (!dataPrint.length) {
                    alert('Bạn vui lòng chọn vận đơn');
                    return;
                }
                if (!confirm('Đẩy các vận đơn đã chọn lên API Viettel?')) {
                    return;
                }
                $.ajax({
                    type: "POST",
                    url: '/order/create-viettel-post',
                    data: {'order_ids': dataPrint},
                    beforeSend: function() {
                        $('#syncApiDropdown').attr("disabled", true)
                        $('#createViettelPost').attr("disabled", true)
                        $('#createViettelPost').html(`Đang đẩy Viettel <img width="20px" src="{{asset('/image/loading.jpg')}}" >`)
                    },
                    success: function (res) {
                        window.location.href = res;
                    },
                });
            })
            $('#createEms').on('click', function () {
                if (!dataPrint.length) {
                    alert('Bạn vui lòng chọn vận đơn');
                    return;
                }
                if (!confirm('Đẩy các vận đơn đã chọn lên API EMS?')) {
                    return;
                }
                $.ajax({
                    type: "POST",
                    url: '/order/create-ems',
                    data: {'order_ids': dataPrint},
                    beforeSend: function() {
                        $('#syncApiDropdown').attr("disabled", true)
                        $('#createEms').attr("disabled", true)
                        $('#createEms').html(`Đang đẩy EMS <img width="20px" src="{{asset('/image/loading.jpg')}}" >`)
                    },
                    success: function (res) {
                        window.location.href = res;
                    },
                });
            })
            $('#resolveLegacyAddresses').on('click', function () {
                if (!dataPrint.length) {
                    alert('Bạn vui lòng chọn vận đơn');
                    return;
                }
                if (!confirm('Tự nhận diện và gán lại Tỉnh/Huyện/Xã cho các vận đơn đã chọn?')) {
                    return;
                }
                $.ajax({
                    type: "POST",
                    url: '/order/resolve-legacy-addresses',
                    data: {'order_ids': dataPrint},
                    beforeSend: function() {
                        $('#syncApiDropdown').attr("disabled", true)
                        $('#resolveLegacyAddresses').attr("disabled", true)
                        $('#resolveLegacyAddresses').html(`Đang gán địa chỉ <img width="20px" src="{{asset('/image/loading.jpg')}}" >`)
                    },
                    success: function (res) {
                        window.location.href = res;
                    },
                });
            })
            $('#sendZaloZNS').on('click', function () {
                $.ajax({
                    type: "POST",
                    url: '/order/send-zalo-zns',
                    data: {'order_ids': dataPrint},
                    beforeSend: function() {
                        $('#sendZaloZNS').attr("disabled", true)
                        $('#sendZaloZNS').html(`Gửi Zalo <img width="20px" src="{{asset('/image/loading.jpg')}}" >`)
                    },
                    success: function (res) {
                        window.location.href = res;
                    },
                });
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
