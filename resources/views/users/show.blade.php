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
                                <h2 class="mb-0">Thông tin tài khoản</h2>
                            </div>
                        </div>
                    </div>
                    {!! Form::open(['method' => 'GET']) !!}

                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-3">
                                {!! Form::label( __('user.email') ) !!}
                                {!! Form::text('email', request('email', ''), ['class' => 'form-control']) !!}
                            </div>
                            <div class="form-group col-md-3">
                                {!! Form::label( __('user.level') ) !!}
                                {!! Form::select('level', \App\User::LEVEL_MAP, request('level', ''), ['class' => 'form-control']) !!}
                            </div>
                            <div class="form-group col-md-3">
                                {!! Form::label( __('user.status') ) !!}
                                {!! Form::select('status', \App\User::STATUS_MAP ,request('status', ''), ['class' => 'form-control' ]) !!}
                            </div>
                            <div class="form-group col-md-3">
                                {!! Form::submit('Tìm kiếm', ['class' => 'btn btn-primary', 'style' => 'margin-bottom: -83px;']) !!}
                            </div>

                        </div>
                    </div>
                    {!! Form::close() !!}

                    @include('users.table')


                    <div class="align-content-center" style="margin-top: 10px">
                        {!! $users->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

