<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_lifetime_is_set_to_thirty_minutes(): void
    {
        $lifetime = config('session.lifetime');

        $this->assertEquals(30, $lifetime);
    }

    public function test_authenticated_user_cannot_access_protected_page_after_session_expiry(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->assertAuthenticated();

        config(['session.lifetime' => 0]);

        $this->assertAuthenticated();

        $response = $this->get('/home');

        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect('/login');
    }
}
