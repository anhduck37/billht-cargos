<div class="row">
    <div class="col">
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
                {{-- @dd($viettel_post) --}}
                @foreach($viettel_post as $item)
                    {{-- <tr>
                        <td class="viettel-post-title" colspan="3">{{$item['STATUS_NAME']}}</td>
                    </tr> --}}

                    @foreach($item['TRACKINGS'] as $tracking)
                        <tr>
                            <td class="custom-size">{{$tracking['THOI_GIAN']}}</td>
                            <td class="custom-size">{{$tracking['STATUS_NAME']}}</th>
                            <td class="custom-size">{{$tracking['NOI_DUNG']}}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
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