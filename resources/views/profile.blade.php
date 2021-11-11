@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{__('profile.title')}}</h1>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">


        <div class="card">
            @include('flash::message')
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12">
                        {!! Form::label(__('profile.name')) !!}
                        <p>{{ $user->name }}</p>
                    </div>
                    <!-- Email Field -->
                    <div class="col-sm-12">
                        {!! Form::label(__('profile.email')) !!}
                        <p>{{ $user->email }}</p>
                    </div>
                    <div class="col-sm-12">
                        {!! Form::label(__('profile.phone')) !!}
                        <p>{{ $user->phone }}</p>
                    </div>
                    <div class="col-sm-12">
                        {!! Form::label(__('profile.number_id')) !!}
                        <p>{{ $user->number_id }}</p>
                    </div>

                    <div class="col-sm-12">
                        {!! Form::label(__('profile.address')) !!}
                        <p>{{ $user->address }}</p>
                    </div>

                    <div class="col-sm-12">
                        {!! Form::label(__('user.tax_code')) !!}
                        <p>{{ $user->tax_code }}</p>
                    </div>


                    <div class="col-sm-12">

                        {!! Form::label(__('user.image_number_id')) !!}
                        @if(isset($user->image_number_id) )
                            <div class="row align-items-start">
                                @foreach(json_decode($user->image_number_id) as $image)
                                    <div class="col-3">
                                        <img height="150px" src="{{$image}}" style="object-fit: cover" >
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p></p>
                        @endif
                    </div>

                    <div class="col-sm-12">
                        {!! Form::label(__('user.link_info')) !!}
                        <p><a href="{{ $user->link_info }}">{{ $user->link_info }}</a></p>
                    </div>

                    <div class="col-sm-12">
                        {!! Form::label(__('profile.created_at')) !!}
                        <p>{{ $user->created_at }}</p>
                    </div>

                    <div class="col-sm-12">
                        {!! Form::label(__('link_aff')) !!}
                        <p>{{ $links }}?tracking_code={{ $user->tracking_code }}</p>
                    </div>

                </div>
            </div>

        </div>

        <div class="card">

            <div class="card-body">
                <div class="row">
                    <div class="col-sm-12">
                        {!! Form::label(__('profile.totalMoney')) !!}
                        {{ $infoProfit['totalMoney'] }}
                    </div>
                    <!-- Email Field -->
                    <div class="col-sm-12">
                        {!! Form::label(__('profile.totalUnpaidProfit')) !!}
                        {{ $infoProfit['totalUnpaidProfit']}}
                    </div>

                    <div class="col-sm-12">
                        {!! Form::label(__('profile.totalProfitMonth')) !!}
                        {{ $infoProfit['totalProfitMonth'] }}
                    </div>

                    <div class="col-sm-12">
                        {!! Form::label(__('profile.totalPaidProfit')) !!}
                        {{ $infoProfit['totalPaidProfit'] }}
                    </div>

                </div>
            </div>

        </div>
    </div>
@endsection
