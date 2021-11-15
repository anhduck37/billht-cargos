<div class="table-responsive mt-4">
    <table class="table align-items-center">
        <thead style="background-color: #f6821f; color: white" class="thead-light">
        <tr>
            <th>Họ và tên</th>
            <th>Email</th>
            <th>Level</th>
            <th>Trạng thái</th>
            <th class="text-center" colspan="2">Hành động</th>
        </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
        <tr>
            <th scope="row">
                <div class="media align-items-center">
                    <div class="media-body">
                        <span class="mb-0 text-sm">{{$user->name}}</span>
                    </div>
                </div>
            </th>
            <td>{{$user->email}}</td>
            <td>{{$user->level_name}}</td>
            <td>{{$user->status_name}}</td>
            <td class="text-center">
                <div class="dropdown">
                    <a class="btn btn-sm btn-icon-only text-light" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                        <a class="dropdown-item" href="{{ route('users.edit', [$user->id]) }}">Chỉnh sửa</a>
                        <a class="dropdown-item delete" data-id="{{$user->id}}">Xóa
                        </a>
                        {!! Form::open(['route' => ['users.destroy', $user->id], 'method' => 'delete', 'class' => ['removeUser'.$user->id],'style' => 'display: none']) !!}
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
                   $(`.removeUser${userId}`).submit()
               }
           })
        });
    </script>
@endsection

