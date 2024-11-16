<div class="row mb-4 mt-4 ml-2 mr-2 custorm-m">
    <div class="col">
        <table class="table table-bordered">
            <thead class="thead-custom">
                <tr>
                    <th class="viettel-post-title" colspan="2" scope="col">THÔNG TIN BƯU PHẨM</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td class="custom-weight custom-size">Mã Bưu phẩm</td>
                <td class="custom-weight custom-size">{{$order->order_code}}</td>
            </tr>
            <tr>
                <td class="custom-weight custom-size">Người gửi</td>
                <td class="custom-size"><span style="white-space:pre-line">{{$order->sender->sender_name}}<span></td>
            </tr>
            <tr>
                <td class="custom-weight custom-size">Trạng thái</td>
                <td class="custom-size">{{$data_tracking[0]['status_name'] ?? $order->order_status}}</td>
            </tr>
            <tr>
                <td class="custom-weight custom-size">Ngày giờ</td>
                <td class="custom-size">{{$data_tracking[0]['order_statusdate'] ?? ''}}</td>
            </tr>
            <tr>
                <td class="custom-weight custom-size">Địa chỉ nhận</td>
                <td class="custom-size">
                    @if(isset($order->receiver))
                        @if($order->receiver->address ) @foreach(explode(',', $order->receiver->address) as $item) {{$item.','}}<br> @endforeach @endif
                        @if(isset($order->receiver->ward))
                        {{ $order->receiver->ward->ward_name.',' }}<br>
                        @endif
                        @if(isset($order->receiver->district))
                        {{ $order->receiver->district->district_name.',' }}<br>
                        @endif
                        @if(isset($order->receiver->city))
                        {{$order->receiver->city->city_name}}<br>
                        @endif
                    @endif
                </td>
            </tr>
            <tr>
                <td class="custom-weight custom-size">Người nhận</td>
                <td class="custom-size">{{$data_tracking[0]['receiver_fullname'] ?? $order->receiver->receiver_name}}</td>
            </tr>
            <tr>
                <td class="custom-weight custom-size">Ghi chú kết quả phát</td>
                <td class="custom-size"><span style="white-space:pre-line">{{ preg_replace("/\([^)]*\)/", "", $data_tracking[0]['note'] ?? '') }}</span></td>
                
            </tr>
        </tbody>
    </table>
        <table class="table table-bordered mt-4 mb-4">
            <thead class="thead-custom">
                <tr>
                    <th class="viettel-post-title" colspan="4" scope="col">THÔNG TIN LỘ TRÌNH</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="custom-weight custom-size">Thời gian</td>
                    <td class="custom-weight custom-size">Trạng thái</td>
                    <td class="custom-weight custom-size">Nội dung</td>
                </tr>
                {{-- @dd($data_tracking) --}}
                @foreach($data_tracking as $item)
                    {{-- <tr>
                        <td class="viettel-post-title" colspan="3">{{$item['STATUS_NAME']}}</td>
                    </tr> --}}

                    {{-- @foreach($item['TRACKINGS'] as $tracking) --}}

                        <tr>
                            <td class="custom-size">{{$item['order_statusdate'] ?? ''}}</td>
                            <td class="custom-size">{{$item['status_name'] ?? ''}}</th>
                            <td class="custom-size"><span style="white-space: pre-line">{{preg_replace("/\([^)]*\)/", "", ($item['note'] ?? ''))}}</span></td>
                        </tr>
                    {{-- @endforeach --}}
                @endforeach
            </tbody>
        </table>
        <div class="text-center mt-4">
            @if (isset($order->image))
                <img style="max-width: 400px; {{$order->image->type_upload == \App\OrderImage::TYPE_IMAGE_WEBCAM ? 'transform: rotate(270deg);' : ''}}" src="{{$order->image->type_save == \App\OrderImage::SAVE_GOOGLE_DRIVE ? (config('google_drive.url').$order->image->file_id) : asset('uploads/'.$order->image->image)}}" />
            @endif
        </div>
    </div>
</div>
<style>
    .viettel-post-title {
        text-align: center;
        font-weight: 700;
        font-size: 15px !important;
    }
    .custom-weight {
        font-weight: 600;
    }
    .custom-padding {
        padding: 0 10%
    }
    .custom-size {
        font-size: 0.9rem !important;
    }
    @media only screen and (max-width: 600px) {
        .custom-padding {
            padding: 0;
        }
        .custom-image {
            margin-top: 20px;
        }
        .custorm-m {
            margin: 1.5rem 0 0 0 !important;
        }
    }
</style>