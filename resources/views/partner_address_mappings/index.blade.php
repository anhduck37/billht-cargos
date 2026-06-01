@extends('layouts.app')

@section('content')
<div class="header bg-gradient-primary pb-8 pt-5 pt-md-8">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row">
                <div class="col">
                    <h1 class="text-white mb-0">Mapping API địa chỉ</h1>
                    <p class="text-white-50 mb-0">Bổ sung mapping thủ công cho Viettel và EMS theo địa danh mới.</p>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-sm btn-neutral mb-2 mb-md-0" data-toggle="modal" data-target="#mappingGuideModal">
                        Hướng dẫn sử dụng
                    </button>
                    <a href="{{ route('order_partner_logs.index') }}" class="btn btn-sm btn-neutral">Quay lại đồng bộ</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--7">
    <div class="row">
        <div class="col">
            @include('flash::message')
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="card shadow">
                <div class="card-header border-0">
                    <h3 class="mb-0">Công cụ chuyển đổi địa chỉ</h3>
                    <p class="text-muted text-sm mb-0">Tra cứu nhanh địa chỉ cũ/mới dựa trên mapping VTP/EMS đã lưu trong hệ thống.</p>
                </div>
                <div class="card-body">
                    <ul class="nav nav-pills mb-3 address-convert-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="old-to-new-tab" data-toggle="tab" href="#old-to-new-panel" role="tab">Cũ → Mới</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="new-to-old-tab" data-toggle="tab" href="#new-to-old-panel" role="tab">Mới → Cũ</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="old-to-new-panel" role="tabpanel">
                            <div class="form-row">
                                <div class="form-group col-lg-3 col-md-6">
                                    <label>Tỉnh/Thành phố cũ</label>
                                    <select id="convert_old_city_id" class="form-control">
                                        <option value="">Vui lòng chọn...</option>
                                        @foreach($legacyCities as $city)
                                            <option value="{{ $city->id }}">{{ $city->city_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-3 col-md-6">
                                    <label>Huyện/Quận cũ</label>
                                    <select id="convert_old_district_id" class="form-control">
                                        <option value="">Vui lòng chọn tỉnh trước...</option>
                                    </select>
                                </div>
                                <div class="form-group col-lg-3 col-md-6">
                                    <label>Xã/Phường cũ</label>
                                    <select id="convert_old_ward_id" class="form-control">
                                        <option value="">Vui lòng chọn huyện trước...</option>
                                    </select>
                                </div>
                                <div class="form-group col-lg-3 col-md-6">
                                    <label>Đối tác</label>
                                    <select id="convert_old_partner_code" class="form-control">
                                        <option value="">Tất cả</option>
                                        <option value="VTP">Viettel</option>
                                        <option value="EMS">EMS</option>
                                    </select>
                                </div>
                            </div>
                            <button type="button" id="convert_old_to_new_btn" class="btn btn-primary">Chuyển đổi</button>
                            <div id="old_to_new_result" class="mt-3"></div>
                        </div>

                        <div class="tab-pane fade" id="new-to-old-panel" role="tabpanel">
                            <div class="form-row">
                                <div class="form-group col-lg-4 col-md-6">
                                    <label>Tỉnh/Thành phố mới</label>
                                    <select id="convert_new_province_id" class="form-control">
                                        <option value="">Vui lòng chọn...</option>
                                        @foreach($newProvinces as $province)
                                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-lg-4 col-md-6">
                                    <label>Xã/Phường mới</label>
                                    <select id="convert_new_ward_id" class="form-control">
                                        <option value="">Vui lòng chọn tỉnh trước...</option>
                                    </select>
                                </div>
                                <div class="form-group col-lg-4 col-md-6">
                                    <label>Đối tác</label>
                                    <select id="convert_new_partner_code" class="form-control">
                                        <option value="">Tất cả</option>
                                        <option value="VTP">Viettel</option>
                                        <option value="EMS">EMS</option>
                                    </select>
                                </div>
                            </div>
                            <button type="button" id="convert_new_to_old_btn" class="btn btn-primary">Chuyển đổi</button>
                            <div id="new_to_old_result" class="mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="card shadow">
                <div class="card-header border-0">
                    <h3 class="mb-0">Tra mã địa chỉ cũ</h3>
                    <p class="text-muted text-sm mb-0">Chọn tỉnh, huyện, xã cũ để lấy mã VTP/EMS điền vào form mapping.</p>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Tỉnh/Thành phố cũ</label>
                            <select id="legacy_city_id" class="form-control">
                                <option value="">Vui lòng chọn...</option>
                                @foreach($legacyCities as $city)
                                    <option value="{{ $city->id }}">{{ $city->city_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Huyện/Quận cũ</label>
                            <select id="legacy_district_id" class="form-control">
                                <option value="">Vui lòng chọn tỉnh trước...</option>
                            </select>
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Xã/Phường cũ</label>
                            <select id="legacy_ward_id" class="form-control">
                                <option value="">Vui lòng chọn huyện trước...</option>
                            </select>
                        </div>
                        <div class="form-group col-lg-3 col-md-6">
                            <label>Đối tác cần dùng mã</label>
                            <select id="legacy_partner_code" class="form-control">
                                <option value="VTP">Viettel</option>
                                <option value="EMS">EMS</option>
                            </select>
                        </div>
                    </div>

                    <div id="legacy_code_result" class="border rounded p-3 mt-2" style="display: none;">
                        <div class="row">
                            <div class="col-lg-4 mb-2">
                                <div class="text-muted text-sm">Địa chỉ cũ</div>
                                <div id="legacy_address_text" class="font-weight-bold"></div>
                            </div>
                            <div class="col-lg-3 mb-2">
                                <div class="text-muted text-sm">Mã VTP</div>
                                <div>Tỉnh: <strong id="legacy_vtp_province"></strong></div>
                                <div>Huyện: <strong id="legacy_vtp_district"></strong></div>
                                <div>Xã: <strong id="legacy_vtp_ward"></strong></div>
                            </div>
                            <div class="col-lg-3 mb-2">
                                <div class="text-muted text-sm">Mã EMS</div>
                                <div>Tỉnh: <strong id="legacy_ems_province"></strong></div>
                                <div>Huyện: <strong id="legacy_ems_district"></strong></div>
                                <div>Xã: <strong id="legacy_ems_ward"></strong></div>
                            </div>
                            <div class="col-lg-2 d-flex align-items-end">
                                <button type="button" id="use_selected_legacy_code" class="btn btn-info btn-block">Dùng mã này</button>
                            </div>
                        </div>
                    </div>
                    <div id="legacy_code_error" class="alert alert-warning mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col">
            <div class="card shadow">
                <div class="card-header border-0">
                    <h3 class="mb-0">Thêm / cập nhật mapping</h3>
                    <p class="text-muted text-sm mb-0">Nếu mapping đã tồn tại theo Xã/Phường + Đối tác, hệ thống sẽ cập nhật lại mã.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('partner_address_mappings.store') }}" id="mappingForm">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-lg-2 col-md-6">
                                <label>Đối tác</label>
                                <select name="partner_code" id="partner_code" class="form-control" required>
                                    <option value="VTP">Viettel</option>
                                    <option value="EMS">EMS</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-3 col-md-6">
                                <label>Tỉnh/Thành phố mới</label>
                                <select id="mapping_province_id" class="form-control" required>
                                    <option value="">Vui lòng chọn...</option>
                                    @foreach($newProvinces as $province)
                                        <option value="{{ $province->id }}">{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-lg-3 col-md-6">
                                <label>Xã/Phường mới</label>
                                <select name="new_ward_id" id="mapping_ward_id" class="form-control" required>
                                    <option value="">Vui lòng chọn tỉnh trước...</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-2 col-md-6">
                                <label>Trạng thái</label>
                                <select name="mapping_status" id="mapping_status" class="form-control" required>
                                    <option value="mapped">Đã map</option>
                                    <option value="manual_review">Cần kiểm tra</option>
                                    <option value="missing">Thiếu mapping</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-lg-2 col-md-4">
                                <label>Mã tỉnh đối tác</label>
                                <input type="text" name="partner_province_code" id="partner_province_code" class="form-control" required>
                            </div>
                            <div class="form-group col-lg-2 col-md-4">
                                <label>Mã huyện đối tác</label>
                                <input type="text" name="partner_district_code" id="partner_district_code" class="form-control">
                            </div>
                            <div class="form-group col-lg-2 col-md-4">
                                <label>Mã xã đối tác</label>
                                <input type="text" name="partner_ward_code" id="partner_ward_code" class="form-control">
                            </div>
                            <div class="form-group col-lg-4 col-md-8">
                                <label>Ghi chú</label>
                                <input type="text" name="note" id="mapping_note" class="form-control" placeholder="VD: Map thủ công theo mã cũ EMS/VTP">
                            </div>
                            <div class="form-group col-lg-2 col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">Lưu mapping</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="card shadow">
                <div class="card-header border-0">
                    <h3 class="mb-3">Danh sách mapping</h3>
                    <form method="GET" action="{{ route('partner_address_mappings.index') }}">
                        <div class="form-row">
                            <div class="form-group col-lg-2 col-md-6">
                                <label>Từ khóa</label>
                                <input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control form-control-sm" placeholder="Xã, tỉnh, mã đối tác">
                            </div>
                            <div class="form-group col-lg-2 col-md-6">
                                <label>Đối tác</label>
                                <select name="partner_code" class="form-control form-control-sm">
                                    <option value="">Tất cả</option>
                                    <option value="VTP" {{ request('partner_code') === 'VTP' ? 'selected' : '' }}>Viettel</option>
                                    <option value="EMS" {{ request('partner_code') === 'EMS' ? 'selected' : '' }}>EMS</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-3 col-md-6">
                                <label>Tỉnh/Thành phố mới</label>
                                <select name="new_province_id" id="filter_province_id" class="form-control form-control-sm">
                                    <option value="">Tất cả</option>
                                    @foreach($newProvinces as $province)
                                        <option value="{{ $province->id }}" {{ (string)request('new_province_id') === (string)$province->id ? 'selected' : '' }}>{{ $province->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-lg-3 col-md-6">
                                <label>Xã/Phường mới</label>
                                <select name="new_ward_id" id="filter_ward_id" class="form-control form-control-sm">
                                    <option value="">Tất cả</option>
                                    @foreach($selectedWards as $ward)
                                        <option value="{{ $ward->id }}" {{ (string)request('new_ward_id') === (string)$ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-lg-2 col-md-6">
                                <label>Trạng thái</label>
                                <select name="mapping_status" class="form-control form-control-sm">
                                    <option value="">Tất cả</option>
                                    <option value="mapped" {{ request('mapping_status') === 'mapped' ? 'selected' : '' }}>Đã map</option>
                                    <option value="manual_review" {{ request('mapping_status') === 'manual_review' ? 'selected' : '' }}>Cần kiểm tra</option>
                                    <option value="missing" {{ request('mapping_status') === 'missing' ? 'selected' : '' }}>Thiếu mapping</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">Lọc</button>
                        <a href="{{ route('partner_address_mappings.index') }}" class="btn btn-sm btn-secondary">Làm mới</a>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>Đối tác</th>
                                <th>Tỉnh mới</th>
                                <th>Xã/Phường mới</th>
                                <th>Mã tỉnh</th>
                                <th>Mã huyện</th>
                                <th>Mã xã</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                                <th>Cập nhật</th>
                                <th class="text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mappings as $mapping)
                                <tr>
                                    <td><span class="badge {{ $mapping->partner_code === 'EMS' ? 'badge-info' : 'badge-warning' }}">{{ $mapping->partner_code === 'VTP' ? 'Viettel' : $mapping->partner_code }}</span></td>
                                    <td>{{ optional($mapping->newProvince)->name }}</td>
                                    <td>{{ optional($mapping->newWard)->name }}</td>
                                    <td>{{ $mapping->partner_province_code }}</td>
                                    <td>{{ $mapping->partner_district_code }}</td>
                                    <td>{{ $mapping->partner_ward_code }}</td>
                                    <td>
                                        @if($mapping->mapping_status === 'mapped')
                                            <span class="badge badge-success">Đã map</span>
                                        @elseif($mapping->mapping_status === 'manual_review')
                                            <span class="badge badge-warning">Cần kiểm tra</span>
                                        @else
                                            <span class="badge badge-danger">Thiếu mapping</span>
                                        @endif
                                    </td>
                                    <td style="white-space: normal; max-width: 260px;">{{ $mapping->note }}</td>
                                    <td>{{ $mapping->updated_at ? $mapping->updated_at->format('H:i d/m/Y') : '' }}</td>
                                    <td class="text-right">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary load-mapping"
                                            data-partner="{{ $mapping->partner_code }}"
                                            data-province="{{ $mapping->new_province_id }}"
                                            data-ward="{{ $mapping->new_ward_id }}"
                                            data-province-code="{{ $mapping->partner_province_code }}"
                                            data-district-code="{{ $mapping->partner_district_code }}"
                                            data-ward-code="{{ $mapping->partner_ward_code }}"
                                            data-status="{{ $mapping->mapping_status }}"
                                            data-note="{{ $mapping->note }}">
                                            Nạp vào form
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">Chưa có dữ liệu mapping.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer py-4">
                    <div class="d-flex justify-content-end">
                        {{ $mappings->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mappingGuideModal" tabindex="-1" role="dialog" aria-labelledby="mappingGuideModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="mappingGuideModalTitle">Hướng dẫn sử dụng Mapping API</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h4>Khi nào cần dùng?</h4>
                <p>Dùng khi đơn địa chỉ mới không đẩy được Viettel hoặc EMS vì thiếu mapping mã tỉnh/huyện/xã của đối tác.</p>

                <h4>Cách thêm mapping mới</h4>
                <ol class="pl-3">
                    <li>Chọn đối tác: Viettel hoặc EMS.</li>
                    <li>Chọn Tỉnh/Thành phố mới, sau đó chọn Xã/Phường mới.</li>
                    <li>Dùng khối <strong>Tra mã địa chỉ cũ</strong> để chọn tỉnh, huyện, xã cũ.</li>
                    <li>Chọn đối tác cần dùng mã và bấm <strong>Dùng mã này</strong> để tự điền mã vào form.</li>
                    <li>Chọn trạng thái <strong>Đã map</strong> và bấm <strong>Lưu mapping</strong>.</li>
                </ol>

                <h4>Cách sửa mapping đã có</h4>
                <ol class="pl-3">
                    <li>Tìm mapping trong danh sách bên dưới.</li>
                    <li>Bấm <strong>Nạp vào form</strong>.</li>
                    <li>Sửa mã đối tác hoặc ghi chú.</li>
                    <li>Bấm <strong>Lưu mapping</strong> để cập nhật.</li>
                </ol>

                <h4>Lưu ý theo đối tác</h4>
                <ul class="pl-3">
                    <li><strong>Viettel:</strong> cần đủ mã tỉnh, mã huyện, mã xã.</li>
                    <li><strong>EMS:</strong> nên nhập đủ cả 3 mã để tránh lỗi khi EMS kiểm tra sâu địa chỉ.</li>
                    <li>Mỗi Xã/Phường mới chỉ có một mapping cho từng đối tác. Lưu lại sẽ cập nhật bản ghi cũ nếu đã tồn tại.</li>
                </ul>

                <h4>Sau khi lưu mapping</h4>
                <p>Quay lại trang đồng bộ, lọc đơn lỗi và dùng menu <strong>Đồng bộ API</strong> để đẩy lại Viettel hoặc EMS.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<style>
    .address-convert-tabs.nav-pills .nav-link {
        color: #344767 !important;
        background: #fff;
        border: 1px solid #e9ecef;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        margin-right: .5rem;
        font-weight: 600;
    }

    .address-convert-tabs.nav-pills .nav-link:hover,
    .address-convert-tabs.nav-pills .nav-link:focus,
    .address-convert-tabs.nav-pills .nav-link:hover *,
    .address-convert-tabs.nav-pills .nav-link:focus * {
        color: #5e72e4 !important;
    }

    .address-convert-tabs.nav-pills .nav-link:hover,
    .address-convert-tabs.nav-pills .nav-link:focus {
        background: #f6f8ff !important;
        border-color: #cfd7ff !important;
    }

    .address-convert-tabs.nav-pills .nav-link.active,
    .address-convert-tabs.nav-pills .show > .nav-link,
    .address-convert-tabs.nav-pills .nav-link.active:hover,
    .address-convert-tabs.nav-pills .nav-link.active:focus {
        color: #fff !important;
        background: #5e72e4 !important;
        border-color: #5e72e4 !important;
    }
</style>
<script>
    $(function () {
        function loadWards(provinceId, targetSelector, selectedWardId) {
            var $target = $(targetSelector);
            $target.html('<option value="">Đang tải...</option>');
            if (!provinceId) {
                $target.html('<option value="">Vui lòng chọn tỉnh trước...</option>');
                return;
            }

            $.get('/api/new-ward/' + provinceId)
                .done(function (wards) {
                    var html = '<option value="">Vui lòng chọn...</option>';
                    (wards || []).forEach(function (ward) {
                        var selected = String(selectedWardId || '') === String(ward.id) ? ' selected' : '';
                        html += '<option value="' + ward.id + '"' + selected + '>' + ward.name + '</option>';
                    });
                    $target.html(html);
                })
                .fail(function () {
                    $target.html('<option value="">Không tải được xã/phường</option>');
                });
        }

        var selectedLegacyCode = null;

        function resetLegacyCodeResult(message) {
            selectedLegacyCode = null;
            $('#legacy_code_result').hide();
            if (message) {
                $('#legacy_code_error').text(message).show();
            } else {
                $('#legacy_code_error').hide().text('');
            }
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        }

        function loadDistrictsTo(cityId, targetSelector, wardSelector) {
            var $target = $(targetSelector);
            $target.html('<option value="">Đang tải...</option>');
            if (wardSelector) {
                $(wardSelector).html('<option value="">Vui lòng chọn huyện trước...</option>');
            }

            if (!cityId) {
                $target.html('<option value="">Vui lòng chọn tỉnh trước...</option>');
                return;
            }

            $.get('/api/district/' + cityId)
                .done(function (districts) {
                    var html = '<option value="">Vui lòng chọn...</option>';
                    (districts || []).forEach(function (district) {
                        html += '<option value="' + district.id + '">' + escapeHtml(district.district_name) + '</option>';
                    });
                    $target.html(html);
                })
                .fail(function () {
                    $target.html('<option value="">Không tải được huyện/quận</option>');
                });
        }

        function loadLegacyWardsTo(districtId, targetSelector) {
            var $target = $(targetSelector);
            $target.html('<option value="">Đang tải...</option>');

            if (!districtId) {
                $target.html('<option value="">Vui lòng chọn huyện trước...</option>');
                return;
            }

            $.get('/api/ward/' + districtId)
                .done(function (wards) {
                    var html = '<option value="">Vui lòng chọn...</option>';
                    (wards || []).forEach(function (ward) {
                        html += '<option value="' + ward.id + '">' + escapeHtml(ward.ward_name) + '</option>';
                    });
                    $target.html(html);
                })
                .fail(function () {
                    $target.html('<option value="">Không tải được xã/phường</option>');
                });
        }

        function codeLine(codes) {
            codes = codes || {};
            return 'Tỉnh: ' + escapeHtml(codes.province_code || 'N/A') +
                ' | Huyện: ' + escapeHtml(codes.district_code || 'N/A') +
                ' | Xã: ' + escapeHtml(codes.ward_code || 'N/A');
        }

        function diachiCodeLine(legacyAddress) {
            var codes = legacyAddress && legacyAddress.diachi ? legacyAddress.diachi : null;
            if (!codes) {
                return '';
            }

            return '<div class="text-sm text-warning mt-1">Mã diachi.io: ' + codeLine({
                province_code: codes.province_code,
                district_code: codes.district_code,
                ward_code: codes.ward_code
            }) + '</div>';
        }

        function renderConversionResults(targetSelector, payload, emptyMessage) {
            var results = payload && payload.results ? payload.results : [];
            if (!results.length) {
                if (payload && payload.api_error) {
                    var error = payload.api_error;
                    var detail = '<strong>' + escapeHtml(error.message || emptyMessage) + '</strong>';
                    detail += '<div class="text-sm mt-1">Mã lỗi: ' + escapeHtml(error.code || 'N/A') + '</div>';
                    if (error.http_status) {
                        detail += '<div class="text-sm">HTTP: ' + escapeHtml(error.http_status) + '</div>';
                    }
                    if (error.body_preview) {
                        detail += '<div class="text-sm mt-1">Chi tiết: ' + escapeHtml(error.body_preview) + '</div>';
                    }
                    $(targetSelector).html('<div class="alert alert-danger mb-0">' + detail + '</div>');
                    return;
                }
                $(targetSelector).html('<div class="alert alert-warning mb-0">' + escapeHtml(emptyMessage) + '</div>');
                return;
            }

            var html = '<div class="list-group">';
            results.forEach(function (item) {
                var newAddress = item.new_address || {};
                var legacyAddress = item.legacy_address || {};
                var codes = item.partner_codes || {};
                html += '<div class="list-group-item">';
                html += '<div class="d-flex flex-column flex-lg-row justify-content-between">';
                html += '<div class="mb-2 mb-lg-0">';
                html += '<span class="badge ' + (item.partner_code === 'EMS' ? 'badge-info' : 'badge-warning') + '">' + escapeHtml(item.partner_code) + '</span> ';
                if (item.mapping_status === 'suggested') {
                    html += '<span class="badge badge-secondary">Gợi ý chưa lưu</span> ';
                } else if (item.mapping_status === 'local_code_missing') {
                    html += '<span class="badge badge-danger">Thiếu mã local</span> ';
                }
                html += '<strong>' + escapeHtml(newAddress.ward_name || 'Chưa rõ xã mới') + ', ' + escapeHtml(newAddress.province_name || 'Chưa rõ tỉnh mới') + '</strong>';
                html += '<div class="text-muted text-sm mt-1">Địa chỉ cũ: ' + escapeHtml(legacyAddress && legacyAddress.text ? legacyAddress.text : 'Chưa tìm được tên địa chỉ cũ theo mã') + '</div>';
                html += '<div class="text-muted text-sm">Mã đối tác: ' + codeLine(codes) + '</div>';
                html += diachiCodeLine(legacyAddress);
                if (item.description) {
                    html += '<div class="text-muted text-sm">Mô tả chuyển đổi: ' + escapeHtml(item.description) + '</div>';
                }
                if (item.note) {
                    html += '<div class="text-muted text-sm">Ghi chú: ' + escapeHtml(item.note) + '</div>';
                }
                html += '</div>';
                html += '<div class="d-flex align-items-start">';
                html += '<button type="button" class="btn btn-sm btn-outline-primary use-conversion-mapping"';
                html += ' data-partner="' + escapeHtml(item.partner_code) + '"';
                html += ' data-new-province="' + escapeHtml(newAddress.province_id || '') + '"';
                html += ' data-new-ward="' + escapeHtml(newAddress.ward_id || '') + '"';
                html += ' data-province-code="' + escapeHtml(codes.province_code || '') + '"';
                html += ' data-district-code="' + escapeHtml(codes.district_code || '') + '"';
                html += ' data-ward-code="' + escapeHtml(codes.ward_code || '') + '"';
                html += ' data-local-missing="' + (item.mapping_status === 'local_code_missing' ? '1' : '0') + '">Dùng mapping</button>';
                if (item.mapping_status === 'local_code_missing') {
                    html += '<button type="button" class="btn btn-sm btn-outline-secondary ml-2 fill-legacy-search"';
                    html += ' data-city="' + escapeHtml(legacyAddress.city_name || '') + '"';
                    html += ' data-district="' + escapeHtml(legacyAddress.district_name || '') + '"';
                    html += ' data-ward="' + escapeHtml(legacyAddress.ward_name || '') + '">Tìm mã local</button>';
                }
                html += '</div></div></div>';
            });
            html += '</div>';
            $(targetSelector).html(html);
        }

        function fillMappingForm(partner, newProvinceId, newWardId, provinceCode, districtCode, wardCode) {
            $('#partner_code').val(partner || 'VTP');
            $('#mapping_province_id').val(newProvinceId || '');
            if (newProvinceId) {
                loadWards(newProvinceId, '#mapping_ward_id', newWardId);
            }
            $('#partner_province_code').val(provinceCode || '');
            $('#partner_district_code').val(districtCode || '');
            $('#partner_ward_code').val(wardCode || '');
            $('#mapping_status').val('mapped');
            $('html, body').animate({ scrollTop: $('#mappingForm').offset().top - 120 }, 250);
        }

        function loadLegacyDistricts(cityId) {
            $('#legacy_district_id').html('<option value="">Đang tải...</option>');
            $('#legacy_ward_id').html('<option value="">Vui lòng chọn huyện trước...</option>');
            resetLegacyCodeResult();

            if (!cityId) {
                $('#legacy_district_id').html('<option value="">Vui lòng chọn tỉnh trước...</option>');
                return;
            }

            $.get('/api/district/' + cityId)
                .done(function (districts) {
                    var html = '<option value="">Vui lòng chọn...</option>';
                    (districts || []).forEach(function (district) {
                        html += '<option value="' + district.id + '">' + district.district_name + '</option>';
                    });
                    $('#legacy_district_id').html(html);
                })
                .fail(function () {
                    $('#legacy_district_id').html('<option value="">Không tải được huyện/quận</option>');
                });
        }

        function loadLegacyWards(districtId) {
            $('#legacy_ward_id').html('<option value="">Đang tải...</option>');
            resetLegacyCodeResult();

            if (!districtId) {
                $('#legacy_ward_id').html('<option value="">Vui lòng chọn huyện trước...</option>');
                return;
            }

            $.get('/api/ward/' + districtId)
                .done(function (wards) {
                    var html = '<option value="">Vui lòng chọn...</option>';
                    (wards || []).forEach(function (ward) {
                        html += '<option value="' + ward.id + '">' + ward.ward_name + '</option>';
                    });
                    $('#legacy_ward_id').html(html);
                })
                .fail(function () {
                    $('#legacy_ward_id').html('<option value="">Không tải được xã/phường</option>');
                });
        }

        function loadLegacyAddressCode(wardId) {
            resetLegacyCodeResult();
            if (!wardId) {
                return;
            }

            $.get('/api/legacy-address-code/' + wardId)
                .done(function (data) {
                    selectedLegacyCode = data;
                    $('#legacy_address_text').text(data.ward_name + ', ' + data.district_name + ', ' + data.city_name);
                    $('#legacy_vtp_province').text(data.vtp_province_code || 'N/A');
                    $('#legacy_vtp_district').text(data.vtp_district_code || 'N/A');
                    $('#legacy_vtp_ward').text(data.vtp_ward_code || 'N/A');
                    $('#legacy_ems_province').text(data.ems_province_code || 'N/A');
                    $('#legacy_ems_district').text(data.ems_district_code || 'N/A');
                    $('#legacy_ems_ward').text(data.ems_ward_code || 'N/A');
                    $('#legacy_code_result').show();
                })
                .fail(function () {
                    resetLegacyCodeResult('Không lấy được mã địa chỉ cũ.');
                });
        }

        $('#mapping_province_id').on('change', function () {
            loadWards($(this).val(), '#mapping_ward_id');
        });

        $('#filter_province_id').on('change', function () {
            loadWards($(this).val(), '#filter_ward_id');
        });

        $('#convert_new_province_id').on('change', function () {
            loadWards($(this).val(), '#convert_new_ward_id');
            $('#new_to_old_result').html('');
        });

        $('#convert_old_city_id').on('change', function () {
            loadDistrictsTo($(this).val(), '#convert_old_district_id', '#convert_old_ward_id');
            $('#old_to_new_result').html('');
        });

        $('#convert_old_district_id').on('change', function () {
            loadLegacyWardsTo($(this).val(), '#convert_old_ward_id');
            $('#old_to_new_result').html('');
        });

        $('#convert_old_to_new_btn').on('click', function () {
            var wardId = $('#convert_old_ward_id').val();
            if (!wardId) {
                $('#old_to_new_result').html('<div class="alert alert-warning mb-0">Vui lòng chọn đủ tỉnh/huyện/xã cũ.</div>');
                return;
            }

            $('#old_to_new_result').html('<div class="text-muted">Đang chuyển đổi...</div>');
            $.get('{{ route('partner_address_mappings.convert_old_to_new') }}', {
                ward_id: wardId,
                partner_code: $('#convert_old_partner_code').val()
            }).done(function (data) {
                renderConversionResults('#old_to_new_result', data, 'Chưa có mapping địa chỉ mới tương ứng với địa chỉ cũ này.');
            }).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không chuyển đổi được địa chỉ cũ sang mới.';
                $('#old_to_new_result').html('<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>');
            });
        });

        $('#convert_new_to_old_btn').on('click', function () {
            var wardId = $('#convert_new_ward_id').val();
            if (!wardId) {
                $('#new_to_old_result').html('<div class="alert alert-warning mb-0">Vui lòng chọn đủ tỉnh/xã mới.</div>');
                return;
            }

            $('#new_to_old_result').html('<div class="text-muted">Đang chuyển đổi...</div>');
            $.ajax({
                url: '{{ route('partner_address_mappings.convert_new_to_old') }}',
                method: 'GET',
                data: {
                    new_ward_id: wardId,
                    partner_code: $('#convert_new_partner_code').val()
                },
                timeout: 60000
            }).done(function (data) {
                renderConversionResults('#new_to_old_result', data, 'Chưa có mapping địa chỉ cũ tương ứng với xã/phường mới này.');
            }).fail(function (xhr) {
                var message = xhr.statusText === 'timeout'
                    ? 'Chuyển đổi quá lâu. Vui lòng thử tab Cũ → Mới với địa chỉ cũ nghi ngờ, hoặc chọn tỉnh/xã cụ thể hơn.'
                    : (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không chuyển đổi được địa chỉ mới sang cũ.');
                $('#new_to_old_result').html('<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>');
            });
        });

        $(document).on('click', '.use-conversion-mapping', function () {
            if (String($(this).data('local-missing')) === '1') {
                alert('Chưa thể dùng mapping vì BillHT chưa tìm thấy mã VTP/EMS local tương ứng. Hãy bấm "Tìm mã local" để tra cứu mã cũ trước.');
                return;
            }

            fillMappingForm(
                $(this).data('partner'),
                $(this).data('new-province'),
                $(this).data('new-ward'),
                $(this).data('province-code'),
                $(this).data('district-code'),
                $(this).data('ward-code')
            );
        });

        $(document).on('click', '.fill-legacy-search', function () {
            var text = [$(this).data('ward'), $(this).data('district'), $(this).data('city')]
                .filter(function (item) { return item; })
                .join(', ');

            $('#legacy_code_error').text('Hãy dùng khối Tra mã địa chỉ cũ để tìm: ' + text).show();
            $('html, body').animate({ scrollTop: $('#legacy_code_error').offset().top - 160 }, 250);
        });

        $('#legacy_city_id').on('change', function () {
            loadLegacyDistricts($(this).val());
        });

        $('#legacy_district_id').on('change', function () {
            loadLegacyWards($(this).val());
        });

        $('#legacy_ward_id').on('change', function () {
            loadLegacyAddressCode($(this).val());
        });

        $('.load-mapping').on('click', function () {
            var provinceId = $(this).data('province');
            var wardId = $(this).data('ward');

            fillMappingForm(
                $(this).data('partner'),
                provinceId,
                wardId,
                $(this).data('province-code'),
                $(this).data('district-code'),
                $(this).data('ward-code')
            );
            $('#mapping_status').val($(this).data('status'));
            $('#mapping_note').val($(this).data('note'));
        });

        $('#use_selected_legacy_code').on('click', function () {
            if (!selectedLegacyCode) {
                resetLegacyCodeResult('Bạn vui lòng chọn đủ tỉnh/huyện/xã cũ trước.');
                return;
            }

            var partner = $('#legacy_partner_code').val();
            $('#partner_code').val(partner);
            if (partner === 'EMS') {
                $('#partner_province_code').val(selectedLegacyCode.ems_province_code || '');
                $('#partner_district_code').val(selectedLegacyCode.ems_district_code || '');
                $('#partner_ward_code').val(selectedLegacyCode.ems_ward_code || '');
            } else {
                $('#partner_province_code').val(selectedLegacyCode.vtp_province_code || '');
                $('#partner_district_code').val(selectedLegacyCode.vtp_district_code || '');
                $('#partner_ward_code').val(selectedLegacyCode.vtp_ward_code || '');
            }
            $('#mapping_status').val('mapped');

            $('html, body').animate({ scrollTop: $('#mappingForm').offset().top - 120 }, 250);
        });
    });
</script>
@endsection
