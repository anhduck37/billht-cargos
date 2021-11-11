<div class="table-responsive mt-4">
    <table class="table align-items-center">
        <thead class="thead-light">
        <tr>
            <th>STT</th>
            <th>Tên đối tác</th>
            <th>Prefix code</th>
            <th class="text-center" colspan="2">Hành động</th>
        </tr>
        </thead>
        <tbody>
        @foreach($partners as $key => $partner)
        <tr>
            <th>{{$key + 1}}</th>
            <th scope="row">
                <div class="media align-items-center">
                    <div class="media-body">
                        <span class="mb-0 text-sm">{{$partner->name}}</span>
                    </div>
                </div>
            </th>
            <td>{{$partner->prefix_code}}</td>
            <td class="text-center">
                <div class="dropdown">
                    <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                        <a class="dropdown-item" href="{{ route('partners.edit', [$partner->id]) }}">Chỉnh sửa</a>
                        <a class="dropdown-item delete" data-id="{{$partner->id}}">Xóa
                        </a>
                        {!! Form::open(['route' => ['partners.destroy', $partner->id], 'method' => 'delete', 'class' => ['removePartner'.$partner->id],'style' => 'display: none']) !!}
                        {!! Form::close() !!}
                    </div>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>

</div>
@section('javascript')
    <script type="text/javascript">
        $(function() {
           $('.delete').on('click',function () {
               let userId = $(this).attr('data-id');
               let isDelete = confirm('Bạn có chắc muốn xóa tài khoản này?');
               if(isDelete) {
                   $(`.removePartner${userId}`).submit()
               }
           })
        });
    </script>
@endsection

