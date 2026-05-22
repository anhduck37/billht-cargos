@extends('layouts.app')

@section('content')
    @include('layouts.headers.cards')

    <div class="container-fluid mt--4">
        <div class="row mt-5">
            <div class="col-xl-12 mb-5 mb-xl-0">
                @include('flash::message')
                <div class="card shadow">
                    <div class="card-header border-0">
                        <h1 class="text-center">Cập nhật trạng thái</h1>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => 'order_status_changes.import', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
                        @csrf
                        <div class="input-group mb-3">
                            <div class="custom-file">
                                <div class="col-10">
                                    <input type="file" name="file" id="file">
                                </div>
                            </div>
                        </div>
                        <div class="input-group mb-3">
                            <div class="col-10">
                                <a href="{{ route('order_status_changes.template') }}">Tải File Mẫu Đổi trạng thái</a>
                            </div>
                        </div>
                        <div class="mb-3 ml-3">
                            <p class="mb-1">- Cột <strong>Ngày tháng</strong> để trống sẽ lấy thời điểm import file cập nhật.</p>
                            <p class="mb-1">- <strong>Loại bill:</strong> để trống là cập nhật bill đã có, nhập <strong>"Tạo mới"</strong> để tạo bill mới.</p>
                            <p class="mb-2"><strong>- Tỉnh đến phải nhập đúng tên tỉnh được hỗ trợ (hoa thường không quan trọng):</strong></p>
                            <div class="alert alert-info" style="max-height: 200px; overflow-y: auto; font-size: 0.9rem;">
                                <strong>Danh sách tỉnh hỗ trợ:</strong><br>
                                <small>
                                    {{ implode(', ', $provinces) }}
                                </small>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            {!! Form::submit('Nhập Excel', ['class' => 'btn btn-primary']) !!}
                            <a class='btn btn-light' href="{{route('orders.index')}}">Quản lý vận đơn</a>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($importResult))
            <div class="row mt-4">
                <div class="col-xl-12">
                    <div class="card shadow">
                        <div class="card-header border-0">
                            <h2 class="mb-0">Kết quả import</h2>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <p><strong>Thành công:</strong> <span class="badge badge-success">{{ $importResult['success'] }}</span></p>
                                <p><strong>Bỏ qua:</strong> <span class="badge badge-warning">{{ $importResult['skipped'] }}</span></p>
                            </div>

                            @if(!empty($importResult['successBills']))
                                <div class="alert alert-success">
                                    <h5 class="mb-3">Danh sách mã bill thành công</h5>
                                    <div style="max-height: 250px; overflow-y: auto;">
                                        @foreach($importResult['successBills'] as $billCode)
                                            <span class="badge badge-success mr-2 mb-2">{{ $billCode }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if(!empty($importResult['errors']))
                                <div class="alert alert-warning">
                                    <h5 class="mb-3">Danh sách lỗi/bỏ qua</h5>
                                    <div style="max-height: 300px; overflow-y: auto;">
                                        @foreach($importResult['errors'] as $error)
                                            <div class="mb-2">
                                                <small class="text-muted">{{ $error }}</small>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted d-block mt-3">
                                        💾 Chi tiết log được lưu tại: <code>storage/logs/order-status-change-import-{{ date('Y-m-d') }}.log</code>
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
