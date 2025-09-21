<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use App\Enums\ClientUserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $owner;
    private User $member;
    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->unauthorizedUser = User::factory()->create();
    }

    public function test_creates_client_with_valid_data(): void
    {
        $clientData = $this->validClientData();

        $response = $this->actingAs($this->owner)
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Client created successfully',
            ]);

        $this->assertDatabaseHas('clients', [
            'name' => $clientData['name'],
            'email' => $clientData['email'],
        ]);
    }

    public function test_fails_to_create_client_with_missing_required_fields(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/clients', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['success', 'message', 'errors', 'status']);
    }

    public function test_fails_to_create_client_with_invalid_email(): void
    {
        $clientData = $this->validClientData(['email' => 'invalid-email']);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_fails_to_create_client_with_duplicate_email(): void
    {
        $existingClient = Client::factory()->create();
        $clientData = $this->validClientData(['email' => $existingClient->email]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_requires_authentication_to_create_client(): void
    {
        $response = $this->postJson('/api/clients', $this->validClientData());

        $response->assertStatus(401);
    }

    public function test_returns_clients_for_authenticated_user(): void
    {
        $clients = Client::factory()->count(3)->create();
        
        $this->owner->clients()->attach($clients->take(2)->pluck('id')->toArray());

        $response = $this->actingAs($this->owner)
            ->getJson('/api/clients');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Clients retrieved successfully',
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_returns_empty_list_when_user_has_no_clients(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/clients');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(0, 'data');
    }

    public function test_requires_authentication_to_list_clients(): void
    {
        $response = $this->getJson('/api/clients');

        $response->assertStatus(401);
    }

    public function test_returns_client_details_for_authorized_user(): void
    {
        $client = $this->createClientWithOwner($this->owner);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/clients/{$client->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Client retrieved successfully',
            ]);
    }

    public function test_allows_member_to_view_client_details(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $this->member->clients()->attach($client->id);

        $response = $this->actingAs($this->member)
            ->getJson("/api/clients/{$client->id}");

        $response->assertStatus(200);
    }

    public function test_denies_access_to_unauthorized_user(): void
    {
        $client = $this->createClientWithOwner($this->owner);

        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson("/api/clients/{$client->id}");

        $response->assertStatus(403);
    }

    public function test_returns_404_for_nonexistent_client(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/clients/99999");

        $response->assertStatus(404);
    }

    public function test_updates_client_with_valid_data(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $updateData = [
            'name' => 'Updated Client Name',
            'contact_name' => 'Updated Contact',
            'phone' => '+1234567890',
        ];

        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/{$client->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Client updated successfully',
            ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Client Name',
        ]);
    }

    public function test_allows_partial_updates(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $originalName = $client->name;

        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/{$client->id}", [
                'contact_name' => 'New Contact Name',
            ]);

        $response->assertStatus(200);
        
        $client->refresh();
        $this->assertEquals($originalName, $client->name);
    }

    public function test_allows_update_for_member(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $this->member->clients()->attach($client->id);

        $response = $this->actingAs($this->member)
            ->putJson("/api/clients/{$client->id}", ['name' => 'Updated Name']);

        $response->assertStatus(200);
    }

    public function test_validates_update_data(): void
    {
        $client = $this->createClientWithOwner($this->owner);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/{$client->id}", [
                'email' => 'invalid-email',
                'name' => '',
            ]);

        $response->assertStatus(422);
    }

    public function test_returns_404_for_update_nonexistent_client(): void
    {
        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/99999", ['name' => 'Test']);

        $response->assertStatus(404);
    }

    public function test_deletes_client_successfully(): void
    {
        $client = $this->createClientWithOwner($this->owner);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Client removed successfully'
            ]);

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_allows_deletion_for_member(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $this->member->clients()->attach($client->id);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/clients/{$client->id}");

        $response->assertStatus(200);
    }

    public function test_returns_404_for_delete_nonexistent_client(): void
    {
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/clients/99999");

        $response->assertStatus(404);
    }

    public function test_returns_client_users(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $this->member->clients()->attach($client->id);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/clients/{$client->id}/users");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data.users');
    }

    public function test_denies_user_role_update_for_non_owner(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $this->member->clients()->attach($client->id);

        $response = $this->actingAs($this->member)
            ->putJson("/api/clients/{$client->id}/users/{$this->owner->id}", [
                'role' => ClientUserRole::OWNER->value,
            ]);

        $response->assertStatus(403);
    }

    public function test_validates_user_role_update(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $this->member->clients()->attach($client->id);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/{$client->id}/users/{$this->member->id}", [
                'role' => 'invalid_role',
            ]);

        $response->assertStatus(422);
    }

    public function test_denies_user_removal_for_non_owner(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $this->member->clients()->attach($client->id);

        $response = $this->actingAs($this->member)
            ->deleteJson("/api/clients/{$client->id}/users/{$this->owner->id}");

        $response->assertStatus(403);
    }

    public function test_denies_owner_self_removal(): void
    {
        $client = $this->createClientWithOwner($this->owner);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/clients/{$client->id}/users/{$this->owner->id}");

        $response->assertStatus(403);
    }

    public function test_denies_user_management_for_non_owners(): void
    {
        $client = $this->createClientWithOwner($this->owner);
        $this->member->clients()->attach($client->id);
        $this->unauthorizedUser->clients()->attach($client->id);

        $updateResponse = $this->actingAs($this->member)
            ->putJson("/api/clients/{$client->id}/users/{$this->unauthorizedUser->id}", [
                'role' => ClientUserRole::OWNER->value,
            ]);

        $updateResponse->assertStatus(403);

        $removeResponse = $this->actingAs($this->member)
            ->deleteJson("/api/clients/{$client->id}/users/{$this->unauthorizedUser->id}");

        $removeResponse->assertStatus(403);
    }

    public function test_denies_access_to_nonexistent_user_in_client(): void
    {
        $client = $this->createClientWithOwner($this->owner);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/{$client->id}/users/99999", [
                'role' => ClientUserRole::PARTICIPANT->value,
            ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_update_user_role(): void
    {
        $client = Client::factory()->create();
        $this->owner->clients()->attach($client->id, ['role' => ClientUserRole::OWNER->value]);
        $this->member->clients()->attach($client->id, ['role' => ClientUserRole::PARTICIPANT->value]);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/{$client->id}/users/{$this->member->id}", [
                'role' => ClientUserRole::OWNER->value,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_owner_can_remove_user(): void
    {
        $client = Client::factory()->create();
        $this->owner->clients()->attach($client->id, ['role' => ClientUserRole::OWNER->value]);
        $this->member->clients()->attach($client->id, ['role' => ClientUserRole::PARTICIPANT->value]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/clients/{$client->id}/users/{$this->member->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    private function validClientData(array $overrides = []): array
    {
        return array_merge([
            'name' => $this->faker->company,
            'contact_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'notes' => $this->faker->paragraph,
        ], $overrides);
    }

    private function createClientWithOwner(User $owner): Client
    {
        $client = Client::factory()->create();
        $owner->clients()->attach($client->id);
        
        return $client;
    }
}