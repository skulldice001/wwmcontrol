<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/admin/login',
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin/*')) {
                return route('admin.login');
            }
            return route('login');
        });

        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            if ($request->is('admin/*')) {
                return redirect()->route('admin.login')->with('error', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
            }
            return redirect()->route('login')->with('error', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, Request $request) {
            return redirect('/')->with('error', 'Bạn không có quyền truy cập trang này.');
        });
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('events:close-expired')->dailyAt('00:00');
        $schedule->command('zoo:daily-bonus')->dailyAt('00:05');
        $schedule->command('lottery:draw daily')->dailyAt('20:00');
        $schedule->command('lottery:draw weekly')->weeklyOn(6, '21:00');
        $schedule->command('lottery:draw jackpot')->dailyAt('08:30');
        $schedule->command('events:run-lucky-draws')->everyMinute();
    })
    ->create();
