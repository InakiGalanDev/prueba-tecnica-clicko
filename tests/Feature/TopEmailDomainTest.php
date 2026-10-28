<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopEmailDomainTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the given number of users for each email domain.
     *
     * @param  array<string, int>  $usersPerDomain
     */
    private function createUsersWithDomains(array $usersPerDomain): void
    {
        foreach ($usersPerDomain as $domain => $count) {
            for ($index = 0; $index < $count; $index++) {
                User::factory()->create(['email' => fake()->unique()->userName()."@{$domain}"]);
            }
        }
    }

    public function test_it_returns_the_three_most_used_domains_in_descending_order(): void
    {
        $this->createUsersWithDomains([
            'outlook.es' => 2,
            'clicko.es' => 5,
            'yahoo.com' => 1,
            'gmail.com' => 3,
        ]);

        $response = $this->actingAs(User::first())->getJson(route('users.email-domains.top'));

        $response->assertOk()->assertExactJson([
            'clicko.es' => 5,
            'gmail.com' => 3,
            'outlook.es' => 2,
        ]);
        $this->assertSame(['clicko.es', 'gmail.com', 'outlook.es'], array_keys($response->json()));
    }

    public function test_domains_are_grouped_case_insensitively(): void
    {
        User::factory()->create(['email' => 'ana@Gmail.com']);
        User::factory()->create(['email' => 'luis@gmail.com']);

        $response = $this->actingAs(User::first())->getJson(route('users.email-domains.top'));

        $response->assertOk()->assertExactJson(['gmail.com' => 2]);
    }

    public function test_guests_cannot_access_the_endpoint(): void
    {
        $this->getJson(route('users.email-domains.top'))->assertUnauthorized();
    }
}
