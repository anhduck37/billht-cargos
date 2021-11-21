<nav class="navbar navbar-vertical fixed-left navbar-expand-md navbar-light" style="background-color:#333537; color: white" id="sidenav-main">
    <div class="container-fluid">
        <!-- Toggler -->
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidenav-collapse-main" aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
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
                    <a href="{{ route('users.show', [auth()->user()->id]) }}" class="dropdown-item">
                        <i class="ni ni-single-02"></i>
                        <span>{{ __('My profile') }}</span>
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
                <div class="row">
                    <div class="col-6 collapse-brand">
                        <a href="#">
                            <img src="{{ asset('image/order_manager.png') }}">
                        </a>
                    </div>
                    <div class="col-6 collapse-close">
                        <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#sidenav-collapse-main" aria-controls="sidenav-main" aria-expanded="false" aria-label="Toggle sidenav">
                            <span></span>
                            <span></span>
                        </button>
                    </div>
                </div>
            </div>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#navbar-order" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="navbar-examples">
                        <i class="fas fa-clipboard-list" style="color: #f4645f;"></i>
                        <span class="nav-link-text">Vận đơn</span>
                    </a>

                    <div class="collapse show" id="navbar-order">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link @if(Request::url() == route('orders.index')) active-custom @endif" href="{{route('orders.index')}}">
                                    Quản lý vận đơn
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if(Request::url() == route('orders.create')) active-custom @endif" href="{{route('orders.create')}}">
                                    Tạo vận đơn
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            </ul>
            @if(auth()->user()->level == \App\User::LEVEL_ADMIN)
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#navbar-partner" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="navbar-examples">
                            <i class="fas fa-shipping-fast" style="color: #f4645f;"></i>
                            <span class="nav-link-text">Đơn vị vận chuyển</span>
                        </a>

                        <div class="collapse show" id="navbar-partner">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a class="nav-link @if(Request::url() == route('partners.index')) active-custom @endif" href="{{route('partners.index')}}">
                                        Quản lý đơn vị vận chuyển
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link @if(Request::url() == route('partners.create')) active-custom @endif" href="{{route('partners.create')}}">
                                        Tạo đơn vị vận chuyển
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" href="#navbar-examples" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="navbar-examples">
                        <i class="fas fa-users" style="color: #f4645f;"></i>
                        <span class="nav-link-text">Tài khoản</span>
                    </a>

                    <div class="collapse show" id="navbar-examples">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link @if(Request::url() == route('users.index')) active-custom @endif" href="{{route('users.index')}}">
                                Quản lý tài khoản
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link @if(Request::url() == route('users.create')) active-custom @endif" href="{{route('users.create')}}">
                                    Tạo tài khoản
                                </a>
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
            </ul>
            <!-- Divider -->
            <hr class="my-3">
            <!-- Heading -->
{{--            <h6 class="navbar-heading text-muted">Đắng xuất</h6>--}}
            <!-- Navigation -->
            <ul class="navbar-nav mb-md-3">
                <li class="nav-item">
                    <a href="{{ route('logout') }}" class="nav-link" href="https://argon-dashboard-laravel.creative-tim.com/docs/getting-started/overview.html" onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();">
                        <i class="ni ni-user-run"></i>
                        <span>{{ __('Logout') }}</span>
                    </a>
{{--                    <a class="nav-link" href="https://argon-dashboard-laravel.creative-tim.com/docs/getting-started/overview.html">--}}
{{--                        <i class="ni ni-spaceship"></i> Đắng xuất--}}
{{--                    </a>--}}
                </li>
{{--                <li class="nav-item">--}}
{{--                    <a class="nav-link" href="https://argon-dashboard-laravel.creative-tim.com/docs/foundation/colors.html">--}}
{{--                        <i class="ni ni-palette"></i> Foundation--}}
{{--                    </a>--}}
{{--                </li>--}}
{{--                <li class="nav-item">--}}
{{--                    <a class="nav-link" href="https://argon-dashboard-laravel.creative-tim.com/docs/components/alerts.html">--}}
{{--                        <i class="ni ni-ui-04"></i> Components--}}
{{--                    </a>--}}
{{--                </li>--}}
            </ul>
        </div>
    </div>
</nav>

