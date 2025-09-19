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

    #[Test]
    public function user_can_register_with_valid_password()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Borsa',
            'email' => 'borsa@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User Registered Successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'borsa@example.com',
        ]);
    }

    #[Test]
    public function user_cannot_register_with_weak_password()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Weak User',
            'email' => 'weak@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Error',
            ]);
    }

    #[Test]
    public function user_cannot_register_with_mismatched_password_confirmation()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Mismatch User',
            'email' => 'mismatch@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass321!',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation Error',
            ]);
    }

    #[Test]
    public function user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'borsa@example.com',
            'password' => Hash::make('StrongPass123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'borsa@example.com',
            'password' => 'StrongPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
    }

    #[Test]
    public function user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'borsa@example.com',
            'password' => Hash::make('StrongPass123!'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'borsa@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Authentication Error',
            ]);
    }

    #[Test]
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out succesfully',
            ]);
    }

    #[Test]
    public function authenticated_user_can_retrieve_own_data()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

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

    #[Test]
    public function unauthenticated_user_cannot_retrieve_user_data()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }
}
