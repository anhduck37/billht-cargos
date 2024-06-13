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
                        <td>{{$table1['tinh']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Trạng Thái</td>
                        <td>{{$table1['tinh_trang']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Ngày giờ</td>
                        <td>{{$table1['ngay_phat'] . ' ' . $table1['gio_phat']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Địa chỉ nhận</td>
                        <td>{{$table[0]['dchi']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Người Ký Nhận</td>
                        <td>{{$table1['nguoi_nhan']}}</td>
                    </tr>
                    <tr>
                        <td class="custom-weight">Ghi chú Kết Quả Phát</td>
                        <td>{{$table1['ghi_chu_phat']}}</td>
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
                        <td>STT</td>
                        <td>Ngày đến</td>
                        <td>Trạng thái</td>
                        <td>Bưu cục</td>
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
        <img style="max-width: 500px; width:100%" src="{{$table1['img']}}">
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
