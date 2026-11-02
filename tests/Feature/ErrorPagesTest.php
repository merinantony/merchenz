<?php

namespace Tests\Feature;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_page_renders_with_custom_theme(): void
    {
        $response = $this->get('/non-existent-tenant-route-' . uniqid());

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('Resource Not Found');
        $response->assertSee('Dark');
        $response->assertSee('White');
        $response->assertDontSee('BLACK & RED');
        $response->assertDontSee('RED & WHITE');
    }

    public function test_403_page_renders_when_user_is_unauthorized(): void
    {
        $merchant = Merchant::create([
            'name' => 'Demo Tenant',
            'email' => 'tenant@example.com',
            'api_key' => 'key_test_403',
        ]);

        $user = User::create([
            'name' => 'Tenant User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_USER,
            'merchant_id' => $merchant->id,
        ]);

        $this->actingAs($user);

        // Attempting to visit super admin dashboard gives 403 with custom error view
        $response = $this->get(route('superadmin.dashboard'));

        $response->assertStatus(403);
        $response->assertSee('403');
        $response->assertSee('Access Forbidden');
        $response->assertSee('Privileged Boundary Restriction');
        $response->assertSee('Dark');
        $response->assertSee('White');
    }

    public function test_503_unavailable_page_renders_properly(): void
    {
        $response = $this->get('/unavailable');

        $response->assertStatus(503);
        $response->assertSee('503');
        $response->assertSee('Server Engine Not Started');
        $response->assertSee('Dark');
        $response->assertSee('White');
    }

    public function test_static_unavailable_html_exists_in_public_folder(): void
    {
        $path = public_path('unavailable.html');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('Server is Not Started', $content);
        $this->assertStringContainsString('php artisan serve', $content);
        $this->assertStringContainsString('Dark', $content);
        $this->assertStringContainsString('White', $content);
    }
}

