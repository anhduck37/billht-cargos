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
                                <h1 class="text-center">Cập nhật vận đơn</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => ['orders.update', $order->id], 'method' => 'PATCH', 'enctype' => 'multipart/form-data']) !!}
                        @csrf
                        @include('orders.fields')

                        <div class="card-footer text-center">
                            {!! Form::submit( 'Cập nhật vận đơn' , ['class' => 'btn btn-primary mb-2']) !!}
                            <button id="print" type="button" data-id="{{$order->id}}" class="btn btn-primary mb-2">In đơn</button>
                            @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                            <button type="button" data-toggle="modal" data-target="#openModalEmail" class="btn btn-primary mb-2">Gửi email</button>
                            @endif
                            @if(auth()->user()->level != \App\User::LEVEL_USER)
                            <button type="button" id="image" class="btn btn-primary mb-2">Chụp ảnh</button>
                            @endif
                            <a class='btn btn-light mb-2' href="{{route('orders.index')}}">Thoát</a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
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
                        <button type="button" data-id="{{$order->id}}" id="sendEmail" class="btn btn-primary">Gửi email</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script type="text/javascript" src="{{ asset('js/render-print.js') }}"></script>
    <script type="text/javascript">
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#print').on('click', function (e) {
                let orderId = $(this).attr('data-id');
                console.log(orderId)
                $.ajax({
                    type: "POST",
                    url: '/template/render',
                    data: {'order': [orderId]},
                    success: function (res) {
                        let html = renderHtml(res)
                        print(html)
                    },
                });
            })

            $('#sendEmail').on('click', function () {
                let orderId = $(this).attr('data-id');
                let typeEmail = $('input[name="type_email"]:checked').val()
                if(typeEmail) {
                    $('.isShow').css('display', 'none')
                    $('#isLoading').css('display', '')
                    $.ajax({
                        type: "POST",
                        url: '/order/send-email',
                        data: {'order_ids': [orderId], type_email: typeEmail, isUpdate: true },
                        success: function (res) {
                            window.location.href = res;
                        },
                    });
                }
            })
        });
        function print(html) {
            var a = window.open();
            a.document.write(html);
            a.document.close();
        }
    </script>
@endsection
