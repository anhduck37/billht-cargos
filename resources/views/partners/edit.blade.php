@extends('layouts.app')

@section('content')

    @include('layouts.headers.cards')

    <div class="container-fluid mt--3">

        <div class="row mt-5">
            <div class="col-xl-12 mb-5 mb-xl-0">
                <div class="card shadow">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h2 class="mb-0 text-center">Cập nhật đối tác vận chuyển</h2>
                            </div>
                        </div>
                    </div>
                    <div class="container mt-4 mb-4">
                        {!! Form::model($partner, ['route' => ['partners.update', $partner->id], 'method' => 'patch']) !!}
                            @csrf
                            @include('partners.fields')
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary mt-4">Cập nhật đối tác vận chuyển</button>
                                <a href="{{route('users.index')}}" class="btn btn-secondary mt-4" >Thoát</a>
                            </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
