<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>THE ZOO | @yield('title')</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
@php
    $user = Auth::user();
    $theme = $user ? $user->themeSetting : null;

    // Default values
    $bodyClass = 'hold-transition sidebar-mini layout-fixed';
    if ($theme && $theme->dark_mode) {
        $bodyClass .= ' dark-mode';
    }
    if ($theme && $theme->accent_color) {
        $bodyClass .= ' ' . $theme->accent_color;
    }

    $navbarClass = 'main-header navbar navbar-expand';
    $navbarClass .= ($theme && $theme->navbar_variant) ? ' ' . $theme->navbar_variant : ' navbar-white navbar-light';

    $sidebarClass = 'main-sidebar elevation-4';
    $sidebarClass .= ($theme && $theme->sidebar_variant) ? ' ' . $theme->sidebar_variant : ' sidebar-dark-primary';

    $brandClass = 'brand-link';
    if ($theme && $theme->brand_logo_variant) {
        $brandClass .= ' ' . $theme->brand_logo_variant;
    } else {
         // Default brand link class if no variant is set, but usually it's just 'brand-link' plus maybe a color?
         // AdminLTE defaults: brand-link (plus bg color if desired).
         // If I look at original code: <a href... class="{{ $brandClass }}">
         // I'll keep it simple.
    }

    $contentWrapperClass = 'content-wrapper';
    if ($theme && $theme->background_color) {
        $contentWrapperClass .= ' ' . $theme->background_color;
    }
@endphp
<body class="{{ $bodyClass }}">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="{{ $navbarClass }}">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- User Name -->
            <li class="nav-item d-none d-sm-inline-block">
                <a href="#" class="nav-link font-weight-bold">
                    {{ Auth::user()->name ?? Auth::guard('staff')->user()->name ?? 'Guest' }}
                </a>
            </li>

            @auth
            <!-- Z-Coin Balance -->
            @php
                $zTotal    = Auth::user()->z_coins        ?? 0;
                $zFrozen   = Auth::user()->z_coins_frozen ?? 0;
                $zAvail    = $zTotal - $zFrozen;
            @endphp
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link" title="{{ $zFrozen > 0 ? __('messages.zcoin_frozen').': '.number_format($zFrozen).' Zoo' : 'Zoo' }}">
                    <i class="fas fa-coins" style="color:#f6c23e;"></i>
                    <strong id="nav-zcoin-balance" style="color:#f6c23e;">{{ number_format($zAvail) }}</strong>
                    <small class="text-muted ml-1">Zoo</small>
                    @if($zFrozen > 0)
                        <small class="ml-1" style="color:#e74c3c;" title="{{ __('messages.zcoin_frozen') }}: {{ number_format($zFrozen) }} Zoo">
                            <i class="fas fa-lock"></i>
                        </small>
                    @endif
                </span>
            </li>
            @endauth

            @php
                $vnFlagPath = file_exists(public_path('flags/vn.jpg'))
                    ? 'flags/vn.jpg'
                    : (file_exists(public_path('flags/vn.png'))
                        ? 'flags/vn.png'
                        : 'flags/vn.svg');
                $currentFlag = App::getLocale() == 'vi' ? $vnFlagPath : 'flags/us.svg';
            @endphp
            <!-- Language Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <img src="{{ asset($currentFlag) }}"
                         alt="{{ App::getLocale() == 'vi' ? __('messages.vietnamese') : __('messages.english') }}"
                         class="lang-flag">
                    <span class="d-none d-md-inline ml-2">
                        {{ App::getLocale() == 'vi' ? __('messages.vietnamese') : __('messages.english') }}
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('lang.switch', 'en') }}" class="dropdown-item {{ App::getLocale() == 'en' ? 'active' : '' }}">
                        <img src="{{ asset('flags/us.svg') }}" alt="{{ __('messages.english') }}" class="lang-flag mr-2"> {{ __('messages.english') }}
                    </a>
                    <a href="{{ route('lang.switch', 'vi') }}" class="dropdown-item {{ App::getLocale() == 'vi' ? 'active' : '' }}">
                        <img src="{{ asset($vnFlagPath) }}" alt="{{ __('messages.vietnamese') }}" class="lang-flag mr-2"> {{ __('messages.vietnamese') }}
                    </a>
                </div>
            </li>

            <li class="nav-item">
                <form action="{{ Auth::guard('staff')->check() ? route('admin.logout') : route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link">
                        <i class="fas fa-sign-out-alt"></i> {{ __('messages.logout') }}
                    </button>
                </form>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="{{ $sidebarClass }}">
        <!-- Brand Logo -->
        <a href="{{ Auth::guard('staff')->check() ? route('admin.dashboard') : route('dashboard') }}" class="{{ $brandClass }}">
            <span class="brand-text font-weight-bold" style="letter-spacing:3px; font-size:16px;">THE ZOO</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    @php
                        $currentUser = Auth::user();
                        $currentStaff = Auth::guard('staff')->user();
                        $avatarUrl = null;

                        if ($currentUser) {
                            if ($currentUser->avatar) {
                                $avatarUrl = asset('storage/' . $currentUser->avatar);
                            } elseif ($currentUser->discord_avatar) {
                                $avatarUrl = $currentUser->discord_avatar;
                            } else {
                                $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($currentUser->name);
                            }
                        } elseif ($currentStaff) {
                             $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($currentStaff->name);
                        }
                    @endphp
                    @if($avatarUrl)
                        <img src="{{ $avatarUrl }}" class="img-circle elevation-2" alt="User Image" style="width: 33px; height: 33px; object-fit: cover;">
                    @endif
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{ Auth::user()->name ?? Auth::guard('staff')->user()->name ?? 'Guest' }}</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->
                    @if(Auth::guard('staff')->check())
                        @php $staffUser = Auth::guard('staff')->user(); @endphp
                        <li class="nav-item">
                            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>{{ __('messages.dashboard') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.profile.edit') }}" class="nav-link {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-cog"></i>
                                <p>{{ __('messages.profile_info') }}</p>
                            </a>
                        </li>
                        @if(!$staffUser->isLibrarian())
                        <li class="nav-item">
                            <a href="{{ route('admin.events.index') }}" class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>{{ __('messages.events_management') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ (request()->routeIs('admin.users.*') && !request()->routeIs('admin.users.create')) ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>{{ __('messages.guild_members') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.users.create') }}" class="nav-link {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user-plus"></i>
                                <p>{{ __('messages.create_new_user') }}</p>
                            </a>
                        </li>
                        @endif
                        @if($staffUser->isAdmin())
                        <li class="nav-item">
                            <a href="{{ route('admin.staff.index') }}" class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users-cog"></i>
                                <p>{{ __('messages.staff_management') }}</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('admin.library.index') }}" class="nav-link {{ request()->routeIs('admin.library.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book"></i>
                                <p>{{ __('messages.nav_library') }}</p>
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>{{ __('messages.dashboard') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-user"></i>
                                <p>{{ __('messages.profile_info') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('skills.edit') }}" class="nav-link {{ request()->routeIs('skills.edit') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-khanda"></i>
                                <p>{{ __('messages.my_skills') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('events.index') }}" class="nav-link {{ request()->routeIs('events.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-check"></i>
                                <p>{{ __('messages.available_events') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('entertainment.index') }}" class="nav-link {{ request()->routeIs('entertainment.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-gamepad"></i>
                                <p>{{ __('messages.entertainment_hall') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('library.index') }}" class="nav-link {{ request()->routeIs('library.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-book-open"></i>
                                <p>{{ __('messages.nav_library') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link disabled" style="opacity:.55; cursor:not-allowed;">
                                <i class="nav-icon fas fa-gift"></i>
                                <p>
                                    {{ __('messages.nav_redeem') }}
                                    <span class="badge badge-secondary ml-1" style="font-size:.65rem;">{{ __('messages.nav_coming_soon') }}</span>
                                </p>
                            </a>
                        </li>
                    @endif
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="{{ $contentWrapperClass }}">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('title')</h1>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid" id="app">
                @yield('content')
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer">
        <strong>Copyright &copy; {{ date('Y') }} <a href="#">{{ config('app.name') }}</a>.</strong>
        {{ __('messages.all_rights_reserved') }}
    </footer>
</div>
<!-- ./wrapper -->
@stack('scripts')
</body>
</html>
