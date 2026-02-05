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

<div class="welcome-box">
    <h1 class="mb-4">{{ config('app.name') }}</h1>

    @if(session('error'))
        <div class="alert alert-danger mb-4" role="alert" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: .75rem 1.25rem; border: 1px solid transparent; border-radius: .25rem;">
            {{ session('error') }}
        </div>
    @endif

    <p class="mb-4">Welcome to the User Portal. Please sign in with Discord to continue.</p>

    @if(Auth::check())
        <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg btn-block">
            <i class="fas fa-tachometer-alt mr-2"></i> Go to Dashboard
        </a>
    @else
        <a href="{{ route('auth.discord') }}" class="btn btn-primary btn-lg btn-block" style="background-color: #5865F2; border-color: #5865F2;">
            <i class="fab fa-discord mr-2"></i> Login with Discord
        </a>
    @endif

    <div class="mt-4 pt-3 border-top">
        <a href="{{ route('admin.login') }}" class="text-muted small">Are you a staff member? Login here</a>
    </div>
</div>

</body>
</html>
