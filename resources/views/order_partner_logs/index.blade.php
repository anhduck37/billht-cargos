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
            @include('flash::message')
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="card shadow">
                <div class="card-header border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">Trạng thái API</h3>
                            <p class="text-muted text-sm mb-0">Hệ thống tự động kiểm tra mỗi 3 giờ. Có thể bấm kiểm tra thủ công khi cần.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row">
                        @foreach($apiStatuses as $providerKey => $apiStatus)
                            <div class="col-xl-4 col-lg-4 col-md-6 mb-3">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="mb-1">{{ $apiStatus['name'] }}</h5>
                                            @if($apiStatus['online'] === true)
                                                <span class="badge badge-success">Online</span>
                                            @elseif($apiStatus['online'] === false)
                                                <span class="badge badge-danger">Offline</span>
                                            @else
                                                <span class="badge badge-secondary">Chưa kiểm tra</span>
                                            @endif
                                        </div>
                                        @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                                            {!! Form::open(['route' => array_merge(['order_partner_logs.api_status.check', $providerKey], request()->query()), 'method' => 'POST', 'class' => 'd-inline']) !!}
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Check thủ công</button>
                                            {!! Form::close() !!}
                                        @endif
                                    </div>
                                    <div class="mt-2 text-sm text-muted">
                                        <div>Lần kiểm tra: {{ !empty($apiStatus['checked_at']) ? \Carbon\Carbon::parse($apiStatus['checked_at'])->format('H:i d/m/Y') : 'Chưa có' }}</div>
                                        <div>Mã phản hồi: {{ $apiStatus['status_code'] ?: 'N/A' }}</div>
                                        <div class="text-truncate" title="{{ $apiStatus['message'] }}">Chi tiết: {{ $apiStatus['message'] }}</div>
                                    </div>

                                    @if($providerKey === 'mickey' && in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                                        <div class="mt-3 d-flex flex-wrap">
                                            {!! Form::open(['route' => array_merge(['order_partner_logs.mickey.sync'], request()->query()), 'method' => 'POST', 'class' => 'mb-2']) !!}
                                                <input type="hidden" name="limit" value="{{ config('tracking.mickey_manual_limit', 20) }}">
                                                <button type="submit" class="btn btn-sm btn-info">Đồng bộ trạng thái</button>
                                            {!! Form::close() !!}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-header border-0">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h3 class="mb-0">Lịch sử đồng bộ vận đơn (VTP / EMS / Mickey)</h3>
                        </div>
                        <div class="col-4 text-right">
                            @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                                <button type="submit" form="bulkCancelPartnerOrdersForm" class="btn btn-sm btn-danger">Huỷ đồng bộ hàng loạt</button>
                            @endif
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
                                <option value="VIETTEL_POST" {{ strtoupper(request('filter_partner', '')) === 'VIETTEL_POST' ? 'selected' : '' }}>Viettel Post</option>
                                <option value="EMS" {{ strtoupper(request('filter_partner', '')) === 'EMS' ? 'selected' : '' }}>EMS</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                        @if(request()->has('filter_date') || request()->has('filter_status') || request()->has('filter_partner'))
                            <a href="{{ route('order_partner_logs.index') }}" class="btn btn-secondary btn-sm ml-1">Làm mới</a>
                        @endif
                    </form>

                </div>

                <div class="table-responsive">
                    {!! Form::open(['route' => array_merge(['order_partner_logs.bulk_cancel'], request()->query()), 'method' => 'POST', 'id' => 'bulkCancelPartnerOrdersForm', 'class' => 'd-none']) !!}
                        <input type="hidden" name="reason" value="Huy don hang loat tu BillHT">
                    {!! Form::close() !!}
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col" class="text-center" style="width: 40px;">
                                    <input type="checkbox" id="checkAllCancelableLogs">
                                </th>
                                <th scope="col">Mã vận đơn</th>
                                <th scope="col">Người gửi</th>
                                <th scope="col">Đối Tác</th>
                                <th scope="col">Chi tiết phản hồi</th>
                                <th scope="col">Thời gian</th>
                                <th scope="col" class="text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($logs) > 0)
                                @foreach($logs as $log)
                                    <tr>
                                        <td class="text-center">
                                            @if($log->can_cancel)
                                                <input type="checkbox" class="cancel-log-checkbox" form="bulkCancelPartnerOrdersForm" name="log_ids[]" value="{{ $log->id }}">
                                            @else
                                                <input type="checkbox" disabled>
                                            @endif
                                        </td>
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
                                        <td class="text-right">
                                            @if($log->can_cancel)
                                                {!! Form::open(['route' => array_merge(['order_partner_logs.cancel', $log->id], request()->query()), 'method' => 'POST', 'class' => 'd-inline cancel-partner-order-form']) !!}
                                                    <input type="hidden" name="reason" value="Huy don tu BillHT">
                                                    <button type="submit" class="btn btn-sm btn-danger">Huỷ đồng bộ</button>
                                                {!! Form::close() !!}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center">Không có dữ liệu logs.</td>
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

@section('javascript')
<script type="text/javascript">
    $(function () {
        $('#checkAllCancelableLogs').on('change', function () {
            $('.cancel-log-checkbox').prop('checked', $(this).is(':checked'));
        });

        $('.cancel-log-checkbox').on('change', function () {
            let total = $('.cancel-log-checkbox').length;
            let checked = $('.cancel-log-checkbox:checked').length;
            $('#checkAllCancelableLogs').prop('checked', total > 0 && total === checked);
        });

        $('#bulkCancelPartnerOrdersForm').on('submit', function (event) {
            let checked = $('.cancel-log-checkbox:checked').length;
            if (checked === 0) {
                alert('Bạn vui lòng chọn ít nhất một log cần huỷ.');
                event.preventDefault();
                return;
            }

            if (!confirm('Bạn có chắc muốn huỷ ' + checked + ' đơn đối tác đã chọn? Sau khi huỷ thành công, mã đối tác sẽ bị xoá để có thể đẩy đơn sang đối tác khác.')) {
                event.preventDefault();
            }
        });

        $('.cancel-partner-order-form').on('submit', function (event) {
            if (!confirm('Bạn có chắc muốn huỷ đồng bộ đơn đối tác này? Sau khi huỷ thành công, mã đối tác sẽ bị xoá để có thể đẩy đơn sang đối tác khác.')) {
                event.preventDefault();
            }
        });
    });
</script>
@endsection
