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
                                <h2 class="mb-0">Quản lý đơn vị vận chuyển</h2>
                            </div>
                            <div class="col text-right">
                                <a class="btn btn-primary float-right"
                                   href="{{ route('partners.create') }}">
                                    Tạo đơn vị vận chuyển
                                </a>
                            </div>
                        </div>
                    </div>

                    @include('partners.table')

                </div>
{{--                <div class="align-content-center" style="margin-top: 20px">--}}
{{--                    {!! $partners->links() !!}--}}
{{--                </div>--}}
            </div>
        </div>
    </div>
@endsection

