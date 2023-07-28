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
                                <h1 class="text-center">Tạo vận đơn mới</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => 'orders.store', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
                            @csrf
                            @include('orders.fields')

                        <div class="card-footer text-center">
                            <!-- @if(auth()->user()->level != \App\User::LEVEL_USER)
                            <button type="button" id="image" class="btn btn-primary mb-2">Chụp ảnh</button>
                            <div class="scanner-box">
                                <div id="scanner-container" class="main_scanner"></div>
                            </div>
                            @endif -->
                            {!! Form::submit( 'Cập nhật' , ['class' => 'btn btn-primary mb-2']) !!}
                            <!-- <a class='btn btn-light mb-2' href="{{route('orders.index')}}">Thoát</a> -->
                            <a class='btn btn-primary mb-2' href="{{route('orders.index')}}">Tìm vận đơn</a>
                            <a class='btn btn-primary mb-2' href="{{ route('orders.create') }}">Tạo vận đơn khác</a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


