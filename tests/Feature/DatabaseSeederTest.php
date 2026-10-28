<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_twenty_users_including_the_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 20);

        $admin = User::firstWhere('email', 'admin@clicko.es');
        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_seeded_users_produce_the_expected_top_email_domains(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->actingAs(User::first())->getJson(route('users.email-domains.top'));

        $response->assertOk()->assertExactJson([
            'clicko.es' => 11,
            'gmail.com' => 4,
            'outlook.es' => 2,
        ]);
    }
}
