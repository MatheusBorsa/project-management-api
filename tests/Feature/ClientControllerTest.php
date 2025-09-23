<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\ClientUserRole;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
    }

    private function validClientData(): array
    {
        return [
            'name' => 'Test Client',
            'contact_name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'notes' => 'Some notes',
            'cnpj' => '11.222.333/0001-81', // valid CNPJ
            'status' => 'active',
        ];
    }

    public function test_creates_client_with_valid_data(): void
    {
        $clientData = $this->validClientData();

        $response = $this->actingAs($this->owner)
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Client created successfully']);

        $this->assertDatabaseHas('clients', ['name' => $clientData['name']]);
        $this->assertDatabaseHas('client_user', [
            'user_id' => $this->owner->id,
            'role' => ClientUserRole::OWNER->value
        ]);
    }

    public function test_requires_valid_cnpj(): void
    {
        $clientData = $this->validClientData();
        $clientData['cnpj'] = 'invalid-cnpj';

        $response = $this->actingAs($this->owner)
            ->postJson('/api/clients', $clientData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }

    public function test_updates_client_with_valid_data(): void
    {
        $client = Client::factory()->create();
        $client->users()->attach($this->owner->id, ['role' => ClientUserRole::OWNER->value]);

        $updateData = ['name' => 'Updated Name'];

        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/{$client->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Client updated successfully']);

        $client->refresh();
        $this->assertEquals('Updated Name', $client->name);
    }

    public function test_prevents_unauthorized_user_from_updating_client(): void
    {
        $client = Client::factory()->create();

        $response = $this->actingAs($this->member)
            ->putJson("/api/clients/{$client->id}", ['name' => 'Hack Attempt']);

        $response->assertStatus(403);
    }

    public function test_owner_cannot_remove_self(): void
    {
        $client = Client::factory()->create();
        $client->users()->attach($this->owner->id, ['role' => ClientUserRole::OWNER->value]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/clients/{$client->id}/users/{$this->owner->id}");

        $response->assertStatus(403);
    }

    public function test_owner_can_update_user_role(): void
    {
        $client = Client::factory()->create();
        $client->users()->attach($this->owner->id, ['role' => ClientUserRole::OWNER->value]);
        $client->users()->attach($this->member->id, ['role' => ClientUserRole::PARTICIPANT->value]);

        $response = $this->actingAs($this->owner)
            ->putJson("/api/clients/{$client->id}/users/{$this->member->id}", ['role' => ClientUserRole::OWNER->value]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('client_user', [
            'user_id' => $this->member->id,
            'role' => ClientUserRole::OWNER->value
        ]);
    }

    public function test_owner_can_remove_other_user(): void
    {
        $client = Client::factory()->create();
        $client->users()->attach($this->owner->id, ['role' => ClientUserRole::OWNER->value]);
        $client->users()->attach($this->member->id, ['role' => ClientUserRole::PARTICIPANT->value]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/clients/{$client->id}/users/{$this->member->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('client_user', [
            'user_id' => $this->member->id,
            'client_id' => $client->id
        ]);
    }
}
