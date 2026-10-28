<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $authenticatedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticatedUser = User::factory()->create();
    }

    public function test_it_lists_users_paginated(): void
    {
        User::factory()->count(2)->create();

        $response = $this->actingAs($this->authenticatedUser)->getJson(route('users.index'));

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'created_at', 'updated_at']],
                'links',
                'meta',
            ]);
    }

    public function test_it_creates_a_user_with_a_hashed_password(): void
    {
        $response = $this->actingAs($this->authenticatedUser)->postJson(route('users.store'), [
            'name' => 'Ana',
            'email' => 'ana@clicko.es',
            'password' => 'secret123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Ana')
            ->assertJsonPath('data.email', 'ana@clicko.es')
            ->assertJsonMissingPath('data.password');

        $createdUser = User::firstWhere('email', 'ana@clicko.es');
        $this->assertNotNull($createdUser);
        $this->assertTrue(Hash::check('secret123', $createdUser->password));
    }

    public function test_it_validates_required_fields_when_creating(): void
    {
        $response = $this->actingAs($this->authenticatedUser)->postJson(route('users.store'), []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_it_rejects_a_duplicated_email_and_a_short_password_when_creating(): void
    {
        $response = $this->actingAs($this->authenticatedUser)->postJson(route('users.store'), [
            'name' => 'Ana',
            'email' => $this->authenticatedUser->email,
            'password' => 'short',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_it_shows_a_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->authenticatedUser)->getJson(route('users.show', $user));

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_it_returns_not_found_for_a_missing_user(): void
    {
        $this->actingAs($this->authenticatedUser)->getJson(route('users.show', 999))->assertNotFound();
    }

    public function test_it_partially_updates_a_user(): void
    {
        $user = User::factory()->create(['name' => 'Ana', 'email' => 'ana@clicko.es']);

        $response = $this->actingAs($this->authenticatedUser)->patchJson(route('users.update', $user), [
            'name' => 'Ana Editada',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Ana Editada')
            ->assertJsonPath('data.email', 'ana@clicko.es');
        $this->assertSame('Ana Editada', $user->fresh()->name);
    }

    public function test_it_allows_keeping_the_same_email_when_updating(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->authenticatedUser)->putJson(route('users.update', $user), [
            'email' => $user->email,
        ]);

        $response->assertOk();
    }

    public function test_it_rejects_an_email_taken_by_another_user_when_updating(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->authenticatedUser)->putJson(route('users.update', $user), [
            'email' => $this->authenticatedUser->email,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_it_deletes_a_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->authenticatedUser)->deleteJson(route('users.destroy', $user));

        $response->assertNoContent();
        $this->assertModelMissing($user);
    }

    public function test_guests_cannot_access_the_users_endpoints(): void
    {
        $user = User::factory()->create();

        $this->getJson(route('users.index'))->assertUnauthorized();
        $this->postJson(route('users.store'), [])->assertUnauthorized();
        $this->getJson(route('users.show', $user))->assertUnauthorized();
        $this->putJson(route('users.update', $user), [])->assertUnauthorized();
        $this->deleteJson(route('users.destroy', $user))->assertUnauthorized();
    }
}
