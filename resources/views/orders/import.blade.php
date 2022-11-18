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
                                <h1 class="text-center">Nhập vận đơn hàng loạt</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => 'orders.import', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
                        @csrf
                        <div class="input-group mb-3">
                            <div class="custom-file">
                                <div class="col-10">
                                    <input type="file" name="file" id="file">
                                </div>

{{--                                <label class="custom-file-label" for="file">Choose file</label>--}}

                            </div>
                        </div>
                        <div class="input-group mb-3">
                            <div class="col-10">
                                    <a href="{{route('fileDemo')}}">Tải File Mẫu</a>
                                </div>

                            </div>
                        </div>

                        <div class="card-footer text-center">
                            
                            {!! Form::submit( 'Nhập Excel' , ['class' => 'btn btn-primary']) !!}
                            <button data-toggle="modal" data-target="#openModalPrint" type="button" class="btn btn-primary">In tất cả</button>
                            <a class='btn btn-light' href="{{route('orders.index')}}">Quản lý vận đơn</a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
            @include('orders.import_table')
        </div>
        <div class="modal fade" id="openModalPrint" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="exampleModalLongTitle">Bạn vui lòng chọn liên</h3>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="loading">
                        <div id="isLoading" style="display: none" class="text-center"><img width="70px" src="{{asset('/image/loading.jpg')}}" ></div>

                        <div class="form-check isShow">
                            <input class="form-check-input" type="radio" name="number" id="number1" value="1">
                            <label class="form-check-label" for="number1">
                                1 liên
                            </label>
                        </div>
                        <div class="form-check isShow">
                            <input class="form-check-input" type="radio" name="number" checked id="number2" value="2">
                            <label class="form-check-label" for="number2">
                                2 liên
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" id="print" class="btn btn-primary">In đơn</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection

