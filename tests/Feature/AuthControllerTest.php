<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonPath('data.email', $user->email);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_cannot_log_in_with_a_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->postJson(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->postJson(route('login'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_an_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('logout'));

        $response->assertNoContent();
        $this->assertGuest('web');
    }
}
