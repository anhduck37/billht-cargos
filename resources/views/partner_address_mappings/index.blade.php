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
                    <h3 class="mb-0">Tra mã địa chỉ cũ</h3>
                    <p class="text-muted text-sm mb-0">Tìm theo tên tỉnh, huyện hoặc xã cũ để lấy mã VTP/EMS điền vào form mapping.</p>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('partner_address_mappings.index') }}">
                        <div class="form-row">
                            <div class="form-group col-lg-6 col-md-6">
                                <label>Từ khóa địa chỉ cũ</label>
                                <input type="text" name="legacy_keyword" value="{{ request('legacy_keyword') }}" class="form-control" placeholder="VD: Hữu Hòa, Thanh Trì, Hà Nội">
                            </div>
                            <div class="form-group col-lg-3 col-md-6">
                                <label>Đối tác cần xem mã</label>
                                <select name="legacy_partner_code" class="form-control">
                                    <option value="">Cả Viettel và EMS</option>
                                    <option value="VTP" {{ request('legacy_partner_code') === 'VTP' ? 'selected' : '' }}>Viettel</option>
                                    <option value="EMS" {{ request('legacy_partner_code') === 'EMS' ? 'selected' : '' }}>EMS</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-3 col-md-12 d-flex align-items-end">
                                <button type="submit" class="btn btn-info btn-block">Tra mã</button>
                            </div>
                        </div>
                    </form>

                    @if(request()->filled('legacy_keyword'))
                        <div class="table-responsive mt-3">
                            <table class="table table-sm align-items-center">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Tỉnh cũ</th>
                                        <th>Huyện cũ</th>
                                        <th>Xã cũ</th>
                                        <th>VTP</th>
                                        <th>EMS</th>
                                        <th class="text-right">Dùng mã</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($legacyAddressResults as $legacy)
                                        <tr>
                                            <td>{{ $legacy->city_name }}</td>
                                            <td>{{ $legacy->district_name }}</td>
                                            <td>{{ $legacy->ward_name }}</td>
                                            <td>
                                                <div>Tỉnh: <strong>{{ $legacy->vtp_province_code }}</strong></div>
                                                <div>Huyện: <strong>{{ $legacy->vtp_district_code }}</strong></div>
                                                <div>Xã: <strong>{{ $legacy->vtp_ward_code }}</strong></div>
                                            </td>
                                            <td>
                                                <div>Tỉnh: <strong>{{ $legacy->ems_province_code }}</strong></div>
                                                <div>Huyện: <strong>{{ $legacy->ems_district_code }}</strong></div>
                                                <div>Xã: <strong>{{ $legacy->ems_ward_code }}</strong></div>
                                            </td>
                                            <td class="text-right">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-warning use-legacy-code mb-1"
                                                    data-partner="VTP"
                                                    data-province-code="{{ $legacy->vtp_province_code }}"
                                                    data-district-code="{{ $legacy->vtp_district_code }}"
                                                    data-ward-code="{{ $legacy->vtp_ward_code }}">
                                                    Dùng VTP
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-info use-legacy-code mb-1"
                                                    data-partner="EMS"
                                                    data-province-code="{{ $legacy->ems_province_code }}"
                                                    data-district-code="{{ $legacy->ems_district_code }}"
                                                    data-ward-code="{{ $legacy->ems_ward_code }}">
                                                    Dùng EMS
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">Không tìm thấy mã địa chỉ cũ phù hợp.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
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
                    <li>Dùng khối <strong>Tra mã địa chỉ cũ</strong> để tìm mã tỉnh, huyện, xã của đối tác.</li>
                    <li>Bấm <strong>Dùng VTP</strong> hoặc <strong>Dùng EMS</strong> để tự điền mã vào form.</li>
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

        $('#mapping_province_id').on('change', function () {
            loadWards($(this).val(), '#mapping_ward_id');
        });

        $('#filter_province_id').on('change', function () {
            loadWards($(this).val(), '#filter_ward_id');
        });

        $('.load-mapping').on('click', function () {
            var provinceId = $(this).data('province');
            var wardId = $(this).data('ward');

            $('#partner_code').val($(this).data('partner'));
            $('#mapping_province_id').val(provinceId);
            loadWards(provinceId, '#mapping_ward_id', wardId);
            $('#partner_province_code').val($(this).data('province-code'));
            $('#partner_district_code').val($(this).data('district-code'));
            $('#partner_ward_code').val($(this).data('ward-code'));
            $('#mapping_status').val($(this).data('status'));
            $('#mapping_note').val($(this).data('note'));

            $('html, body').animate({ scrollTop: $('#mappingForm').offset().top - 120 }, 250);
        });

        $('.use-legacy-code').on('click', function () {
            $('#partner_code').val($(this).data('partner'));
            $('#partner_province_code').val($(this).data('province-code'));
            $('#partner_district_code').val($(this).data('district-code'));
            $('#partner_ward_code').val($(this).data('ward-code'));
            $('#mapping_status').val('mapped');

            $('html, body').animate({ scrollTop: $('#mappingForm').offset().top - 120 }, 250);
        });
    });
</script>
@endsection
