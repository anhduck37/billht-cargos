@extends('layouts.app')

@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Order #{{ $order->id }}</h1>
                </div>
                <div class="col-sm-6">
                    @if(
                        (isset($order->sender) && $order->sender->address_scheme === 'new')
                        || (isset($order->receiver) && $order->receiver->address_scheme === 'new')
                    )
                        <form method="POST"
                              action="{{ route('orders.convertToLegacyAddress', $order->id) }}"
                              class="float-right ml-2"
                              onsubmit="return confirm('Chuyển vận đơn này về form địa chỉ cũ? Thông tin đơn sẽ được giữ nguyên.');">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                Chuyển về form địa chỉ cũ
                            </button>
                        </form>
                    @endif
                    @if(
                        (isset($order->sender) && $order->sender->address_scheme !== 'new')
                        || (isset($order->receiver) && $order->receiver->address_scheme !== 'new')
                    )
                        <form method="POST"
                              action="{{ route('orders.convertToNewAddress', $order->id) }}"
                              class="float-right ml-2"
                              onsubmit="return confirm('Chuyển vận đơn này sang form địa chỉ mới? Thông tin đơn sẽ được giữ nguyên.');">
                            @csrf
                            <button type="submit" class="btn btn-info">
                                Chuyển sang form địa chỉ mới
                            </button>
                        </form>
                    @endif
                    <a class="btn btn-default float-right"
                       href="{{ route('orders.index') }}">
                        Back
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">
        @include('flash::message')
        <div class="card">

            <div class="card-body">
                <div class="row">
                    @include('orders.show_fields')
                </div>
            </div>

        </div>
    </div>
@endsection
