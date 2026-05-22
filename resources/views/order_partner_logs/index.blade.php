@extends('layouts.app')

@section('content')
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <!-- Card stats -->
            <div class="row">
                <div class="col-xl-6 col-lg-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">Thành công trong tháng</h5>
                                    <span class="h2 font-weight-bold mb-0 text-success">{{ $successCount }}</span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap">Đồng bộ thành công (VTP, EMS)</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 col-lg-6">
                    <div class="card card-stats mb-4 mb-xl-0">
                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">Thất bại trong tháng</h5>
                                    <span class="h2 font-weight-bold mb-0 text-danger">{{ $errorCount }}</span>
                                </div>
                                <div class="col-auto">
                                    <div class="icon icon-shape bg-danger text-white rounded-circle shadow">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 mb-0 text-muted text-sm">
                                <span class="text-nowrap">Đơn lỗi cần kiểm tra lại</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--7">
    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h3 class="mb-0">Lịch sử đồng bộ vận đơn (VTP / EMS)</h3>
                        </div>
                        <div class="col-4 text-right">
                            <!-- Placeholder for bulk actions if needed -->
                        </div>
                    </div>
                    
                    <!-- Filter bar -->
                    <form action="{{ route('order_partner_logs.index') }}" method="GET" class="form-inline mt-3">
                        <div class="form-group mr-2">
                            <label for="filter_date" class="form-control-label mr-2">Ngày:</label>
                            <input type="date" name="filter_date" id="filter_date" value="{{ request('filter_date') }}" class="form-control form-control-sm">
                        </div>
                        
                        <div class="form-group mr-2">
                            <label for="filter_status" class="form-control-label mr-2">Trạng thái:</label>
                            <select name="filter_status" id="filter_status" class="form-control form-control-sm">
                                <option value="">Tất cả</option>
                                <option value="1" {{ request('filter_status') === '1' ? 'selected' : '' }}>Thành công</option>
                                <option value="0" {{ request('filter_status') === '0' ? 'selected' : '' }}>Thất bại</option>
                            </select>
                        </div>

                        <div class="form-group mr-2">
                            <label for="filter_partner" class="form-control-label mr-2">Đối tác:</label>
                            <select name="filter_partner" id="filter_partner" class="form-control form-control-sm">
                                <option value="">Tất cả</option>
                                <option value="viettel_post" {{ request('filter_partner') === 'viettel_post' ? 'selected' : '' }}>Viettel Post</option>
                                <option value="ems" {{ request('filter_partner') === 'ems' ? 'selected' : '' }}>EMS</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                        @if(request()->has('filter_date') || request()->has('filter_status') || request()->has('filter_partner'))
                            <a href="{{ route('order_partner_logs.index') }}" class="btn btn-secondary btn-sm ml-1">Làm mới</a>
                        @endif
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">Mã vận đơn</th>
                                <th scope="col">Người gửi</th>
                                <th scope="col">Đối Tác</th>
                                <th scope="col">Chi tiết phản hồi</th>
                                <th scope="col">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($logs) > 0)
                                @foreach($logs as $log)
                                    <tr>
                                        <td>
                                            @if($log->order_id > 0)
                                                <a href="{{ route('orders.edit', $log->order_id) }}" target="_blank" class="font-weight-bold">
                                                    {{ $log->parsed->order_number }}
                                                </a>
                                            @else
                                                {{ $log->parsed->order_number }}
                                            @endif
                                        </td>
                                        <td>{{ $log->parsed->sender_name }}</td>
                                        <td>
                                            @if(strtoupper($log->partner_code) == 'EMS')
                                                <span class="badge badge-dot mr-4">
                                                    <i class="bg-warning"></i> EMS
                                                </span>
                                            @else
                                                <span class="badge badge-dot mr-4">
                                                    <i class="bg-danger"></i> VTP
                                                </span>
                                            @endif
                                        </td>
                                        <td style="white-space: normal; max-width: 400px;">
                                            {!! $log->parsed->response_html !!}
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($log->updated_at)->format('H:i d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center">Không có dữ liệu logs.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer py-4">
                    <div class="d-flex justify-content-end">
                        {{ $logs->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
