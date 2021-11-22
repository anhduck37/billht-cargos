@extends('layouts.app')

@section('content')

    @include('layouts.headers.cards')

    <div class="container-fluid mt--4">
        <div class="row mt-5">
            <div class="col-xl-12 mb-5 mb-xl-0">
                <div class="card shadow">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h2 class="mb-0">Tra cứu đơn hàng</h2>
                            </div>
                        </div>
                    </div>
                    {!! Form::open(['method' => 'GET']) !!}

                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-3">
                                <label>Vui lòng nhập mã vận đơn, ví dụ: HE000001</label>
                                <!-- {!! Form::label( 'Vui lòng nhập mã vận đơn. Ví dụ: HE000001' ) !!} -->
                                {!! Form::text('order_code', request('order_code', ''), ['class' => 'form-control']) !!}
                            </div>
                            <div class="form-group col-md-3">
                                {!! Form::submit('Tìm kiếm', ['class' => 'btn btn-primary', 'style' => 'margin-bottom: -83px;']) !!}
                            </div>
                        </div>
                    </div>
                    {!! Form::close() !!}

                    <div class="table-responsive mt-4">
                        <table class="table align-items-center">
                            <thead style="background-color: #f6821f; color: white" class="thead-light">
                            <tr>
                                <td>Mã vận đơn</td>
                                <td>Trạng thái vận đơn</td>
                                <td>Người gửi</td>
                                <td>Người nhận</td>
                                <td>Địa chỉ</td>
                                <td>Người ký nhận</td>
                                <td>Thời gian cập nhật</td>
                                <td>Nội dung</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($order_trackings as $item)
                                <tr>
                                    <th scope="row">
                                        <div class="media align-items-center">
                                            <div class="media-body">
                                                <span class="mb-0 text-sm">{{$item->order_code}}</span>
                                            </div>
                                        </div>
                                    </th>
                                    <td>{{$item->getDeliveryStatusName($item->delivery_status)}}</td>
                                    <td>{{isset($item->order) && isset($item->order->sender) ? $item->order->sender->sender_name : ''}}</td>
                                    <td>{{isset($item->order) && isset($item->order->receiver) ? $item->order->receiver->receiver_name : ''}}</td>
                                    <td>{{isset($item->location) ? $item->location->city_name : ''}}</td>
                                    <td>{{$item->signator}}</td>
                                    <td>
                                        {{$item->updated_at}}
                                    </td>
                                    <td>{{isset($item->order) ? $item->order->note : ''}}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
{{--                <div class="align-content-center" style="margin-top: 20px">--}}
{{--                    {!! $users->links() !!}--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
@endsection

