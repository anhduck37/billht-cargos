@extends('layouts.app')

@section('content')

    @include('layouts.headers.cards')

    <div class="container-fluid mt--4">
        <div class="row mt-5">
            <div class="col-xl-12 mb-5 mb-xl-0">
                @include('flash::message')
                <div class="card shadow border-0">
                    <div class="card-header bg-white border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="text-center text-primary mt-3 mb-0">Nhập vận đơn hàng loạt</h1>
                                <p class="text-center text-muted small">Vui lòng chọn đúng phiên bản mẫu Excel bạn đang sử dụng</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => 'orders.import', 'method' => 'POST', 'enctype' => 'multipart/form-data']) !!}
                        @csrf
                        
                        <!-- File selection section -->
                        <div class="row justify-content-center mb-5">
                            <div class="col-md-7">
                                <div class="form-group text-center">
                                    <label class="form-control-label d-block mb-3" style="font-size: 1.1rem;">1. Chọn tệp Excel của bạn</label>
                                    <div class="custom-file shadow-sm">
                                        <input type="file" name="file" class="custom-file-input" id="file" required>
                                        <label class="custom-file-label border-primary" for="file" style="height: 50px; line-height: 35px;"><i class="fas fa-file-excel mr-2 text-primary"></i>Chọn tệp tin vận đơn...</label>
                                    </div>
                                    <a href="{{route('fileDemo')}}" class="btn btn-link btn-sm text-primary mt-2"><i class="fas fa-download mr-1"></i>Tải File Excel Mẫu Chuẩn</a>
                                </div>
                            </div>
                        </div>

                        <div class="row px-lg-4">
                            <!-- Card Mẫu Mới 2025 (PRIMARY) -->
                            <div class="col-lg-8 mb-4">
                                <div class="card border-0 shadow h-100" style="background: linear-gradient(135deg, #ffffff 0%, #f0fff4 100%); border-left: 5px solid #2dce89 !important;">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-3 text-center">
                                                <i class="fas fa-rocket fa-4x text-success mb-3 mb-md-0"></i>
                                            </div>
                                            <div class="col-md-9">
                                                <h3 class="card-title font-weight-bold text-success mb-2">ƯU TIÊN: Mẫu Mới 2025</h3>
                                                <p class="text-dark small mb-3">Sử dụng hệ thống địa danh hành chính mới nhất (Tỉnh, Xã). Đảm bảo giao hàng chính xác.</p>
                                                
                                                <div class="bg-white p-3 border rounded shadow-sm mb-4">
                                                    <p class="mb-1 font-weight-bold small text-muted"><i class="fas fa-map-marker-alt mr-1 text-danger"></i> Định dạng địa chỉ tại Cột F và S:</p>
                                                    <div class="d-flex align-items-center bg-light p-2 rounded">
                                                        <code class="flex-grow-1" style="font-size: 0.9rem; color: #1a1a1a;">Số nhà, Tên Xã, Tên Tỉnh</code>
                                                        <span class="badge badge-success ml-2">VD: 123, Xã Kim Chung, Hà Nội</span>
                                                    </div>
                                                </div>

                                                <button type="submit" name="address_scheme" value="new" class="btn btn-success btn-lg btn-block shadow"><i class="fas fa-check-circle mr-2"></i>XÁC NHẬN NHẬP EXCEL 2025</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Mẫu Cũ (MINIMIZED) -->
                            <div class="col-lg-4 mb-4">
                                <div class="card border-0 shadow-sm h-100 bg-secondary" style="opacity: 0.85;">
                                    <div class="card-body text-center d-flex flex-column">
                                        <div class="mb-2">
                                            <i class="fas fa-history fa-2x text-muted"></i>
                                        </div>
                                        <h5 class="font-weight-bold text-muted">Hệ thống cũ</h5>
                                        <p class="small text-muted mb-3">Dành cho các file dữ liệu cũ chưa cập nhật địa danh 2025.</p>
                                        
                                        <div class="alert alert-light p-2 text-left mb-3" style="font-size: 0.7rem; border: 1px dashed #ced4da;">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> Không bắt buộc định dạng phẩy.
                                        </div>

                                        <button type="submit" name="address_scheme" value="old" class="btn btn-outline-secondary btn-sm mt-auto">Dùng mẫu cũ</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-center bg-transparent border-0 mt-3">
                            <button data-toggle="modal" data-target="#openModalPrint" type="button" class="btn btn-outline-info btn-sm mx-1"><i class="fas fa-print mr-1"></i>In tất cả</button>
                            <a class='btn btn-outline-primary btn-sm mx-1' href="{{route('orders.index')}}"><i class="fas fa-list mr-1"></i>Quản lý vận đơn</a>
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
            @include('orders.import_table')
        </div>
        
        <!-- Modal Print (Giữ nguyên) -->
        <div class="modal fade" id="openModalPrint" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h3 class="modal-title text-white" id="exampleModalLongTitle">Chọn số liên in</h3>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="loading">
                        <div id="isLoading" style="display: none" class="text-center"><img width="70px" src="{{asset('/image/loading.jpg')}}" ></div>

                        <div class="custom-control custom-radio mb-3 isShow">
                            <input name="number" class="custom-control-input" id="number1" type="radio" value="1">
                            <label class="custom-control-label" for="number1">In 1 liên (Tiết kiệm giấy)</label>
                        </div>
                        <div class="custom-control custom-radio isShow">
                            <input name="number" class="custom-control-input" id="number2" checked type="radio" value="2">
                            <label class="custom-control-label" for="number2">In 2 liên (Mặc định)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-link text-muted" data-dismiss="modal">Hủy</button>
                        <button type="button" id="print" class="btn btn-primary px-4">Xác nhận in</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $('#file').on('change',function(){
        var fileName = $(this).val().replace('C:\\fakepath\\', " ");
        $(this).next('.custom-file-label').html(fileName);
    })
</script>
@endsection
