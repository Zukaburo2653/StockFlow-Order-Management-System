<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The root URL redirects unauthenticated users to /login.
     * An authenticated user gets redirected to /dashboard.
     * Both are valid 302 responses — the app is working correctly.
     */
    public function test_root_redirects_to_dashboard(): void
    {
        // The root route does: redirect()->route('dashboard')
        // which is /dashboard — regardless of auth state.
        // The auth middleware on /dashboard then redirects guests to /login.
        $response = $this->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
    }

    /**
     * Authenticated users hitting / are redirected to /dashboard.
     */
    public function test_root_redirects_authenticated_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
    }

    /**
     * The login page loads successfully (200).
     */
    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}