<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use App\Enums\TaskStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $owner;
    private User $participant;
    private User $unauthorizedUser;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->owner = User::factory()->create();
        $this->participant = User::factory()->create();
        $this->unauthorizedUser = User::factory()->create();
        $this->client = Client::factory()->create();
        $this->client->users()->attach($this->owner->id, ['role' => 'owner']);
        $this->client->users()->attach($this->participant->id, ['role' => 'participant']);
    }

    public function test_store_creates_task_successfully_for_owner(): void
    {
        $taskData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'deadline' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'status' => TaskStatus::PENDING->value,
            'assigned_to' => $this->participant->id,
        ];

        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => [
                    'title' => $taskData['title'],
                    'description' => $taskData['description'],
                    'status' => $taskData['status'],
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'client_id' => $this->client->id,
            'title' => $taskData['title'],
            'status' => $taskData['status'],
        ]);
    }

    public function test_store_creates_task_successfully_for_participant(): void
    {
        $taskData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => TaskStatus::PENDING->value,
        ];

        $response = $this->actingAs($this->participant)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Task created successfully',
            ]);
    }

    public function test_store_with_image_upload(): void
    {
        $image = UploadedFile::fake()->image('task-image.jpg', 800, 600)->size(1024);

        $taskData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => TaskStatus::PENDING->value,
            'image_path' => $image,
        ];

        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(201);
        $task = Task::latest()->first();
        $this->assertNotNull($task->image_path);
        $this->assertTrue(Storage::disk('public')->exists($task->image_path));
    }

    public function test_store_fails_for_unauthorized_user(): void
    {
        $taskData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => TaskStatus::PENDING->value,
        ];

        $response = $this->actingAs($this->unauthorizedUser)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You are not authorized',
            ]);
    }

    public function test_store_validation_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/{$this->client->id}/tasks", [
                'title' => '',
                'status' => 'invalid_status',
                'assigned_to' => 99999,
            ]);

        $response->assertStatus(500);
    }

    public function test_store_fails_for_nonexistent_client(): void
    {
        $taskData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => TaskStatus::PENDING->value,
        ];

        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/99999/tasks", $taskData);

        $response->assertStatus(500);
    }

    public function test_update_modifies_task_successfully(): void
    {
        $task = Task::factory()->create([
            'client_id' => $this->client->id,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'status' => TaskStatus::IN_PROGRESS->value,
        ];

        $response = $this->actingAs($this->owner)
            ->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task updated successfully',
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'description' => 'Updated Description',
        ]);
    }

    public function test_update_with_image_upload(): void
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);
        $image = UploadedFile::fake()->image('updated-image.jpg');

        $response = $this->actingAs($this->owner)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated with image',
                'image_path' => $image,
            ]);

        $response->assertStatus(200);
        $task->refresh();
        $this->assertNotNull($task->image_path);
        $this->assertTrue(Storage::disk('public')->exists($task->image_path));
    }

    public function test_update_fails_for_unauthorized_user(): void
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->unauthorizedUser)
            ->putJson("/api/tasks/{$task->id}", ['title' => 'Updated Title']);

        $response->assertStatus(403);
    }

    public function test_update_fails_for_nonexistent_task(): void
    {
        $response = $this->actingAs($this->owner)
            ->putJson("/api/tasks/99999", ['title' => 'Updated Title']);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Task not found',
            ]);
    }

    public function test_update_status_changes_task_status(): void
    {
        $task = Task::factory()->create([
            'client_id' => $this->client->id,
            'status' => TaskStatus::PENDING->value,
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => TaskStatus::IN_PROGRESS->value,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Status updated',
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::IN_PROGRESS->value,
        ]);
    }

    public function test_update_status_fails_with_invalid_status(): void
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(500);
    }

    public function test_show_returns_task_details(): void
    {
        $assignedUser = User::factory()->create();
        $task = Task::factory()->create([
            'client_id' => $this->client->id,
            'assigned_to' => $assignedUser->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task retrieved successfully',
                'data' => ['id' => $task->id],
            ]);
    }

    public function test_show_fails_for_unauthorized_user(): void
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_destroy_deletes_task_successfully(): void
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_destroy_fails_for_unauthorized_user(): void
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->unauthorizedUser)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_destroy_fails_for_nonexistent_task(): void
    {
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/tasks/99999");

        $response->assertStatus(404);
    }

    public function test_index_returns_all_client_tasks(): void
    {
        Task::factory()->count(3)->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/clients/{$this->client->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_fails_for_unauthorized_user(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson("/api/clients/{$this->client->id}/tasks");

        $response->assertStatus(403);
    }

    public function test_index_fails_for_nonexistent_client(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/clients/99999/tasks");

        $response->assertStatus(404);
    }
}
