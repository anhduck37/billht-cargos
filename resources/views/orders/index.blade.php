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
                                <h2 class="mb-0">Tìm kiếm</h2>
                            </div>
                        </div>
                    </div>
                    {!! Form::open(['method' => 'GET']) !!}

                    <div class="card-body">
                        <div class="form-row">
                            <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-4 @else col-md-6 @endif mb-3">
                                <label>Tên cá nhân / Công ty</label>
                                <input type="text" value="{{request('name', '')}}" class="form-control" name="name" placeholder="Tên cá nhân / Công ty">
                            </div>
                            <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-4 @else col-md-6 @endif mb-3">
                                <label>Số điện thoại</label>
                                <input type="number" value="{{request('phone', '')}}" class="form-control" name="phone" placeholder="Số điện thoại">
                            </div>
                            @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                            <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-4 @else col-md-6 @endif mb-3">
                                <label>Đơn vị vận chuyển</label>
                                <select name="partner" class="form-control">
                                    <option value=""></option>
                                    @foreach($partners as $partner)
                                        <option value="{{$partner->id}}" @if(request('partner') == $partner->id) selected @endif>{{$partner->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                        <div class="form-row">
                            <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-4 @else col-md-6 @endif mb-3">
                                <label>Mã vận đơn</label>
                                <input type="text" class="form-control" value="{{request('order_code', '')}}" name="order_code" placeholder="Mã vận đơn">
                            </div>
                            <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-4 @else col-md-6 @endif mb-3">
                                <label>Ngày gửi</label>
                                <input type="text" class="form-control" value="{{request('order_date', '')}}" name="order_date" id="order_date" placeholder="Ngày gửi">
                            </div>
                            @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                            <div class="@if(auth()->user()->level == \App\User::LEVEL_ADMIN) col-md-4 @else col-md-6 @endif mb-3">
                                <label>Trạng thái vận đơn</label>
                                <select name="delivery_status" class="form-control">
                                    <option value=""></option>
                                    @foreach(\App\Models\Order::DELIVERY_MAP as $key => $status)
                                        <option value="{{$key}}" @if(request('delivery_status') == $key) selected @endif>{{$status}}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <div class="row">
                            <div class="col mb-1" >
                                {!! Form::submit('Tìm kiếm', ['class' => 'btn btn-primary', 'style' => 'width: 100%']) !!}
                            </div>
                            <div class="col mb-1">
                                <a style="width: 100%" href="{{route('orders.showFormImport')}}" class="btn btn-primary">Nhập file excel</a>
                            </div>
                            <div class="col mb-1">
                                <button style="width: 100%" id="print" type="button" class="btn btn-primary">In đơn</button>
                            </div>
                            <div class="col mb-1">
                                <a href="{{route('orders.export')}}" style="width: 100%" class="btn btn-primary float-right"
                                   >
                                    Xuất file excel
                                </a>
                            </div>
                            @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                            <div class="col mb-1">
                                <button style="width: 100%" type="button" id="deleteMany" class="btn btn-primary">Xóa vận đơn</button>
                            </div>
                            <div class="col mb-1">
                                <button style="width: 100%" type="button" id="updateMany" class="btn btn-primary">Cập nhật trạng thái</button>
                            </div>
                            <div class="col mb-1">
                                <button style="width: 100%" type="button" data-toggle="modal" data-target="#openModalEmail" class="btn btn-primary">Gửi email</button>
                            </div>
                            @endif
                        </div>
                    </div>

                    {!! Form::close() !!}
                    @include('orders.table')

                </div>
                <div class="align-content-center" style="margin-top: 20px">
                    {!! $orders->links() !!}
                </div>

                <!-- Modal -->
                <div class="modal fade" id="openModalEmail" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title" id="exampleModalLongTitle">Bạn vui lòng template email</h3>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" id="loading">
                                <div id="isLoading" style="display: none" class="text-center"><img width="70px" src="{{asset('/image/loading.jpg')}}" ></div>

                                <div class="form-check isShow">
                                    <input class="form-check-input" type="radio" name="type_email" id="exampleRadios1" value="1">
                                    <label class="form-check-label" for="exampleRadios1">
                                        Đã tiếp nhận bưu phẩm
                                    </label>
                                </div>
                                <div class="form-check isShow">
                                    <input class="form-check-input" type="radio" name="type_email" id="exampleRadios2" value="2">
                                    <label class="form-check-label" for="exampleRadios2">
                                        Đã giao
                                    </label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                                <button type="button" id="sendEmail" class="btn btn-primary">Gửi email</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script type="text/javascript">
        $(function() {
            $(function() {
                $('#order_date').daterangepicker({
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: 'Clear',
                        format: "DD/MM/YYYY"
                    }
                });
            });
            $('#order_date').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            });
            $('#order_date').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

        });
    </script>
@endsection
