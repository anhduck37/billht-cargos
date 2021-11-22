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
                            {!! Form::submit( 'Nhập' , ['class' => 'btn btn-primary']) !!}
                            <a class='btn btn-light' href="{{route('orders.index')}}">Thoát</a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

