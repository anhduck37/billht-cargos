<div class="row">
    <div class="col-sm-6">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Thông tin người gửi</h3>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Tên cá nhân/ Công ty</label>
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" name="sender[sender_name]" value="{{old('sender.sender_name') ? old('sender.sender_name') : (isset($order->sender) ? $order->sender->sender_name : '') }}" class="form-control" />
                        @if ($errors->has('sender.sender_name'))
                            <span class="invalid-feedback" style="display: block;" role="alert">
                                <strong>{{ $errors->first('sender.sender_name') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                        <label>Số điện thoại</label>
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="number" name="sender[sender_phone]" value="{{old('sender.sender_phone') ? old('sender.sender_phone') : (isset($order->sender) ? $order->sender->sender_phone : '') }}" class="form-control" />
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
                        <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="sender[city_id]" id="sender_city" class="form-control">
                            <option value=""></option>
                            @foreach($citys as $city)
                                <option value="{{$city->id}}" @if(isset($order->sender) && $order->sender->city_id == $city->id) selected @endif >{{$city->city_name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                            <label>Huyện / Quận</label>
                            <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="sender[district_id]" id="sender_district" class="form-control">
                                <option value=""></option>
                            </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Xã / Phường</label>
                        <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="sender[ward_id]" id="sender_ward" class="form-control">
                            <option value=""></option>
                        </select>
                    </div>
                </div>


                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Địa chỉ</label>
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" name="sender[address]" value="{{old('sender.sender_email') ? old('sender.sender_email') : (isset($order->sender) ? $order->sender->address : '') }}" class="form-control" />
                    </div>
                    <div class="form-group col-md-4">
                        <label>Email</label>
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" name="sender[sender_email]" value="{{old('sender.sender_email') ? old('sender.sender_email') : (isset($order->sender) ? $order->sender->sender_email : '') }}"  class="form-control" />
                    </div>
                    <div class="form-group col-md-4">
                        <label>Phòng ban</label>
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" class="form-control" value="{{old('order.department') ? old('order.department') : $order->department }}" name="order[department]">
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
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" name="receiver[receiver_name]" value="{{old('receiver.receiver_name') ? old('receiver.receiver_name') : (isset($order->receiver) ? $order->receiver->receiver_name : '')}}" class="form-control" />
                        @if ($errors->has('receiver.receiver_name'))
                            <span class="invalid-feedback" style="display: block;" role="alert">
                                <strong>{{ $errors->first('receiver.receiver_name') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                        <label>Số điện thoại</label>
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="number" name="receiver[receiver_phone]" value="{{old('receiver.receiver_phone') ? old('receiver.receiver_phone') : (isset($order->receiver) ? $order->receiver->receiver_phone : '')}}" class="form-control" />
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
                        <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="receiver[city_id]" id="receiver_city" class="form-control">
                            <option value=""></option>
                            @foreach($citys as $city)
                                <option value="{{$city->id}}" @if(isset($order->receiver) && $order->receiver->city_id == $city->id) selected @endif>{{$city->city_name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Huyện / Quận</label>
                        <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="receiver[district_id]" id="receiver_district" class="form-control">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Xã / Phường</label>
                        <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="receiver[ward_id]" id="receiver_ward" class="form-control">
                            <option value=""></option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Địa chỉ</label>
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" name="receiver[address]" value="{{old('receiver.address') ? old('receiver.address') : (isset($order->receiver) ? $order->receiver->address : '')}}" class="form-control" />
                        @if ($errors->has('receiver.address'))
                            <span class="invalid-feedback" style="display: block;" role="alert">
                                <strong>{{ $errors->first('receiver.address') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                        <label>Email</label>
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" name="receiver[receiver_email]" value="{{old('receiver.receiver_email') ? old('receiver.receiver_email') : (isset($order->receiver) ? $order->receiver->receiver_email : '')}}" class="form-control" />
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
                @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                <div class="@if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) col-md-3 @else col-md-4 @endif mb-3">
                    <label>Mã khác</label>
                    <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" class="form-control" value="{{old('order.invoice_code') ? old('order.invoice_code') : $order->invoice_code }}" name="order[invoice_code]">
                </div>

                <div class="@if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) col-md-3 @else col-md-4 @endif mb-3">
                    <label>Đối tác vận chuyển</label>
                    <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="order[partner]" class="form-control">
                        <option value=""></option>
                        @foreach($partners as $partner)
                            <option value="{{$partner->id}}" @if($order->partner == $partner->id) selected @endif>{{$partner->name}}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="@if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) col-md-3 @else col-md-6 @endif mb-3">
                    <label> Ngày gửi </label>
{{--                    <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" class="form-control" name="order[order_date]" value="10/24/1984" id="order_date">--}}
                    <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" class="form-control" name="order[order_date]" value="{{old('order.order_date') ? old('order.order_date') : (isset($order->order_date) ? $order->converDate($order->order_date) : '') }}" id="order_date">
                </div>
                    <div class="@if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) col-md-3 @else col-md-6 @endif mb-3">
                        <label for="validationDefault01"> Phương thức thanh toán </label>
                        <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="order[payment_method]" class="form-control">
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
                @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                <div class="col-md-3 mb-3">
                    <label> Người phụ trách </label>
                    <select @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="order[person_charge]" id="receiver_city" class="form-control">
                        <option value=""></option>
                        @foreach($users as $user)
                            <option value="{{$user->id}}" @if(isset($order->person_charge) && $order->person_charge == $user->id) selected @endif>{{$user->name}}</option>
                        @endforeach
                    </select>
                </div>
                @endif
{{--                <div class="col-md-4 mb-3">--}}
{{--                    <label for="validationDefault02">Tình trạng vận đơn</label>--}}
{{--                    <select name="order[order_status]" class="form-control">--}}
{{--                        <option value="0" selected></option>--}}
{{--                        @foreach(\App\Models\Order::MAP_ORDER_STATUS as $key => $order_status)--}}
{{--                            <option value="{{$key}}" @if($order->order_status == $key) selected @endif >{{$order_status}}</option>--}}
{{--                        @endforeach--}}
{{--                    </select>--}}
{{--                </div>--}}
                @if(auth()->user()->level != \App\User::LEVEL_USER)
                <div class=" @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) col-md-3 @else col-md-4 @endif mb-3">
                    <label for="validationDefault01">Tình trạng vận chuyển</label>
                    <select name="order[delivery_status]" class="form-control">
                        <option value="{{\App\Models\Order::DELIVERY_STATUS_PROCESSING}}" @if(!isset($order->delivery_status)) selected @endif></option>
                        @foreach(\App\Models\Order::DELIVERY_MAP as $key => $delivery)
                            <option value="{{$key}}" @if($order->delivery_status == $key) selected @endif >{{$delivery}}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(auth()->user()->level != \App\User::LEVEL_USER)
                <div class="@if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) col-md-3 @else col-md-4 @endif mb-3">
                    <label>Tỉnh / Thành phố </label>
                    <select name="order[location_id]" class="form-control">
                        <option value=""></option>
                        @foreach($citys as $city)
                            <option value="{{$city->id}}" @if(isset($order->location_id) && $order->location_id == $city->id) selected @endif>{{$city->city_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="@if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) col-md-3 @else col-md-4 @endif mb-3">
                    <label>Người ký nhận</label>
                    <input type="text" class="form-control" value="{{old('order.signator') ? old('order.signator') : $order->signator }}" name="order[signator]">
                </div>
                @endif
            </div>
        </div>
        <div class="form-group">
            <div class="form-row">
                <div class="col-md-3 mb-3">
                    <label for="validationDefault01">Trọng lượng</label>
                    <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" class="form-control" name="order[weight]" placeholder="Trọng lượng" value="{{old('order.weight') ? old('order.weight') :$order->weight}}">
                </div>
                <div class="col-md-6 mb-3">
                    <div class="row">
                        <div class="col">
                            <label for="validationDefault02">Chiều cao</label>
                            <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" class="form-control" value="{{old('order.height') ? old('order.height') : $order->height}}" name="order[height]" placeholder="Chiều cao">
                        </div>
                        <div class="col">
                            <label for="validationDefault01">Chiều dài</label>
                            <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" class="form-control" name="order[long]" value="{{old('order.long') ? old('order.long') :$order->long}}" placeholder="Chiều dài">
                        </div>
                        <div class="col">
                            <label for="validationDefault01">Chiều rộng</label>
                            <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="text" class="form-control" name="order[width]" value="{{old('order.width') ? old('order.width') :$order->width}}" placeholder="Chiều rộng">
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="validationDefault01">Giá trị hàng hóa</label>
                    <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif type="number" class="form-control" name="order[total]" value="{{old('order.total') ? old('order.total') : ( isset($order->total) ? $order->total : 0)}}" placeholder="Giá tị vận đơn">
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
                        <input @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="order_service[{{$type}}][]" value="{{$key}}" @if(in_array($key, $order->serviceArray($order->id))) checked @endif type="checkbox" class="form-check-input">
                        <label class="form-check-label">{{$item}}</label>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        <div class="form-group">
            <label>Nội dung</label>
            <textarea @if(auth()->user()->level == \App\User::LEVEL_POSTMAN) disabled @endif name="order[note]" class="form-control" rows="3">{{old('order.note') ? old('order.note') : $order->note}}</textarea>
        </div>
        <div id="results"></div>
        <div id="cardCamera" style="display: none">
            <div id="camera" style="border: 1px solid #cad1d7;min-height: 120px"></div>
            <div class="row mt-2">
                <div class="col text-center">
                    {{--  <button class="btn btn-primary" id="changeCamera" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-repeat" viewBox="0 0 16 16">
                            <path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/>
                            <path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/>
                        </svg>
                    </button>  --}}
                    <button class="btn btn-primary" type="button" id="snapshot">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-camera" viewBox="0 0 16 16">
                            <path d="M15 12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h1.172a3 3 0 0 0 2.12-.879l.83-.828A1 1 0 0 1 6.827 3h2.344a1 1 0 0 1 .707.293l.828.828A3 3 0 0 0 12.828 5H14a1 1 0 0 1 1 1v6zM2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 1 3.172 4H2z"/>
                            <path d="M8 11a2.5 2.5 0 1 1 0-5 2.5 2.5 0 0 1 0 5zm0 1a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM3 6.5a.5.5 0 1 1-1 0 .5.5 0 0 1 1 0z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
@section('javascript')
    <script type="text/javascript">

        $(function() {
            var shutter = new Audio();
            shutter.autoplay = false;
            shutter.src = "{{asset('file/camera-focus-beep-01.mp3')}}"

            $('#openCamera').click(function() {
                $('#cardCamera').css({"display": ""})
                Webcam.set({
                    width: '100%',
                    height: '100%',
                    force_flash: false,
                    image_fromat: 'jpeg',
                    jpeg_quality: 90,
                    constraints: {
                        facingMode: 'environment'
                    }
                })
                Webcam.attach('#camera')
            })

            $('#snapshot').click(function() {
                shutter.play();
                Webcam.snap(function(data_uri) {
                    console.log('data_uri', data_uri)
                    document.getElementById('results').innerHTML = '<img src="'+ data_uri +'"/>';
                })
                Webcam.reset();
            })

        })

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
                        cancelLabel: 'Clear',
                        format: "DD/MM/YYYY"
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
