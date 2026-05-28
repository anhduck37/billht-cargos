@extends('layouts.app')

@section('content')
    @include('layouts.headers.cards')

    @php
        $typeLabels = [
            'new' => ['label' => 'Địa chỉ mới', 'class' => 'badge-success'],
            'old' => ['label' => 'Địa chỉ cũ', 'class' => 'badge-info'],
            'mixed' => ['label' => 'Có thể dùng cả 2', 'class' => 'badge-primary'],
            'unknown' => ['label' => 'Không chắc', 'class' => 'badge-warning'],
        ];
    @endphp

    <div class="container-fluid mt--4">
        <div class="row mt-5">
            <div class="col-xl-12 mb-5 mb-xl-0">
                @include('flash::message')

                <div class="card shadow border-0 mb-4">
                    <div class="card-header bg-white border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-1 text-primary">Công cụ kiểm tra địa chỉ Excel</h1>
                                <p class="text-muted mb-0">Upload file để nhận diện địa chỉ mới/cũ và kiểm tra khả năng đẩy Viettel, EMS trước khi import thật.</p>
                            </div>
                            <div class="col-auto">
                                <a href="{{ route('orders.showFormImport') }}" class="btn btn-outline-primary">Quay lại import</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => 'orders.addressImportTool.preview', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
                        @csrf
                        <div class="row align-items-end">
                            <div class="col-md-8">
                                <label class="form-control-label">File Excel</label>
                                <div class="custom-file">
                                    <input type="file" name="file" class="custom-file-input" id="file" required>
                                    <label class="custom-file-label" for="file">Chọn file cần kiểm tra...</label>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3 mt-md-0">
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-search mr-1"></i> Kiểm tra địa chỉ
                                </button>
                            </div>
                        </div>
                        {!! Form::close() !!}
                    </div>
                </div>

                @if($summary)
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Tổng dòng</div>
                                    <div class="h2 mb-0">{{ $summary['total'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Địa chỉ mới / cũ</div>
                                    <div class="h4 mb-0">{{ $summary['new'] }} / {{ $summary['old'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Sẵn sàng VTP / EMS</div>
                                    <div class="h4 mb-0">{{ $summary['vtp_ready'] }} / {{ $summary['ems_ready'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card shadow-sm border-0">
                                <div class="card-body py-3">
                                    <div class="text-muted small">Cần kiểm tra</div>
                                    <div class="h4 mb-0 text-warning">{{ $summary['has_warning'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(!empty($summary['warning_messages']))
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body py-3">
                                <div class="font-weight-bold mb-2">Nhóm lỗi cần xử lý nhanh</div>
                                @foreach($summary['warning_messages'] as $message => $count)
                                    <span class="badge badge-warning mr-2 mb-2">{{ $count }} dòng: {{ $message }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                @if(!empty($rows))
                    <style>
                        .address-tool-table {
                            width: 100%;
                        }
                        .address-tool-table th,
                        .address-tool-table td {
                            white-space: normal;
                            vertical-align: top;
                        }
                        .address-tool-address {
                            max-width: 520px;
                            word-break: break-word;
                        }
                        .address-tool-status {
                            max-width: 260px;
                        }
                        .address-tool-table .badge {
                            white-space: normal;
                            line-height: 1.35;
                        }
                        .address-tool-table td,
                        .address-tool-table th {
                            padding: .75rem .85rem;
                        }
                        .address-tool-details summary {
                            cursor: pointer;
                            color: #5e72e4;
                            font-size: .8rem;
                        }
                    </style>
                    <div class="card shadow border-0">
                        <div class="card-header bg-white border-0">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h2 class="mb-0">Kết quả kiểm tra</h2>
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-sm btn-outline-secondary address-filter" data-filter="all">Tất cả</button>
                                    <button type="button" class="btn btn-sm btn-outline-warning address-filter" data-filter="warning">Cần sửa</button>
                                    <button type="button" class="btn btn-sm btn-outline-success address-filter" data-filter="ready">Sẵn sàng</button>
                                    @if(!empty($summary['download_token']))
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('orders.addressImportTool.download', [$summary['download_token'], 'all']) }}">Tải tất cả</a>
                                        <a class="btn btn-sm btn-primary" href="{{ route('orders.addressImportTool.download', [$summary['download_token'], 'errors']) }}">Tải dòng lỗi</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-items-start table-flush address-tool-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 64px;">Dòng</th>
                                        <th style="width: 170px;">Người nhận</th>
                                        <th>Địa chỉ người nhận</th>
                                        <th style="width: 130px;">Nhận diện</th>
                                        <th style="width: 260px;">Đối tác</th>
                                        <th style="width: 230px;">Cần xử lý</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $row)
                                        @php
                                            $type = $typeLabels[$row['type']] ?? $typeLabels['unknown'];
                                            $hasWarning = !empty($row['warnings']);
                                            $isReady = $row['vtp']['ready'] || $row['ems']['ready'];
                                        @endphp
                                        <tr data-warning="{{ $hasWarning ? 1 : 0 }}" data-ready="{{ $isReady ? 1 : 0 }}">
                                            <td class="font-weight-bold">{{ $row['row'] }}</td>
                                            <td>
                                                <div class="font-weight-bold">{{ $row['receiver_name'] ?: 'N/A' }}</div>
                                                <div class="text-muted small">{{ $row['receiver_phone'] }}</div>
                                                @if($row['partner_code'])
                                                    <span class="badge badge-secondary">{{ $row['partner_code'] }}</span>
                                                @endif
                                            </td>
                                            <td class="address-tool-address">
                                                <div class="mb-2">{{ $row['receiver_address'] ?: 'N/A' }}</div>
                                                <details class="address-tool-details">
                                                    <summary>Chi tiết nhận diện</summary>
                                                    @include('orders.partials.address_import_analysis', ['analysis' => $row['receiver_analysis']])
                                                </details>
                                            </td>
                                            <td>
                                                <span class="badge {{ $type['class'] }}">{{ $type['label'] }}</span>
                                            </td>
                                            <td class="address-tool-status">
                                                @php
                                                    $isVtpSelected = $row['partner_code'] === \App\Models\Order::CODE_VIETTEL_POST;
                                                    $vtpBadge = $row['vtp']['ready'] ? 'badge-success' : ($isVtpSelected ? 'badge-danger' : 'badge-warning');
                                                @endphp
                                                <span class="badge {{ $vtpBadge }}">
                                                    {{ $row['vtp']['ready'] ? 'Sẵn sàng' : ($isVtpSelected ? 'Chưa đủ' : 'Cần bổ sung') }}
                                                </span>
                                                <div class="small mt-1">{{ $row['vtp']['message'] }}</div>
                                                @if($row['vtp']['mode'])
                                                    <div class="text-muted small">Theo {{ $row['vtp']['mode'] === 'new' ? 'mẫu mới' : 'mẫu cũ' }}</div>
                                                @endif
                                                <hr class="my-2">
                                                @php
                                                    $isEmsSelected = $row['partner_code'] === \App\Models\Order::CODE_EMS;
                                                    $emsBadge = $row['ems']['ready'] ? 'badge-success' : ($isEmsSelected ? 'badge-danger' : 'badge-warning');
                                                @endphp
                                                <span class="badge {{ $emsBadge }}">
                                                    {{ $row['ems']['ready'] ? 'Sẵn sàng' : ($isEmsSelected ? 'Chưa đủ' : 'Cần bổ sung') }}
                                                </span>
                                                <div class="small mt-1">{{ $row['ems']['message'] }}</div>
                                                @if($row['ems']['mode'])
                                                    <div class="text-muted small">Theo {{ $row['ems']['mode'] === 'new' ? 'mẫu mới' : 'mẫu cũ' }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                @forelse($row['warnings'] as $warning)
                                                    <div class="text-warning small mb-1">{{ $warning }}</div>
                                                @empty
                                                    <span class="text-success small">Không có cảnh báo</span>
                                                @endforelse
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @elseif($summary)
                    <div class="alert alert-warning">Không tìm thấy dòng dữ liệu hợp lệ trong file.</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $('#file').on('change', function () {
        var fileName = $(this).val().replace('C:\\fakepath\\', '');
        $(this).next('.custom-file-label').html(fileName || 'Chọn file cần kiểm tra...');
    });

    $('.address-filter').on('click', function () {
        var filter = $(this).data('filter');
        $('.address-filter').removeClass('active');
        $(this).addClass('active');

        $('.address-tool-table tbody tr').each(function () {
            var show = true;
            if (filter === 'warning') {
                show = $(this).data('warning') == 1;
            }
            if (filter === 'ready') {
                show = $(this).data('ready') == 1 && $(this).data('warning') != 1;
            }
            $(this).toggle(show);
        });
    });

</script>
@endsection
