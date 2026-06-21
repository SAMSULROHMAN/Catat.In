<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate(): void
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('Password1'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1',
        ]);

        $response->assertValid();
        $this->assertAuthenticated();
        $response->assertRedirect('/home');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('Password1'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'WrongPass1',
        ]);

        $response->assertInvalid();
        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_nonexistent_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password1',
        ]);

        $response->assertInvalid();
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create([
            'password_hash' => bcrypt('Password1'),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_see_login_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/home');
    }
}
