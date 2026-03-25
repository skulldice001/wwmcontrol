<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tắt kiểm tra CSRF khi chạy test để tránh lỗi 419
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // Tắt kiểm tra Vite manifest khi chạy test
        $this->withoutVite();
    }
}
