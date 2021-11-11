@extends('layouts.app')

@section('content')
<div class="container">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>{{ __('user.changepass') }}</h1>
                </div>
            </div>
        </div>
    </section>
    <div class="content px-3">
    <div class="card">        
        {!! Form::open([]) !!}
        <div class="card-body">
            <div class="row">
                <div class="container row">
                    <div class="form-group col-md-4">
                        {!! Form::label( __('user.passnew') ) !!}
                        {!! Form::password('password', ['class' => 'form-control']) !!}
                    </div>

                </div>
            </div>
        </div>
        <div class="card-footer">
            {!! Form::submit( __('user.save') , ['class' => 'btn btn-primary']) !!}                
        </div>
        {!! Form::close() !!}

    </div>
</div>
@endsection
