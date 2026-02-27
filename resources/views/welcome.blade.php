<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | Welcome</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        .welcome-page {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f4f6f9;
        }
        .welcome-box {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
    </style>
</head>
<body class="welcome-page">

@php
    $vnFlagPath = file_exists(public_path('flags/vn.jpg'))
        ? 'flags/vn.jpg'
        : (file_exists(public_path('flags/vn.png'))
            ? 'flags/vn.png'
            : 'flags/vn.svg');
@endphp
<div style="position: absolute; top: 20px; right: 20px;">
    <a href="{{ route('lang.switch', 'en') }}" class="{{ App::getLocale() == 'en' ? 'font-weight-bold text-dark' : 'text-muted' }} mr-2">
        <img src="{{ asset('flags/us.svg') }}" alt="EN" class="lang-flag mr-1"> EN
    </a>
    <span class="text-muted">|</span>
    <a href="{{ route('lang.switch', 'vi') }}" class="{{ App::getLocale() == 'vi' ? 'font-weight-bold text-dark' : 'text-muted' }} ml-2">
        <img src="{{ asset($vnFlagPath) }}" alt="VN" class="lang-flag mr-1"> VN
    </a>
</div>

<div class="welcome-box">
    <h1 class="mb-4">{{ config('app.name') }}</h1>
    
    @if (session('error'))
        <div class="alert alert-danger mb-4" role="alert" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: .75rem 1.25rem; border: 1px solid transparent; border-radius: .25rem;">
            {{ session('error') }}
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success mb-4" role="alert" style="color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: .75rem 1.25rem; border: 1px solid transparent; border-radius: .25rem;">
            {{ session('success') }}
        </div>
    @endif

    <p class="mb-4">{{ __('messages.welcome_message') }}</p>
    
    @if(Auth::check())
        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg btn-block">
            <i class="fas fa-tachometer-alt mr-2"></i> {{ __('messages.go_to_dashboard') }}
        </a>
    @else
        <a href="{{ route('auth.discord') }}" class="btn btn-primary btn-lg btn-block" style="background-color: #5865F2; border-color: #5865F2;">
            <i class="fab fa-discord mr-2"></i> {{ __('messages.login_discord') }}
        </a>
    @endif

    <div class="mt-4 pt-3 border-top">
        <a href="{{ route('admin.login') }}" class="text-muted small">{{ __('messages.staff_login_link') }}</a>
    </div>
</div>

</body>
</html>
