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
                            <div class="col-md-4 mb-3">
                                <label>Email</label>
                                <input type="text" value="{{request('email', '')}}" class="form-control" name="email" placeholder="Email">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Số điện thoại</label>
                                <input type="number" value="{{request('phone', '')}}" class="form-control" name="phone" placeholder="Số điện thoại">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Đơn vị vận chuyển</label>
                                <select name="partner" class="form-control">
                                    <option value=""></option>
                                    @foreach($partners as $partner)
                                        <option value="{{$partner->id}}" @if(request('partner') == $partner->id) selected @endif>{{$partner->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-md-4 mb-3">
                                <label>Mã vận đơn</label>
                                <input type="text" class="form-control" value="{{request('order_code', '')}}" name="order_code" placeholder="Mã vận đơn">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Ngày gửi</label>
                                <input type="text" class="form-control" value="{{request('order_date', '')}}" name="order_date" id="order_date" placeholder="Ngày gửi">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Trạng thái vận đơn</label>
                                <select name="order_status" class="form-control">
                                    <option value=""></option>
                                    @foreach(\App\Models\Order::DELIVERY_MAP as $key => $status)
                                        <option value="{{$key}}" @if(request('delivery_status') == $key) selected @endif>{{$status}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-row">
                                    <div class="col-md-1 mb-3">

                                    </div>
                                <div class="col-md-1 mb-3">

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        {!! Form::submit('Tìm kiếm', ['class' => 'btn btn-primary']) !!}
                        <a href="{{route('orders.showFormImport')}}" class="btn btn-primary">Import</a>
                        <button id="print" type="button" class="btn btn-primary">In đơn</button>
                                        <a class="btn btn-primary float-right"
                                           href="{{ route('orders.create') }}">
                                            Tạo vận đơn
                                        </a>
                    </div>

                    {!! Form::close() !!}
                    @include('orders.table')

                </div>
                <div class="align-content-center" style="margin-top: 20px">
                    {!! $orders->links() !!}
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
                        cancelLabel: 'Clear'
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
