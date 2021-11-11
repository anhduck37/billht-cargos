@extends('layouts.app')

@section('content')
    <div class="container">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-12">
                    <h1>Change password</h1>
                </div>
            </div>
        </div>
    </section>
    <div class="content px-3">

        @include('adminlte-templates::common.errors')

        <div class="card" >

            <form method="post" action="{{route('users.updatePassword',$id)}}">
                @csrf
                <div class="card-body" >
                    <label>@lang('auth.change_password')</label>
                    <input type="password" name="password" class="form-control" placeholder="@lang('auth.password')">

                    <label>@lang('auth.confirm_password')</label>
                    <input type="password" name="confirmPassword" class="form-control" placeholder="@lang('auth.confirm_password')">
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('users.index') }}" class="btn btn-default">@lang('fields.btn_cancel')</a>
                </div>
            </form>
            </div>

        </div>
    </div>
@endsection
