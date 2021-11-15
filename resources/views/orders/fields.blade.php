<div class="row">
    <div class="col-sm-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Thông tin người gửi</h3>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tên cá nhân/ Công ty</label>
                        <input type="text" name="sender[sender_name]" value="{{old('sender.sender_name') ? old('sender.sender_name') : (isset($order->sender) ? $order->sender->sender_name : '') }}" class="form-control" />
                        @if ($errors->has('sender.sender_name'))
                            <span class="invalid-feedback" style="display: block;" role="alert">
                                <strong>{{ $errors->first('sender.sender_name') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                        <label>Số điện thoại</label>
                        <input type="number" name="sender[sender_phone]" value="{{old('sender.sender_phone') ? old('sender.sender_phone') : (isset($order->sender) ? $order->sender->sender_phone : '') }}" class="form-control" />
                        @if ($errors->has('sender.sender_phone'))
                            <span class="invalid-feedback" style="display: block;" role="alert">
                                <strong>{{ $errors->first('sender.sender_phone') }}</strong>
                            </span>
                        @endif
                    </div>

                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Tỉnh / Thành phố</label>
                        <select name="sender[city_id]" id="sender_city" class="form-control">
                            <option value="2"></option>
                            @foreach($citys as $city)
                                <option value="{{$city->id}}" @if(isset($order->sender) && $order->sender->city_id == $city->id) selected @endif >{{$city->city_name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                            <label>Huyện / Quận</label>
                            <select name="sender[district_id]" id="sender_district" class="form-control">
                                <option value="1"></option>
                            </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Xã / Phường</label>
                        <select name="sender[ward_id]" id="sender_ward" class="form-control">
                            <option value=""></option>
                        </select>
                    </div>
                </div>


                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Địa chỉ</label>
                        <input type="text" name="sender[address]" value="{{old('sender.sender_email') ? old('sender.sender_email') : (isset($order->sender) ? $order->sender->address : '') }}" class="form-control" />
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input type="text" name="sender[sender_email]" value="{{old('sender.sender_email') ? old('sender.sender_email') : (isset($order->sender) ? $order->sender->sender_email : '') }}"  class="form-control" />
                    </div>
                </div>
{{--                <div class="form-group">--}}
{{--                    <label>Phòng ban</label>--}}
{{--                    <input type="text" name="sender[department]" class="form-control" />--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Thông tin người nhận </h3>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tên cá nhân/ Công ty</label>
                        <input type="text" name="receiver[receiver_name]" value="{{old('receiver.receiver_name') ? old('receiver.receiver_name') : (isset($order->receiver) ? $order->receiver->receiver_name : '')}}" class="form-control" />
                        @if ($errors->has('receiver.receiver_name'))
                            <span class="invalid-feedback" style="display: block;" role="alert">
                                <strong>{{ $errors->first('receiver.receiver_name') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                        <label>Số điện thoại</label>
                        <input type="number" name="receiver[receiver_phone]" value="{{old('receiver.receiver_phone') ? old('receiver.receiver_phone') : (isset($order->receiver) ? $order->receiver->receiver_phone : '')}}" class="form-control" />
                        @if ($errors->has('receiver.receiver_phone'))
                            <span class="invalid-feedback" style="display: block;" role="alert">
                                <strong>{{ $errors->first('receiver.receiver_phone') }}</strong>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Tỉnh / Thành phố </label>
                        <select name="receiver[city_id]" id="receiver_city" class="form-control">
                            <option value=""></option>
                            @foreach($citys as $city)
                                <option value="{{$city->id}}" @if(isset($order->receiver) && $order->receiver->city_id == $city->id) selected @endif>{{$city->city_name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Huyện / Quận</label>
                        <select name="receiver[district_id]" id="receiver_district" class="form-control">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Xã / Phường</label>
                        <select name="receiver[ward_id]" id="receiver_ward" class="form-control">
                            <option value=""></option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Địa chỉ</label>
                        <input type="text" name="receiver[address]" value="{{old('receiver.address') ? old('receiver.address') : (isset($order->receiver) ? $order->receiver->address : '')}}" class="form-control" />
                        @if ($errors->has('receiver.address'))
                            <span class="invalid-feedback" style="display: block;" role="alert">
                                <strong>{{ $errors->first('receiver.address') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email</label>
                        <input type="text" name="receiver[receiver_email]" value="{{old('receiver.receiver_email') ? old('receiver.receiver_email') : (isset($order->receiver) ? $order->receiver->receiver_email : '')}}" class="form-control" />
                    </div>
                    <div class="form-group col-md-4">
                        <label>Phòng ban</label>
                        <input type="text" class="form-control" value="{{old('order.department') ? old('order.department') : $order->department }}" name="order[department]">
                    </div>
                </div>
{{--                <div class="form-group">--}}
{{--                    <label>Phòng ban</label>--}}
{{--                    <input type="text" name="receiver[department]" class="form-control" />--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
</div>
<div class="card mt-4">
    <div class="card-body">
        <h3 class="card-title">Thông tin vận đơn</h3>
        <div class="form-group">
            <div class="form-row">
                @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-3 @else col-md-4 @endif mb-3">
                    <label>Mã vận đơn bên thứ 3</label>
                    <input type="text" class="form-control" value="{{old('order.invoice_code') ? old('order.invoice_code') : $order->invoice_code }}" name="order[invoice_code]">
                </div>
                @endif
                <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-3 @else col-md-4 @endif mb-3">
                    <label>Đối tác vận chuyển</label>
                    <select name="order[partner]" class="form-control">
                        <option value=""></option>
                        @foreach($partners as $partner)
                            <option value="{{$partner->id}}" @if($order->partner == $partner->id) selected @endif>{{$partner->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-3 @else col-md-4 @endif mb-3">
                    <label> Ngày gửi </label>
                    <input type="text" class="form-control" name="order[order_date]" value="{{old('order.order_date') ? old('order.order_date') : (isset($order->order_date) ? $order->converDate($order->order_date) : '') }}" id="order_date">
                </div>
                    <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-3 @else col-md-4 @endif mb-3">
                        <label for="validationDefault01"> Phương thức thanh toán </label>
                        <select name="order[payment_method]" class="form-control">
                            <option value="{{\App\Models\Order::PAYMENT_METHOD_LAST}}" @if(!isset($order->payment_method)) selected @endif></option>
                            @foreach(\App\Models\Order::PAYMENT_METHOD_MAP as $key => $payment_method)
                                <option value="{{$key}}" @if($order->payment_method == $key) selected @endif >{{$payment_method}}</option>
                            @endforeach
                        </select>
                    </div>
{{--                <div class="col-md-4 mb-3">--}}
{{--                    <label>Ngày vận chuyển</label>--}}
{{--                    <input type="text" class="form-control" name="order[delivery_date]" id="delivery_date">--}}
{{--                </div>--}}
            </div>
        </div>
        <div class="form-group">
            <div class="form-row">
                <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-3 @else col-md-4 @endif mb-3">
                    <label> Người phụ trách </label>
                    <select name="order[person_charge]" id="receiver_city" class="form-control">
                        <option value=""></option>
                        @foreach($users as $user)
                            <option value="{{$user->id}}" @if(isset($order->person_charge) && $order->person_charge == $user->id) selected @endif>{{$user->name}}</option>
                        @endforeach
                    </select>
                </div>
{{--                <div class="col-md-4 mb-3">--}}
{{--                    <label for="validationDefault02">Tình trạng vận đơn</label>--}}
{{--                    <select name="order[order_status]" class="form-control">--}}
{{--                        <option value="0" selected></option>--}}
{{--                        @foreach(\App\Models\Order::MAP_ORDER_STATUS as $key => $order_status)--}}
{{--                            <option value="{{$key}}" @if($order->order_status == $key) selected @endif >{{$order_status}}</option>--}}
{{--                        @endforeach--}}
{{--                    </select>--}}
{{--                </div>--}}
                <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-3 @else col-md-4 @endif mb-3">
                    <label for="validationDefault01">Tình trạng vận chuyển</label>
                    <select name="order[delivery_status]" class="form-control">
                        <option value="{{\App\Models\Order::DELIVERY_STATUS_PROCESSING}}" @if(!isset($order->delivery_status)) selected @endif></option>
                        @foreach(\App\Models\Order::DELIVERY_MAP as $key => $delivery)
                            <option value="{{$key}}" @if($order->delivery_status == $key) selected @endif >{{$delivery}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-3 @else col-md-4 @endif mb-3">
                    <label>Tỉnh / Thành phố </label>
                    <select name="order[location_id]" class="form-control">
                        <option value=""></option>
                        @foreach($citys as $city)
                            <option value="{{$city->id}}" @if(isset($order->location_id) && $order->location_id == $city->id) selected @endif>{{$city->city_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-3 @else col-md-4 @endif mb-3">
                    <label>Người ký nhận</label>
                    <input type="text" class="form-control" value="{{old('order.signator') ? old('order.signator') : $order->signator }}" name="order[signator]">
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="form-row">
                <div class="col-md-3 mb-3">
                    <label for="validationDefault01">Trọng lượng</label>
                    <input type="text" class="form-control" name="order[weight]" placeholder="Trọng lượng" value="{{old('order.weight') ? old('order.weight') :$order->weight}}">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="validationDefault02">Chiều cao</label>
                    <input type="text" class="form-control" value="{{old('order.height') ? old('order.height') : $order->height}}" name="order[height]" placeholder="Chiều cao">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="validationDefault01">Chiều dài</label>
                    <input type="text" class="form-control" name="order[width]" value="{{old('order.width') ? old('order.width') :$order->width}}" placeholder="Chiều dài">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="validationDefault01">Giá trị vận đơn</label>
                    <input type="number" class="form-control" name="order[total]" value="{{old('order.total') ? old('order.total') : ( isset($order->total) ? $order->total : 0)}}" placeholder="Giá tị vận đơn">
                </div>
            </div>
        </div>
        <div class="form-group">
            <div class="form-row">
                @foreach(\App\Service::SERVICE_MAP as $type => $service)
                <div class="col-md-4 mb-3">
                    <h4>{{$service['name']}}</h4>
                    @foreach($service['value'] as $key => $item)
                    <div class="form-check">
                        <input name="order_service[{{$type}}][]" value="{{$key}}" @if(in_array($key, $order->serviceArray($order->id))) checked @endif type="checkbox" class="form-check-input">
                        <label class="form-check-label">{{$item}}</label>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        <div class="form-group">
            <label>Ghi chú</label>
            <textarea name="order[note]" class="form-control" rows="3">{{old('order.note') ? old('order.note') : $order->note}}</textarea>
        </div>
    </div>
</div>
@section('javascript')
    <script type="text/javascript">
        $(function() {
            @if(isset($update))
                let order = {!! json_encode($order) !!}
                let cityUpdateSender = order && order.sender && order.sender.city_id;
                let districtUpdateSender = order && order.sender && order.sender.district_id;
                let wardUpdateSender = order && order.sender && order.sender.ward_id;
                renderSelect('sender_district', cityUpdateSender, 'district', districtUpdateSender)
                renderSelect('sender_ward', districtUpdateSender, 'ward', wardUpdateSender)
                let cityUpdateReceiver = order && order.receiver && order.receiver.city_id;
                let districtUpdateReceiver = order && order.receiver && order.receiver.district_id;
                let wardUpdateReceiver = order && order.receiver && order.receiver.ward_id;

                renderSelect('receiver_district', cityUpdateReceiver, 'district', districtUpdateReceiver)
                renderSelect('receiver_ward', districtUpdateReceiver, 'ward', wardUpdateReceiver)
            @endif

            $('#sender_city').on('change', function (e) {
                let city_id = e.target.value;
                $('#sender_ward').html('<option value=""></option>')
                renderSelect('sender_district', city_id, 'district')
            })
            $('#sender_district').on('change', function (e) {
                let district_id = e.target.value;
                renderSelect('sender_ward', district_id, 'ward')
            })
            $('#receiver_city').on('change', function (e) {
                let city_id = e.target.value;
                $('#receiver_ward').html('<option value=""></option>')
                renderSelect('receiver_district', city_id, 'district')
            })
            $('#receiver_district').on('change', function (e) {
                let district_id = e.target.value;
                renderSelect('receiver_ward', district_id, 'ward')
            })
            $(function() {
                $('#order_date').daterangepicker({
                    singleDatePicker: true,
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: 'Clear'
                    }
                }, function(start, end, label) {
                    var years = moment().diff(start, 'years');
                });
            });
            $('#order_date').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY'));
            });
            $('#order_date').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });
            $(function() {
                $('#delivery_date').daterangepicker({
                    singleDatePicker: true,
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: 'Clear'
                    }
                }, function(start, end, label) {
                    var years = moment().diff(start, 'years');
                });
            });
            $('#delivery_date').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY'));
            });
            $('#delivery_date').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

        });

        function renderSelect(name, id, type, idUpdate = null) {
            let url =`/api/${type}/${id}`;
            $.get(url, function (res) {
                let html = `
                <option value=''></option>
            `;
                res.forEach(item => {
                    html += `<option value="${item.id}" ${idUpdate == item.id ? 'selected' : ''}>${item[`${type}_name`]}</option>`
                })
                $(`#${name}`).html(html)
            })
        }

    </script>
@endsection
