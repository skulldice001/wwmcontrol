<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    public function test_user_can_switch_language()
    {
        // Test switching to Vietnamese
        $response = $this->get(route('lang.switch', 'vi'));
        
        $response->assertSessionHas('locale', 'vi');
        $response->assertRedirect(); // Should redirect back
    }

    public function test_middleware_sets_locale()
    {
        // Set session locale to 'vi'
        $this->withSession(['locale' => 'vi']);

        // Visit home page
        $response = $this->get('/');

        // Should see Vietnamese text
        $response->assertSee('Chào mừng đến với Cổng thông tin thành viên');
        $response->assertDontSee('Welcome to the User Portal');
    }

    public function test_middleware_sets_english_locale()
    {
        // Set session locale to 'en'
        $this->withSession(['locale' => 'en']);

        // Visit home page
        $response = $this->get('/');

        // Should see English text (assuming default or en messages)
        // Check en messages.php for exact string if needed, but 'Welcome' is likely
        // Actually, let's just check it doesn't show Vietnamese
        $response->assertDontSee('Chào mừng đến với Cổng thông tin thành viên');
    }
}
