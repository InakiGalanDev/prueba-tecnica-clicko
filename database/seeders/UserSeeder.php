<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Number of users to create per email domain, on top of the admin user (clicko.es).
     *
     * @var array<string, int>
     */
    private const USERS_PER_DOMAIN = [
        'clicko.es' => 10,
        'gmail.com' => 4,
        'outlook.es' => 2,
        'yahoo.com' => 1,
        'hotmail.com' => 1,
        'icloud.com' => 1,
    ];

    /**
     * Seed 20 users: a known admin to log in with and 19 users spread across several email domains.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@clicko.es',
            'password' => 'password',
        ]);

        foreach (self::USERS_PER_DOMAIN as $domain => $count) {
            for ($index = 0; $index < $count; $index++) {
                User::factory()->create(['email' => fake()->unique()->userName()."@{$domain}"]);
            }
        }
    }
}
