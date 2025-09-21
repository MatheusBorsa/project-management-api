<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function validUserData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Borsa',
            'email' => 'borsa@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ], $overrides);
    }

    protected function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    protected function actingAsUser(User $user): void
    {
        Sanctum::actingAs($user);
    }

    public function test_user_can_register_with_valid_password(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validUserData());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User Registered Successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'borsa@example.com',
        ]);
    }

    public function test_user_cannot_register_with_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validUserData([
            'password' => '123',
            'password_confirmation' => '123',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Error',
            ]);
    }

    public function test_user_cannot_register_with_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/auth/register', $this->validUserData([
            'password_confirmation' => 'WrongPass123!',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Error',
            ]);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = $this->createUser([
            'email' => 'borsa@example.com',
            'password' => Hash::make('StrongPass123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'StrongPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = $this->createUser([
            'email' => 'borsa@example.com',
            'password' => Hash::make('StrongPass123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Authentication Error',
            ]);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = $this->createUser();
        $this->actingAsUser($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out succesfully',
            ]);
    }

    public function test_authenticated_user_can_retrieve_own_data(): void
    {
        $user = $this->createUser();
        $this->actingAsUser($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                    ],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_retrieve_user_data(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}
