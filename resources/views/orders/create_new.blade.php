@extends('layouts.app')

@section('content')

    @include('layouts.headers.cards')

    <div class="container-fluid mt--4">
        <div class="row mt-5">
            <div class="col-xl-12 mb-5 mb-xl-0">
                @include('flash::message')
                <div class="card shadow border-0">
                    <div class="card-header bg-white border-0">
                        <div class="row align-items-center">
                            <div class="col-md-8 text-left">
                                <h1 class="text-primary mt-3 mb-0"><i class="fas fa-plus-circle mr-2"></i>Tạo Vận Đơn Mới 2025</h1>
                                <p class="text-muted small">Sử dụng hệ thống địa danh hành chính mới nhất (Tỉnh, Xã)</p>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="{{ route('orders.create') }}" class="btn btn-sm btn-outline-warning shadow-sm"><i class="fas fa-exchange-alt mr-1"></i> Quay về trang tạo đơn cũ</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        {!! Form::open(['route' => 'orders.store', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
                            @csrf
                            @include('orders.fields_new')

                        <div class="card-footer text-center bg-secondary border-0" style="border-radius: 0 0 12px 12px;">
                            @if(auth()->user()->level != \App\User::LEVEL_USER)
                            <button type="button" id="image" class="btn btn-info mb-2"><i class="fas fa-camera mr-2"></i>Chụp ảnh</button>
                            @endif
                            {!! Form::submit( 'Tạo vận đơn' , ['class' => 'btn btn-success btn-lg mb-2 px-5 font-weight-bold shadow']) !!}
                            <a class='btn btn-outline-primary mb-2' href="{{route('orders.index')}}"><i class="fas fa-list mr-2"></i>Quản lý vận đơn</a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
