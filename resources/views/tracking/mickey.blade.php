<?php 
    $table1 = $mickey_tracking['table1'][0] ?? [];
    $table = $mickey_tracking['table'] ?? [];
?>

<div class="row mb-4 mt-4 ml-2 mr-2 custorm-m">
    <div class="col">
        {{-- <div class="table-responsive"> --}}
            <table class="table table-bordered">
                    <thead class="thead-custom">
                        <tr>
                            <th class="mickey-title" colspan="2" scope="col">THÔNG TIN BƯU PHẨM</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="custom-weight custom-size">Mã Bưu phẩm</td>
                        <td class="custom-weight custom-size">{{$table1['so_hieu']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight custom-size">Người gửi</td>
                        <td class="custom-size"><span style="white-space:pre-line">{{$order->sender->sender_name ?? $table1['ten_kh']}} hahahahah kdkdkdk ahahsdhfdhf ạdhjhsjhfj<span></td>
                    </tr>
                    <!-- <tr>
                        <td class="custom-size">Trọng Lượng</td>
                        <td class="custom-size">{{$table1['kluong']}}</td>
                    </tr> -->
                    <tr>
                        <td class="custom-weight custom-size">Tuyến phát</td>
                        <td class="custom-size">{{$table1['tinh']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight custom-size">Trạng thái</td>
                        <td class="custom-size">{{$table1['tinh_trang']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight custom-size">Ngày giờ</td>
                        <td class="custom-size">{{$table1['ngay_phat'] . ' ' . $table1['gio_phat']}}</td>
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
                            @else
                                {{$table[0]['dchi']}}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="custom-weight custom-size">Người ký nhận</td>
                        <td class="custom-size">{{$table1['nguoi_nhan']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight custom-size">Ghi chú kết quả phát</td>
                        <td class="custom-size"><span style="white-space:pre-line">{{$table1['ghi_chu_phat']}}</span></td>
                    </tr>
                </tbody>
            </table>
        {{-- </div> --}}
        {{-- <div class="table-wrapper"> --}}
            <table class="table table-bordered mt-4">
                <thead class="thead-custom">
                    <tr>
                        <th class="mickey-title" colspan="4" scope="col">THÔNG TIN LỘ TRÌNH</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="custom-weight custom-size">STT</td>
                        <td class="custom-weight custom-size">Ngày đến</td>
                        <td class="custom-weight custom-size">Trạng thái</td>
                        <td class="custom-weight custom-size">Bưu cục</td>
                    </tr>
                    @foreach($table as $item)
                    <tr>
                        <td class="custom-size">{{$item['stt']}}</td>
                        <td class="custom-size">{{$item['ngay_den'] . ' ' . $item['gio']}}</td>
                        <td class="custom-size"><span style="white-space:pre-line">{{$item['trang_thai']}}</span></th>
                        <td class="custom-size">{{$item['dchi']}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        {{-- </div> --}}
    </div>
    <div class="col custom-image" style="text-align: center">
        <img style="max-width: 500px; width:100%" data-toggle="modal" data-target=".bd-example-modal-lg" src="{{$table1['img']}}">
    </div>
</div>

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="padding: 0">
                <button style="position: absolute; right: 10px; top: 10px" type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span style="font-size: 35px" aria-hidden="true">&times;</span>
                </button>
            </div>
            <img style="padding: 35px" src="{{$table1['img']}}">
        </div>
    </div>
</div>

<style>
    .mickey-title {
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
