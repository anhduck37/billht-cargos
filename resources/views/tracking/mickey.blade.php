<?php 
    $table1 = $mickey_tracking['table1'][0] ?? [];
    $table = $mickey_tracking['table'] ?? [];
?>

<div class="row mb-4 mt-4 ml-2 mr-2">
    <div class="col">
        <div class="table-responsive">
            <table class="table table-bordered">
                    <thead class="thead-custom">
                        <tr>
                            <th class="mickey-title" colspan="2" scope="col">THÔNG TIN BƯU PHẨM</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Mã Bưu phẩm</td>
                        <td>{{$table1['so_hieu']}}</td>
                    </tr>
                    <tr>
                        <td>Người gửi</td>
                        <td>{{$table1['ten_kh']}}</td>
                    </tr>
                    <tr>
                        <td>Trọng Lượng</td>
                        <td>{{$table1['kluong']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Tuyến phát</td>
                        <td class="custom-weight">{{$table1['tinh']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Trạng Thái</td>
                        <td class="custom-weight">{{$table1['tinh_trang']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Ngày giờ</td>
                        <td class="custom-weight">{{$table1['ngay_phat'] . ' ' . $table1['gio_phat']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Địa chỉ nhận</td>
                        <td class="custom-weight">{{$table[0]['dchi']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Người Ký Nhận</td>
                        <td class="custom-weight">{{$table1['nguoi_nhan']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Ghi chú Kết Quả Phát</td>
                        <td class="custom-weight">{{$table1['ghi_chu_phat']}}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered mt-4">
                <thead class="thead-custom">
                    <tr>
                        <th class="mickey-title" colspan="4" scope="col">THÔNG TIN LỘ TRÌNH</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="custom-weight">STT</td>
                        <td class="custom-weight">Ngày đến</td>
                        <td class="custom-weight">Trạng thái</td>
                        <td class="custom-weight">Bưu cục</td>
                    </tr>
                    @foreach($table as $item)
                    <tr>
                        <td>{{$item['stt']}}</td>
                        <td>{{$item['ngay_den'] . ' ' . $item['gio']}}</td>
                        <td>{{$item['trang_thai']}}</th>
                        <td>{{$item['dchi']}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
        font-weight: 700;
    }
    .custom-padding {
        padding: 0 10%
    }
    @media only screen and (max-width: 600px) {
        .custom-padding {
            padding: 0;
        }
        .custom-image {
            margin-top: 20px;
        }
    }
</style>
