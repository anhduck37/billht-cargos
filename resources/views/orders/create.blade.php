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
                                <h1 class="text-center">Tạo vận đơn</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => 'orders.store', 'method' => 'POST']) !!}
                            @csrf
                            @include('orders.fields')

                        <div class="card-footer text-center">
                            {!! Form::submit( 'Tạo vận đơn' , ['class' => 'btn btn-primary']) !!}
                            <a class='btn btn-light' href="{{route('orders.index')}}">Thoát</a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

