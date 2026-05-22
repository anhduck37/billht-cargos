@extends('layouts.app')

@section('content')
<style>
    #uploadImageModal .modal-content {
        border-radius: 10px;
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    #uploadImageModal .spinner-border {
        width: 3rem;
        height: 3rem;
        border-width: 0.3rem;
    }
    
    #uploadImageModal .progress {
        border-radius: 10px;
        overflow: hidden;
    }
    
    #uploadImageModal .progress-bar {
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    #uploadImageModal .modal-body {
        padding: 2rem;
    }
    
    #uploadImageModal h5 {
        color: #5e72e4;
        font-weight: 600;
    }
    
    #uploadImageModal .text-muted {
        font-size: 0.9rem;
    }
</style>

    @include('layouts.headers.cards')

    <div class="container-fluid mt--4">
        <div class="row mt-5">
            <div class="col-xl-12 mb-5 mb-xl-0">
                @include('flash::message')
                <div class="card shadow">
                    <div class="card-header border-0">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="text-center">Cập nhật vận đơn</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(['route' => ['orders.update', $order->id], 'method' => 'PATCH', 'enctype' => 'multipart/form-data']) !!}
                        @csrf
                        @if(isset($order) && (($order->sender && $order->sender->address_scheme === 'new') || ($order->receiver && $order->receiver->address_scheme === 'new')))
                            @include('orders.fields_new', ['update' => true])
                        @else
                            @include('orders.fields')
                        @endif

                        <div class="card-footer text-center">
                        	@if(auth()->user()->level != \App\User::LEVEL_USER)
                            <button type="button" id="image" class="btn btn-primary mb-2">Chụp ảnh</button>
                            @endif
                            {!! Form::submit( 'Cập nhật vận đơn' , ['class' => 'btn btn-primary mb-2']) !!}

                            @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                            <button id="print" type="button" data-id="{{$order->id}}" class="btn btn-primary mb-2">In đơn</button>
                            {{-- <button type="button" data-toggle="modal" data-target="#openModalEmail" class="btn btn-primary mb-2">Gửi email</button> --}}
                            <button type="button" id="sendSMS"  data-id="{{$order->id}}" class="btn btn-primary mb-2">Gửi SMS</button>
                            <button type="button" id="sendZaloZNS"  data-id="{{$order->id}}" class="btn btn-primary mb-2">Gửi Zalo</button>
                            @endif
                            <a class='btn btn-primary mb-2' href="{{route('orders.index')}}">Tìm vận đơn</a>
                            <a class='btn btn-primary mb-2' href="{{ route('orders.create') }}">Tạo vận đơn khác</a>
                            @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                            <a class='btn btn-primary mb-2' href="{{ route('orders.createViettelPost', ['id' => $order->id]) }}">Tạo vận đơn Viettel Post</a>
                            <a class='btn btn-primary mb-2' href="{{ route('orders.createEms', ['id' => $order->id]) }}">Tạo vận đơn EMS</a>
                            @endif
                            <!-- <a class='btn btn-light mb-2' href="{{route('orders.index')}}">Thoát</a> -->
                        </div>

                        {!! Form::close() !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="openModalEmail" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title" id="exampleModalLongTitle">Bạn vui lòng template email</h3>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="loading">
                        <div id="isLoading" style="display: none" class="text-center"><img width="70px" src="{{asset('/image/loading.jpg')}}" ></div>

                        <div class="form-check isShow">
                            <input class="form-check-input" type="radio" name="type_email" id="exampleRadios1" value="1">
                            <label class="form-check-label" for="exampleRadios1">
                                Đã tiếp nhận bưu phẩm
                            </label>
                        </div>
                        <div class="form-check isShow">
                            <input class="form-check-input" type="radio" name="type_email" id="exampleRadios2" value="2">
                            <label class="form-check-label" for="exampleRadios2">
                                Đã giao
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                        <button type="button" data-id="{{$order->id}}" id="sendEmail" class="btn btn-primary">Gửi email</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Loading Upload Image -->
    <div class="modal fade" id="uploadImageModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <h5 class="mb-3" id="uploadImageModalLabel">Đang tải ảnh lên...</h5>
                    <p class="text-muted mb-3" id="uploadImageStatus">Vui lòng đợi trong giây lát</p>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                             id="uploadImageProgress" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                            <span id="uploadImageProgressText">0%</span>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">Không đóng trình duyệt trong lúc này</small>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script type="text/javascript" src="{{ asset('js/render-print.js') }}?v={{ time() }}"></script>
    <script type="text/javascript">
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#print').on('click', function (e) {
                let orderId = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: '/template/render',
                    data: {'order': [orderId]},
                    success: function (res) {
                        let html = renderHtml(res)
                        print(html)
                    },
                });
            })

            $('#sendEmail').on('click', function () {
                let orderId = $(this).attr('data-id');
                let typeEmail = $('input[name="type_email"]:checked').val()
                if(typeEmail) {
                    $('.isShow').css('display', 'none')
                    $('#isLoading').css('display', '')
                    $.ajax({
                        type: "POST",
                        url: '/order/send-email',
                        data: {'order_ids': [orderId], type_email: typeEmail, isUpdate: true },
                        success: function (res) {
                            window.location.href = res;
                        },
                    });
                }
            })

            $('#sendSMS').on('click', function() {
                let orderId = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: '/order/send-sms',
                    data: {'order_ids': [orderId], isUpdate: true },
                    beforeSend: function() {
                        $('#sendSMS').attr("disabled", true)
                        $('#sendSMS').html(`Gửi SMS <img width="20px" src="{{asset('/image/loading.jpg')}}" >`)
                    },
                    success: function (res) {
                        window.location.href = res;
                    },
                });
            })

            $('#sendZaloZNS').on('click', function () {
                let orderId = $(this).attr('data-id');
                $.ajax({
                    type: "POST",
                    url: '/order/send-zalo-zns',
                    data: {'order_ids': [orderId], isUpdate: true },
                    beforeSend: function() {
                        $('#sendZaloZNS').attr("disabled", true)
                        $('#sendZaloZNS').html(`Gửi Zalo <img width="20px" src="{{asset('/image/loading.jpg')}}" >`)
                    },
                    success: function (res) {
                        window.location.href = res;
                    },
                });
            })

            // Biến để theo dõi trạng thái upload ảnh
            let imageUploaded = false;
            let imageFileName = null;
            let isUploadingImage = false;
            let isSubmittingAfterUpload = false;

            // Hàm cập nhật progress bar
            function updateUploadProgress(percent) {
                $('#uploadImageProgress').css('width', percent + '%').attr('aria-valuenow', percent);
                $('#uploadImageProgressText').text(percent + '%');
                
                if (percent < 30) {
                    $('#uploadImageStatus').text('Đang gửi dữ liệu ảnh lên server...');
                } else if (percent < 60) {
                    $('#uploadImageStatus').text('Đang xử lý và lưu ảnh...');
                } else if (percent < 90) {
                    $('#uploadImageStatus').text('Đang hoàn tất quá trình upload...');
                } else {
                    $('#uploadImageStatus').text('Gần hoàn thành...');
                }
            }

            // Xử lý form submit với upload ảnh bất đồng bộ
            $('form').on('submit', function(e) {
                // Nếu đang submit sau khi upload ảnh, cho phép submit bình thường
                if (isSubmittingAfterUpload) {
                    return true;
                }
                
                let $form = $(this);
                let $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
                let originalText = $submitBtn.val() || $submitBtn.text();
                let hasImage = false;
                let imageData = '';
                
                // Kiểm tra xem có ảnh base64 không
                let $imageDataInput = $('#image_data');
                if ($imageDataInput.length && $imageDataInput.attr('type') === 'hidden') {
                    imageData = $imageDataInput.val() || '';
                    if (imageData && imageData.length > 0) {
                        hasImage = true;
                        // Validate kích thước base64 (ước tính ~1.33x kích thước thực)
                        let estimatedSize = (imageData.length * 3) / 4;
                        if (estimatedSize > 10 * 1024 * 1024) {
                            e.preventDefault();
                            alert('Kích thước ảnh quá lớn (' + Math.round(estimatedSize / 1024 / 1024) + 'MB). Vui lòng chụp lại với chất lượng thấp hơn hoặc chọn ảnh khác.');
                            return false;
                        }
                    }
                }
                
                // Kiểm tra file upload
                let $fileInput = $form.find('input[type="file"][name="image_data"]');
                if ($fileInput.length && $fileInput[0].files.length > 0) {
                    hasImage = true;
                    let fileSize = $fileInput[0].files[0].size;
                    if (fileSize > 10 * 1024 * 1024) {
                        e.preventDefault();
                        alert('Kích thước ảnh quá lớn (' + Math.round(fileSize / 1024 / 1024) + 'MB). Vui lòng chọn ảnh nhỏ hơn 10MB.');
                        return false;
                    }
                }
                
                // Nếu có ảnh và chưa upload, upload trước khi submit form
                if (hasImage && !imageUploaded && !isUploadingImage) {
                    e.preventDefault();
                    isUploadingImage = true;
                    
                    $submitBtn.prop('disabled', true);
                    $submitBtn.html('Đang tải ảnh lên... <img width="20px" src="{{asset('/image/loading.jpg')}}" >');
                    
                    // Hiển thị modal loading
                    $('#uploadImageModal').modal('show');
                    $('#uploadImageStatus').text('Đang chuẩn bị upload ảnh...');
                    updateUploadProgress(10);
                    
                    // Tạo FormData để upload ảnh
                    let formData = new FormData();
                    if ($imageDataInput.length && $imageDataInput.attr('type') === 'hidden') {
                        formData.append('image_data', imageData);
                        formData.append('type_image', $('input[name="type_image"]:checked').val() || '{{\App\OrderImage::TYPE_IMAGE_WEBCAM}}');
                    } else if ($fileInput.length && $fileInput[0].files.length > 0) {
                        formData.append('image_data', $fileInput[0].files[0]);
                        formData.append('type_image', $('input[name="type_image"]:checked').val() || '{{\App\OrderImage::TYPE_IMAGE_FILE}}');
                    }
                    
                    // Simulate progress (vì base64 upload không có progress event thực sự)
                    let progressInterval = setInterval(function() {
                        let currentProgress = parseInt($('#uploadImageProgress').attr('aria-valuenow'));
                        if (currentProgress < 80) {
                            updateUploadProgress(Math.min(currentProgress + 5, 80));
                        }
                    }, 200);
                    
                    // Upload ảnh qua AJAX
                    $.ajax({
                        url: '{{route("orders.upload-image", $order->id)}}',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        timeout: 120000, // 120 giây timeout
                        xhr: function() {
                            let xhr = new window.XMLHttpRequest();
                            // Nếu là file upload (không phải base64), có thể track progress
                            if ($fileInput.length && $fileInput[0].files.length > 0) {
                                xhr.upload.addEventListener("progress", function(evt) {
                                    if (evt.lengthComputable) {
                        let percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        clearInterval(progressInterval);
                        updateUploadProgress(percentComplete);
                                    }
                                }, false);
                            }
                            return xhr;
                        },
                        success: function(response) {
                            clearInterval(progressInterval);
                            updateUploadProgress(100);
                            $('#uploadImageStatus').text('Upload thành công! Đang xử lý...');
                            
                            setTimeout(function() {
                                if (response.success) {
                                    imageUploaded = true;
                                    imageFileName = response.file_name;
                                    
                                    // Thêm fileName vào form để backend biết ảnh đã được upload
                                    $form.append('<input type="hidden" name="uploaded_image_file" value="' + imageFileName + '">');
                                    
                                    // Xóa input ảnh để tránh gửi lại
                                    $imageDataInput.remove();
                                    $fileInput.remove();
                                    
                                    $('#uploadImageModal').modal('hide');
                                    $submitBtn.html('Đang cập nhật... <img width="20px" src="{{asset('/image/loading.jpg')}}" >');
                                    
                                    // Đánh dấu đang submit sau khi upload để tránh xử lý lại
                                    isSubmittingAfterUpload = true;
                                    
                                    // Submit form sau khi upload ảnh thành công
                                    $form[0].submit();
                                } else {
                                    isUploadingImage = false;
                                    $('#uploadImageModal').modal('hide');
                                    $submitBtn.prop('disabled', false);
                                    $submitBtn.html(originalText);
                                    alert('Lỗi upload ảnh: ' + (response.error || 'Vui lòng thử lại.'));
                                }
                            }, 500);
                        },
                        error: function(xhr) {
                            clearInterval(progressInterval);
                            isUploadingImage = false;
                            $('#uploadImageModal').modal('hide');
                            $submitBtn.prop('disabled', false);
                            $submitBtn.html(originalText);
                            
                            let errorMsg = 'Lỗi upload ảnh. Vui lòng thử lại.';
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errorMsg = xhr.responseJSON.error;
                            } else if (xhr.status === 0) {
                                errorMsg = 'Kết nối bị gián đoạn. Vui lòng kiểm tra kết nối mạng và thử lại.';
                            } else if (xhr.status >= 500) {
                                errorMsg = 'Lỗi máy chủ. Vui lòng thử lại sau.';
                            }
                            alert(errorMsg);
                        }
                    });
                    
                    return false;
                }
                
                // Nếu đã upload ảnh, thêm fileName vào form
                if (imageUploaded && imageFileName) {
                    // Đảm bảo chỉ thêm một lần
                    if ($form.find('input[name="uploaded_image_file"]').length === 0) {
                        $form.append('<input type="hidden" name="uploaded_image_file" value="' + imageFileName + '">');
                    }
                }
                
                // Nếu có ảnh, thêm loading indicator
                if (hasImage && !imageUploaded) {
                    $submitBtn.prop('disabled', true);
                    $submitBtn.html('Đang tải lên... <img width="20px" src="{{asset('/image/loading.jpg')}}" >');
                    
                    // Tạo timeout cho request (120 giây)
                    let submitTimeout = setTimeout(function() {
                        $submitBtn.prop('disabled', false);
                        $submitBtn.html(originalText);
                        alert('Yêu cầu mất quá nhiều thời gian. Vui lòng kiểm tra kết nối mạng và thử lại.');
                    }, 120000); // 120 giây
                    
                    // Lưu timeout ID để clear khi form submit thành công
                    $form.data('submitTimeout', submitTimeout);
                }
            });
            
            // Xử lý khi form submit thành công hoặc có lỗi
            $(document).ajaxError(function(event, xhr, settings) {
                if (settings.url && settings.url.includes('orders.update') || settings.type === 'PATCH') {
                    let $form = $('form');
                    let $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
                    let originalText = $submitBtn.data('original-text') || 'Cập nhật vận đơn';
                    
                    clearTimeout($form.data('submitTimeout'));
                    $submitBtn.prop('disabled', false);
                    $submitBtn.html(originalText);
                    
                    if (xhr.status === 0 || xhr.statusText === 'abort') {
                        alert('Kết nối bị gián đoạn. Vui lòng kiểm tra kết nối mạng và thử lại.');
                    } else if (xhr.status >= 500) {
                        alert('Lỗi máy chủ. Vui lòng thử lại sau.');
                    }
                }
            });
        });
        function print(html) {
            var a = window.open();
            a.document.write(html);
            a.document.close();
        }
    </script>
@endsection
