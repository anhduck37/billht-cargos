@extends('layouts.app', ['title' => __('Lịch sử thao tác')])

@section('content')
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
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
                            <h3 class="mb-0">Lịch sử thao tác vận đơn</h3>
                        </div>
                    </div>
                    
                    <!-- Filter bar -->
                    <form action="{{ route('order_historys.index') }}" method="GET" class="form-inline mt-3">
                        <div class="form-group mr-2">
                            <label for="search_date" class="form-control-label mr-2">Thời gian:</label>
                            <input type="text" name="search_date" id="search_date" value="{{ request('search_date') }}" class="form-control form-control-sm" placeholder="DD/MM/YYYY - DD/MM/YYYY" style="min-width: 200px;">
                            <div class="ml-2 btn-group">
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-date" data-range="1">1T</button>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-date" data-range="3">3T</button>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-date" data-range="6">6T</button>
                                <button type="button" class="btn btn-outline-primary btn-sm btn-quick-date" data-range="12">12T</button>
                            </div>
                        </div>
                        
                        <div class="form-group mr-2">
                            <label for="action" class="form-control-label mr-2">Hành động:</label>
                            <select name="action" id="action" class="form-control form-control-sm">
                                <option value="">Tất cả</option>
                                <option value="CREATE" {{ request('action') === 'CREATE' ? 'selected' : '' }}>Tạo mới</option>
                                <option value="UPDATE" {{ request('action') === 'UPDATE' ? 'selected' : '' }}>Cập nhật</option>
                                <option value="DELETE" {{ request('action') === 'DELETE' ? 'selected' : '' }}>Xóa</option>
                                <option value="SYNC" {{ request('action') === 'SYNC' ? 'selected' : '' }}>Đồng bộ</option>
                            </select>
                        </div>

                        <div class="form-group mr-2">
                            <label for="search" class="form-control-label mr-2">Mã vận đơn:</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nhập mã vận đơn...">
                        </div>
                        
                        <div class="form-group mr-2">
                            <label for="email" class="form-control-label mr-2">Email người cập nhật:</label>
                            <input type="text" name="email" id="email" value="{{ request('email') }}" class="form-control form-control-sm" placeholder="Nhập email...">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                        @if(request()->has('search_date') || request()->has('action') || request()->has('search') || request()->has('email'))
                            <a href="{{ route('order_historys.index') }}" class="btn btn-secondary btn-sm ml-1">Làm mới</a>
                        @endif
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">Mã vân đơn / Tham chiếu</th>
                                <th scope="col">Hành Động</th>
                                <th scope="col">Chi tiết</th>
                                <th scope="col">Người thao tác</th>
                                <th scope="col">Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($orderHistorys) > 0)
                                @foreach($orderHistorys as $history)
                                    <tr>
                                        <td>
                                            @if($history->order && $history->order->order_code)
                                                <a href="{{ route('orders.edit', $history->order_id) }}" target="_blank" class="font-weight-bold">
                                                    {{ $history->order->order_code }}
                                                </a>
                                            @elseif($history->tracking_code)
                                                {{ $history->tracking_code }}
                                            @else
                                                #{{ $history->order_id }} 
                                            @endif
                                            @if($history->partner_name)
                                            <br><small class="text-muted">({{ $history->partner_name }})</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($history->type_order == \App\OrderHistory::TYPE_ORDER_CREATE)
                                                <span class="badge badge-success">Tạo mới</span>
                                            @elseif($history->type_order == \App\OrderHistory::TYPE_ORDER_UPDATE)
                                                <span class="badge badge-info">Cập nhật</span>
                                            @elseif($history->action == 'DELETE')
                                                <span class="badge badge-danger">Xóa</span>
                                            @elseif($history->action == 'SYNC')
                                                <span class="badge badge-warning">Đồng bộ</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $history->action ?? 'Thao tác' }}</span>
                                            @endif
                                        </td>
                                        <td style="white-space: normal; max-width: 400px; font-size: 0.85rem">
                                            @if($history->data)
                                                @php
                                                    $data = json_decode($history->data, true);
                                                    $isError = isset($data['error']) || isset($data['message']);
                                                @endphp
                                                @if(is_array($data))
                                                    @if(isset($data['action_desc']))
                                                        <strong>{{ $data['action_desc'] }}</strong><br>
                                                    @endif
                                                    
                                                    @if(isset($data['error']))
                                                        <span class="text-danger">Lỗi: {{ is_string($data['error']) ? $data['error'] : json_encode($data['error'], JSON_UNESCAPED_UNICODE) }}</span><br>
                                                    @endif
                                                    
                                                    @if(isset($data['message']))
                                                        <span class="text-info">{{ $data['message'] }}</span><br>
                                                    @endif
                                                    
                                                    @if(isset($data['changes']))
                                                        <ul class="pl-3 mb-0">
                                                        @foreach($data['changes'] as $key => $change)
                                                            <li>Thay đổi <strong>{{ $key }}</strong>: <del>{{ $change['old'] ?? '...' }}</del> <i class="fas fa-arrow-right text-muted mx-1"></i> <span class="text-success">{{ $change['new'] ?? '...' }}</span></li>
                                                        @endforeach
                                                        </ul>
                                                    @endif
                                                @else
                                                    {{ $history->data }}
                                                @endif
                                            @else
                                                <em>Không có chi tiết dữ liệu</em>
                                            @endif
                                        </td>
                                        <td>
                                            @if($history->user)
                                                {{ $history->user->name }}
                                                <br><small class="text-muted">{{ $history->user->email }}</small>
                                            @else
                                                Hệ thống
                                            @endif
                                            @if($history->user_level)
                                            <br><small class="text-muted">Lev: {{ $history->user_level }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($history->created_at)->format('H:i d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center">Không có lịch sử thao tác.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer py-4">
                    <div class="d-flex justify-content-end">
                        {{ $orderHistorys->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

<script>
$(function() {
    $('#search_date').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Xóa',
            applyLabel: 'Áp dụng',
            format: 'DD/MM/YYYY',
            daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
            monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
        }
    });

    // Populate daterangepicker with current value if exists
    var currentVal = $('#search_date').val();
    if (currentVal) {
        var dates = currentVal.split(' - ');
        if (dates.length == 2) {
            $('#search_date').data('daterangepicker').setStartDate(moment(dates[0], 'DD/MM/YYYY'));
            $('#search_date').data('daterangepicker').setEndDate(moment(dates[1], 'DD/MM/YYYY'));
        }
    }

    $('#search_date').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
    });

    $('#search_date').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });

    $('.btn-quick-date').on('click', function() {
        var months = $(this).data('range');
        var start = moment().subtract(months, 'months').startOf('day');
        var end = moment().endOf('day');
        
        $('#search_date').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
        $(this).closest('form').submit();
    });
});
</script>
@endpush
