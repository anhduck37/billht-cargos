<nav class="navbar navbar-vertical fixed-left navbar-expand-md navbar-light" style="background-color:#333537; color: white" id="sidenav-main">
    <div class="container-fluid">
        <!-- Toggler -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main" aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle navigation">
        <i style="color: white" class="fas fa-bars fa-2x"></i>
        </button>
        <!-- Brand -->
        <a class="navbar-brand pt-0" href="#">
            <img src="{{ asset('image/order_manager.png') }}" class="navbar-brand-img" alt="...">
        </a>
        <!-- User -->
        <ul class="nav align-items-center d-md-none">
            <li class="nav-item dropdown">
                <a class="nav- active" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="media align-items-center">
                        <span class="avatar avatar-sm rounded-circle">
                        <img alt="Image placeholder" src="{{ asset('argon/img/theme/team-1-800x800.jpg') }}">
                        </span>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-right">
                    <div class=" dropdown-header noti-title">
                        <h6 class="text-overflow m-0">{{ __('Welcome!') }}</h6>
                    </div>
                    <a href="#" class="dropdown-item">
                        <i class="ni ni-single-02"></i>
                        <span style="font-weight: 900;">Đã tạo: {{ $totalOrder }} Bill</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('logout') }}" class="dropdown-item" onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();">
                        <i class="ni ni-user-run"></i>
                        <span>{{ __('Logout') }}</span>
                    </a>
                </div>
            </li>
        </ul>
        <!-- Collapse -->
        <div class="collapse navbar-collapse" id="sidenav-collapse-main">
            <!-- Collapse header -->
            <div class="navbar-collapse-header d-md-none">
                <div class="row" style="background-color: #333537;border-radius: 5px;">
                    <div class="col-6 collapse-brand mt-2 mb-2">
                        <a href="#">
                            <img src="{{ asset('image/order_manager.png') }}">
                        </a>
                    </div>
                    <div class="col-6 collapse-close mb-2 mt-2">
                        <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#sidenav-collapse-main" aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle sidenav">
                            <span style="background-color: white;"></span>
                            <span style="background-color: white;"></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link @if(Request::url() == route('orders.createNew')) active-custom @endif" href="{{route('orders.createNew')}}">
                        <i class="fas fa-plus-circle" style="color: #2dce89;"></i>
                        <span class="nav-link-text">Tạo vận đơn</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(Request::url() == route('orders.index')) active-custom @endif" href="{{route('orders.index')}}">
                        <i class="fas fa-list-ul" style="color: #5e72e4;"></i>
                        <span class="nav-link-text">Quản lý vận đơn</span>
                    </a>
                </li>
                @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                <li class="nav-item">
                    <a class="nav-link @if(Request::url() == route('orders.showFormImport')) active-custom @endif" href="{{route('orders.showFormImport')}}">
                        <i class="fas fa-file-import" style="color: #fb6340;"></i>
                        <span class="nav-link-text">Nhập file Excel</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(Request::url() == route('orders.addressImportTool')) active-custom @endif" href="{{route('orders.addressImportTool')}}">
                        <i class="fas fa-search-location" style="color: #2dce89;"></i>
                        <span class="nav-link-text">Kiểm tra địa chỉ</span>
                    </a>
                </li>
                @endif
                @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF]))
                    <li class="nav-item">
                        <a class="nav-link @if(Request::url() == route('order_status_changes.index')) active-custom @endif" href="{{ route('order_status_changes.index') }}">
                            <i class="fas fa-sync-alt" style="color: #11cdef;"></i>
                            <span class="nav-link-text">Cập nhật trạng thái</span>
                        </a>
                    </li>
                @endif
            </ul>

            <hr class="my-3">
            <h6 class="navbar-heading text-muted">Hệ thống</h6>

            @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link " href="#navbar-partner" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-partner">
                            <i class="fas fa-shipping-fast" style="color: #f4645f;"></i>
                            <span class="nav-link-text">Đối tác</span>
                        </a>
                        <div class="collapse" id="navbar-partner">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{route('partners.index')}}">Quản lý đối tác</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{route('partners.create')}}">Tạo đối tác</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="#navbar-users" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-users">
                            <i class="fas fa-users" style="color: #f4645f;"></i>
                            <span class="nav-link-text">Tài khoản</span>
                        </a>
                        <div class="collapse" id="navbar-users">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{route('users.index')}}">Quản lý tài khoản</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{route('users.create')}}">Tạo tài khoản</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            @endif

            <ul class="navbar-nav mb-md-3">
                <li class="nav-item">
                    <a class="nav-link @if(Request::url() == route('tracking')) active-custom @endif" href="{{route('tracking')}}">
                        <i class="fas fa-search-location" style="color: #f4645f;"></i> Tra cứu đơn hàng
                    </a>
                </li>
                @if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN]))
                <li class="nav-item">
                    <a class="nav-link @if(Request::url() == '/zalo') active-custom @endif" href="/zalo" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-comment-dots" style="color: #f4645f;"></i> Tra cứu gửi tin ZALO
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(Request::routeIs('order_partner_logs.index')) active-custom @endif" href="{{ route('order_partner_logs.index') }}">
                        <i class="fas fa-history" style="color: #f4645f;"></i> Đồng bộ API
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link @if(Request::routeIs('order_historys.index')) active-custom @endif" href="{{ route('order_historys.index') }}">
                        <i class="fas fa-list-ul" style="color: #f4645f;"></i> Lịch sử thao tác
                    </a>
                </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link" href="https://ht-cargos.com/lien-he/" target="_blank">
                        <i class="fas fa-address-book" style="color: #f4645f;"></i> Liên hệ
                    </a>
                </li>
            </ul>

            <hr class="my-3">
            <ul class="navbar-nav mb-md-3">
                <li class="nav-item">
                    <a href="{{ route('logout') }}" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="ni ni-user-run"></i>
                        <span>Đăng xuất</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
