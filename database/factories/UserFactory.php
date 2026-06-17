<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'member',
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->roles()->count() === 0) {
                Role::firstOrCreate(['name' => $user->role ?: 'member']);
                $user->assignRole($user->role ?: 'member');
            }
        });
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ])->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'admin']);
            $user->assignRole('admin');
        });
    }

    public function coach(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'coach',
        ])->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'coach']);
            $user->assignRole('coach');
        });
    }

    public function guardian(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guardian',
        ])->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'guardian']);
            $user->assignRole('guardian');
        });
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'member',
        ])->afterCreating(function (User $user) {
            Role::firstOrCreate(['name' => 'member']);
            $user->assignRole('member');
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
