@extends('layouts.app')

@section('content')
    @include('layouts.headers.cards')

    <style>
        .smart-import-scroll {
            max-height: calc(100vh - 270px);
            min-height: 420px;
            overflow: auto;
            overscroll-behavior: contain;
        }
        .smart-table {
            width: max-content;
            min-width: 100%;
            margin-bottom: 0;
            table-layout: auto;
        }
        .smart-table th,
        .smart-table td {
            vertical-align: top !important;
            padding: .55rem .45rem;
            white-space: normal;
            word-break: break-word;
        }
        .smart-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            white-space: nowrap;
        }
        .smart-table input,
        .smart-table select,
        .smart-table textarea {
            width: 100%;
            min-width: 0;
            font-size: 12px;
            padding: .35rem .45rem;
        }
        .smart-table textarea {
            min-height: 48px;
            resize: vertical;
        }
        .smart-col-row { width: 58px; min-width: 58px; }
        .smart-col-status { width: 98px; min-width: 98px; }
        .smart-col-message { width: 220px; min-width: 220px; max-width: 260px; }
        .smart-col-code { width: 125px; min-width: 125px; }
        .smart-col-name { width: 145px; min-width: 145px; }
        .smart-col-phone { width: 120px; min-width: 120px; }
        .smart-col-address { width: 230px; min-width: 230px; max-width: 280px; }
        .smart-col-receiver-address { width: 360px; min-width: 360px; max-width: 440px; }
        .smart-col-service { width: 145px; min-width: 145px; }
        .smart-col-weight { width: 95px; min-width: 95px; }
        .smart-col-note { width: 220px; min-width: 220px; max-width: 280px; }
        .smart-col-partner { width: 110px; min-width: 110px; }
        .smart-col-action { width: 120px; min-width: 120px; }
        .smart-analysis-block {
            max-height: 190px;
            overflow: auto;
            line-height: 1.45;
            margin-top: .45rem;
            padding: .55rem;
            border: 1px solid rgba(94, 114, 228, .18);
            border-radius: 6px;
            background: rgba(255,255,255,.72);
        }
        .smart-analysis-item + .smart-analysis-item {
            margin-top: .45rem;
            padding-top: .45rem;
            border-top: 1px dashed rgba(0,0,0,.12);
        }
        .smart-analysis-address {
            color: #172b4d;
        }
        .smart-analysis-meta {
            color: #6b7c93;
            font-size: 11px;
        }
        .smart-sticky {
            position: sticky;
            left: 0;
            background: inherit;
            z-index: 4;
            box-shadow: 1px 0 0 rgba(0,0,0,.08);
        }
        thead .smart-sticky {
            background: #f6f9fc;
            z-index: 5;
        }
        .smart-error { background: #fff5f5; }
        .smart-valid { background: #f4fff8; }
        .smart-imported { background: #f7f8ff; }
        .smart-summary-card { border-radius: 6px; background: #fff; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
        @media (max-width: 768px) {
            .smart-import-scroll {
                max-height: calc(100vh - 220px);
                min-height: 360px;
            }
            .smart-table th,
            .smart-table td {
                padding: .45rem .35rem;
            }
            .smart-col-message,
            .smart-col-address,
            .smart-col-note,
            .smart-col-receiver-address {
                min-width: 210px;
            }
        }
    </style>

    <div class="container-fluid mt--4">
        <div class="row mt-5">
            <div class="col-xl-12">
                @include('flash::message')

                <div class="card shadow border-0 mb-4">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h1 class="mb-1 text-primary">Preview import thông minh</h1>
                                <p class="text-muted mb-0">{{ $batch->file_name }} - {{ $batch->created_at ? $batch->created_at->format('d/m/Y H:i') : '' }}</p>
                            </div>
                            <div>
                                <a href="{{ route('orders.smartImport.index') }}" class="btn btn-outline-primary">Upload file khác</a>
                                <button data-toggle="modal" data-target="#openModalPrint" type="button" class="btn btn-outline-info" @if($batch->rows()->whereNotNull('order_id')->count() === 0) disabled @endif>
                                    <i class="fas fa-print mr-1"></i> In đơn đã import
                                </button>
                                {!! Form::open(['route' => ['orders.smartImport.confirm', $batch->id], 'method' => 'POST', 'style' => 'display:inline']) !!}
                                @csrf
                                <button type="submit" class="btn btn-success" @if($batch->valid_rows === 0) disabled @endif>Import dòng hợp lệ</button>
                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                    <div class="card-body bg-light">
                        <div class="row">
                            <div class="col-md-3 mb-3"><div class="smart-summary-card"><div class="text-muted">Tổng dòng</div><h2>{{ $batch->total_rows }}</h2></div></div>
                            <div class="col-md-3 mb-3"><div class="smart-summary-card"><div class="text-muted">Hợp lệ</div><h2 class="text-success">{{ $batch->valid_rows }}</h2></div></div>
                            <div class="col-md-3 mb-3"><div class="smart-summary-card"><div class="text-muted">Còn lỗi</div><h2 class="text-danger">{{ $batch->error_rows }}</h2></div></div>
                            <div class="col-md-3 mb-3"><div class="smart-summary-card"><div class="text-muted">Đã import</div><h2 class="text-info">{{ $batch->imported_rows }}</h2></div></div>
                        </div>
                        <div class="btn-group">
                            <a class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('orders.smartImport.show', $batch->id) }}">Tất cả</a>
                            <a class="btn btn-sm {{ $status === 'error' ? 'btn-danger' : 'btn-outline-danger' }}" href="{{ route('orders.smartImport.show', ['batch' => $batch->id, 'status' => 'error']) }}">Dòng lỗi</a>
                            <a class="btn btn-sm {{ $status === 'valid' ? 'btn-success' : 'btn-outline-success' }}" href="{{ route('orders.smartImport.show', ['batch' => $batch->id, 'status' => 'valid']) }}">Hợp lệ</a>
                            <a class="btn btn-sm {{ $status === 'imported' ? 'btn-info' : 'btn-outline-info' }}" href="{{ route('orders.smartImport.show', ['batch' => $batch->id, 'status' => 'imported']) }}">Đã import</a>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0">
                    <div class="smart-import-scroll">
                        <table class="table table-bordered align-items-start smart-table">
                            <thead class="thead-light">
                                <tr>
                                    <th class="smart-sticky smart-col-row">Dòng</th>
                                    <th class="smart-col-status">Trạng thái</th>
                                    <th class="smart-col-message">Lỗi / cảnh báo</th>
                                    <th class="smart-col-code">Mã đơn</th>
                                    <th class="smart-col-name">Người gửi</th>
                                    <th class="smart-col-phone">SĐT gửi</th>
                                    <th class="smart-col-address">Địa chỉ gửi</th>
                                    <th class="smart-col-name">Người nhận</th>
                                    <th class="smart-col-phone">SĐT nhận</th>
                                    <th class="smart-col-receiver-address">Địa chỉ nhận</th>
                                    <th class="smart-col-service">Dịch vụ trong nước</th>
                                    <th class="smart-col-weight">Trọng lượng</th>
                                    <th class="smart-col-note">Nội dung</th>
                                    <th class="smart-col-partner">Đối tác</th>
                                    <th class="smart-col-action">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    @php
                                        $data = $row->editable_data ?: [];
                                        $analysis = $row->analysis ?: [];
                                        $rowClass = $row->status === 'error' ? 'smart-error' : ($row->status === 'imported' ? 'smart-imported' : 'smart-valid');
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        {!! Form::open(['route' => ['orders.smartImport.rows.update', $batch->id, $row->id], 'method' => 'POST']) !!}
                                        @csrf
                                        <td class="smart-sticky smart-col-row font-weight-bold">{{ $row->row_number }}</td>
                                        <td class="smart-col-status">
                                            @if($row->status === 'error')
                                                <span class="badge badge-danger">Lỗi</span>
                                            @elseif($row->status === 'imported')
                                                <span class="badge badge-info">Đã import</span>
                                                @if($row->order)
                                                    <div><a href="{{ route('orders.edit', $row->order_id) }}" target="_blank">{{ $row->order->order_code }}</a></div>
                                                @endif
                                            @else
                                                <span class="badge badge-success">Hợp lệ</span>
                                            @endif
                                        </td>
                                        <td class="smart-col-message">
                                            @foreach(($row->errors ?: []) as $error)
                                                <div class="text-danger small">- {{ $error }}</div>
                                            @endforeach
                                            @foreach(($row->warnings ?: []) as $warning)
                                                <div class="text-warning small">- {{ $warning }}</div>
                                            @endforeach
                                        </td>
                                        <td class="smart-col-code"><input class="form-control form-control-sm" name="row[invoice_code]" value="{{ $data['invoice_code'] ?? '' }}"></td>
                                        <td class="smart-col-name"><input class="form-control form-control-sm" name="row[sender_name]" value="{{ $data['sender_name'] ?? '' }}"></td>
                                        <td class="smart-col-phone"><input class="form-control form-control-sm" name="row[sender_phone]" value="{{ $data['sender_phone'] ?? '' }}"></td>
                                        <td class="smart-col-address"><textarea class="form-control form-control-sm" name="row[sender_address]">{{ $data['sender_address'] ?? '' }}</textarea></td>
                                        <td class="smart-col-name"><input class="form-control form-control-sm" name="row[receiver_name]" value="{{ $data['receiver_name'] ?? '' }}"></td>
                                        <td class="smart-col-phone"><input class="form-control form-control-sm" name="row[receiver_phone]" value="{{ $data['receiver_phone'] ?? '' }}"></td>
                                        <td class="smart-col-receiver-address">
                                            <textarea class="form-control form-control-sm" name="row[receiver_address]">{{ $data['receiver_address'] ?? '' }}</textarea>
                                            @php
                                                $addressAnalysis = $analysis['receiver'] ?? [];
                                                $schemeLabel = ($addressAnalysis['scheme'] ?? '') === 'new' ? 'Địa chỉ mới' : (($addressAnalysis['scheme'] ?? '') === 'old' ? 'Địa chỉ cũ' : 'Chưa nhận diện');
                                                $vtpCodes = $addressAnalysis['vtp_codes'] ?? null;
                                                $emsCodes = $addressAnalysis['ems_codes'] ?? null;
                                                if (($addressAnalysis['scheme'] ?? '') === 'new' && (!empty($addressAnalysis['new_ward_id']) || !empty($addressAnalysis['new_province_id']))) {
                                                    $newWard = !empty($addressAnalysis['new_ward_id']) ? \App\NewWard::with('newProvince')->find($addressAnalysis['new_ward_id']) : null;
                                                    $newProvince = !empty($addressAnalysis['new_province_id']) ? \App\NewProvince::find($addressAnalysis['new_province_id']) : null;
                                                    $addressAnalysis['ward_name'] = $addressAnalysis['ward_name'] ?? optional($newWard)->name;
                                                    if (empty($addressAnalysis['province_name'])) {
                                                        $addressAnalysis['province_name'] = optional(optional($newWard)->newProvince)->name ?: optional($newProvince)->name;
                                                    }
                                                    $addressAnalysis['display_address'] = trim(($addressAnalysis['detail_address'] ?? '') . ', ' . ($addressAnalysis['ward_name'] ?? '') . ', ' . ($addressAnalysis['province_name'] ?? ''), ' ,');
                                                }
                                                if (($addressAnalysis['scheme'] ?? '') === 'old' && (!empty($addressAnalysis['ward_id']) || !empty($addressAnalysis['district_id']) || !empty($addressAnalysis['city_id']))) {
                                                    $city = !empty($addressAnalysis['city_id']) ? \App\City::find($addressAnalysis['city_id']) : null;
                                                    $district = !empty($addressAnalysis['district_id']) ? \App\District::find($addressAnalysis['district_id']) : null;
                                                    $ward = !empty($addressAnalysis['ward_id']) ? \App\Ward::find($addressAnalysis['ward_id']) : null;
                                                    $addressAnalysis['ward_name'] = $addressAnalysis['ward_name'] ?? optional($ward)->ward_name;
                                                    $addressAnalysis['district_name'] = $addressAnalysis['district_name'] ?? optional($district)->district_name;
                                                    $addressAnalysis['province_name'] = $addressAnalysis['province_name'] ?? optional($city)->city_name;
                                                    $addressAnalysis['display_address'] = trim(($addressAnalysis['detail_address'] ?? '') . ', ' . ($addressAnalysis['ward_name'] ?? '') . ', ' . ($addressAnalysis['district_name'] ?? '') . ', ' . ($addressAnalysis['province_name'] ?? ''), ' ,');
                                                }
                                            @endphp
                                            <div class="smart-analysis-block small">
                                                <div class="smart-analysis-item">
                                                    <div><strong>Người nhận:</strong> <span class="badge {{ !empty($addressAnalysis['parsed']) ? 'badge-success' : 'badge-danger' }}">{{ $schemeLabel }}</span></div>
                                                    @if(!empty($addressAnalysis['detail_address']))
                                                        <div class="smart-analysis-meta"><strong>Số nhà/đường:</strong> {{ $addressAnalysis['detail_address'] }}</div>
                                                    @endif
                                                    @if(!empty($addressAnalysis['ward_name']))
                                                        <div class="smart-analysis-meta"><strong>Xã/Phường:</strong> {{ $addressAnalysis['ward_name'] }}</div>
                                                    @endif
                                                    @if(!empty($addressAnalysis['district_name']))
                                                        <div class="smart-analysis-meta"><strong>Huyện/Quận:</strong> {{ $addressAnalysis['district_name'] }}</div>
                                                    @endif
                                                    @if(!empty($addressAnalysis['province_name']))
                                                        <div class="smart-analysis-meta"><strong>Tỉnh/Thành phố:</strong> {{ $addressAnalysis['province_name'] }}</div>
                                                    @endif
                                                    @if($vtpCodes)
                                                        <div class="smart-analysis-meta"><strong>Mã VTP:</strong> Tỉnh {{ $vtpCodes['province'] ?? 'N/A' }}, Huyện {{ $vtpCodes['district'] ?? 'N/A' }}, Xã {{ $vtpCodes['ward'] ?? 'N/A' }}</div>
                                                    @endif
                                                    @if($emsCodes)
                                                        <div class="smart-analysis-meta"><strong>Mã EMS:</strong> Tỉnh {{ $emsCodes['province'] ?? 'N/A' }}, Huyện {{ $emsCodes['district'] ?? 'N/A' }}, Xã {{ $emsCodes['ward'] ?? 'N/A' }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="smart-col-service"><input class="form-control form-control-sm" name="row[service_domestic]" value="{{ $data['service_domestic'] ?? '' }}" placeholder="Chuyển phát nhanh"></td>
                                        <td class="smart-col-weight"><input class="form-control form-control-sm" name="row[weight]" value="{{ $data['weight'] ?? '' }}"></td>
                                        <td class="smart-col-note"><textarea class="form-control form-control-sm" name="row[note]">{{ $data['note'] ?? '' }}</textarea></td>
                                        <td class="smart-col-partner">
                                            <select class="form-control form-control-sm" name="row[partner_code]">
                                                <option value="" @if(($data['partner_code'] ?? '') === '') selected @endif>Không đẩy</option>
                                                <option value="VTP" @if(($data['partner_code'] ?? '') === 'VTP') selected @endif>Viettel</option>
                                                <option value="EMS" @if(($data['partner_code'] ?? '') === 'EMS') selected @endif>EMS</option>
                                            </select>
                                        </td>
                                        <td class="smart-col-action">
                                            @foreach(['order_date','department','payment_method','service_extra','person_charge','quantity','type','total','collection'] as $hidden)
                                                <input type="hidden" name="row[{{ $hidden }}]" value="{{ $data[$hidden] ?? '' }}">
                                            @endforeach
                                            <input type="hidden" name="status" value="{{ $status }}">
                                            <button type="submit" class="btn btn-sm btn-primary mb-2">Lưu & kiểm tra</button>
                                        </td>
                                        {!! Form::close() !!}
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                        {{ $rows->appends(['status' => $status])->links() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="openModalPrint" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h3 class="modal-title text-white">Chọn số liên in</h3>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="custom-control custom-radio mb-3">
                            <input name="number" class="custom-control-input" id="number1" type="radio" value="1">
                            <label class="custom-control-label" for="number1">In 1 liên</label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input name="number" class="custom-control-input" id="number2" checked type="radio" value="2">
                            <label class="custom-control-label" for="number2">In 2 liên</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link text-muted" data-dismiss="modal">Hủy</button>
                        <button type="button" id="printSmartBatch" class="btn btn-primary">Xác nhận in</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script type="text/javascript" src="{{ asset('js/render-print.js') }}?v={{ time() }}"></script>
<script>
    var smartImportOrderIds = {!! json_encode($batch->rows()->whereNotNull('order_id')->pluck('order_id')->values()) !!};
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    $('#printSmartBatch').on('click', function () {
        var number = $('input[name="number"]:checked').val();
        $.ajax({
            type: 'POST',
            url: '/template/render',
            data: { order: smartImportOrderIds, number: number },
            success: function (res) {
                var html = renderHtml(res);
                var printDocument = window.open();
                printDocument.document.write(html);
                printDocument.document.close();
            }
        });
    });
</script>
@endsection
