<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }} | @yield('title')</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
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
                         alt="{{ App::getLocale() == 'vi' ? 'Tiếng Việt' : 'English' }}"
                         class="lang-flag">
                    <span class="d-none d-md-inline ml-2">
                        {{ App::getLocale() == 'vi' ? 'Tiếng Việt' : 'English' }}
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('lang.switch', 'en') }}" class="dropdown-item {{ App::getLocale() == 'en' ? 'active' : '' }}">
                        <img src="{{ asset('flags/us.svg') }}" alt="English" class="lang-flag mr-2"> English
                    </a>
                    <a href="{{ route('lang.switch', 'vi') }}" class="dropdown-item {{ App::getLocale() == 'vi' ? 'active' : '' }}">
                        <img src="{{ asset($vnFlagPath) }}" alt="Tiếng Việt" class="lang-flag mr-2"> Tiếng Việt
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
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <span class="brand-text font-weight-light">{{ config('app.name', 'AdminLTE') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <a href="#" class="d-block">{{ Auth::user()->name ?? Auth::guard('staff')->user()->name ?? 'Guest' }}</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->
                    @if(Auth::guard('staff')->check())
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
                        <li class="nav-item">
                            <a href="{{ route('admin.events.index') }}" class="nav-link {{ request()->routeIs('admin.events.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>{{ __('messages.events_management') }}</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>{{ __('messages.guild_members') }}</p>
                            </a>
                        </li>
                        @if(Auth::guard('staff')->user()->isAdmin())
                        <li class="nav-item">
                            <a href="{{ route('admin.staff.index') }}" class="nav-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users-cog"></i>
                                <p>{{ __('messages.staff_management') }}</p>
                            </a>
                        </li>
                        @endif
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
                    @endif
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
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
        All rights reserved.
    </footer>
</div>
<!-- ./wrapper -->
@stack('scripts')
</body>
</html>
