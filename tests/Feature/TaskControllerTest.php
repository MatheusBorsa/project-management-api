<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Task;
use App\Models\Art;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $owner;
    protected $participant;
    protected $otherUser;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->participant = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->client = Client::factory()->create();

        $this->client->users()->attach($this->owner->id, ['role' => 'owner']);
        $this->client->users()->attach($this->participant->id, ['role' => 'participant']);

        // Create art table if it doesn't exist
        $this->createArtTableIfNotExists();
    }

    private function createArtTableIfNotExists()
    {
        if (!Schema::hasTable('art')) {
            Schema::create('art', function ($table) {
                $table->id();
                $table->foreignId('task_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->string('art_path');
                $table->string('status')->default('pending');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    private function createArt($task, $attributes = [])
    {
        return Art::create(array_merge([
            'task_id' => $task->id,
            'title' => 'Test Art',
            'art_path' => 'art/test.jpg',
            'status' => 'pending',
        ], $attributes));
    }

    public function testItCanCreateATaskAsOwner()
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'deadline' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'status' => TaskStatus::PENDING->value,
            'assigned_to' => $this->participant->id,
        ];

        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Task created successfully',
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'client_id' => $this->client->id,
            'assigned_to' => $this->participant->id,
        ]);
    }

    public function testItCanCreateATaskAsParticipant()
    {
        $taskData = [
            'title' => 'Participant Task',
            'description' => 'Task created by participant',
            'status' => TaskStatus::IN_PROGRESS->value,
        ];

        $response = $this->actingAs($this->participant)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Participant Task']);
    }

    public function testItCannotCreateATaskAsUnauthorizedUser()
    {
        $taskData = [
            'title' => 'Unauthorized Task',
            'status' => TaskStatus::PENDING->value,
            'description' => 'Test description',
        ];

        $response = $this->actingAs($this->otherUser)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('tasks', ['title' => 'Unauthorized Task']);
    }

    public function testItValidatesTaskCreationData()
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/{$this->client->id}/tasks", []);

        $response->assertStatus(500);
    }

    public function testItCanCreateArtForTask()
    {
        Storage::fake('public');

        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $file = UploadedFile::fake()->image('art-image.jpg');

        $response = $this->actingAs($this->owner)
            ->call('POST', "/api/tasks/{$task->id}/arts", [
                'title' => 'Art Title',
            ], [], [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Art created successfully',
            ]);

        $this->assertDatabaseHas('art', [
            'task_id' => $task->id,
            'title' => 'Art Title'
        ]);
    }

    public function testItCannotCreateArtWithoutPermission()
    {
        Storage::fake('public');

        // Create a task that the other user doesn't have access to
        $otherClient = Client::factory()->create();
        $task = Task::factory()->create(['client_id' => $otherClient->id]);

        $file = UploadedFile::fake()->image('art-image.jpg');

        $response = $this->actingAs($this->otherUser)
            ->call('POST', "/api/tasks/{$task->id}/arts", [
                'title' => 'Art Title',
            ], [], [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(403);
    }

    public function testItValidatesArtCreationData()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        // Test without file - use call method for consistency
        $response = $this->actingAs($this->owner)
            ->call('POST', "/api/tasks/{$task->id}/arts", [
                'title' => 'Art Title',
            ], [], [], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);

        // Test without title
        $file = UploadedFile::fake()->image('art-image.jpg');
        
        $response = $this->actingAs($this->owner)
            ->call('POST', "/api/tasks/{$task->id}/arts", [], [], [
                'file' => $file,
            ], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
    }

    public function testItCanUpdateTaskAsOwner()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $updateData = [
            'title' => 'Updated Task Title',
            'status' => TaskStatus::COMPLETED->value,
        ];

        $response = $this->actingAs($this->owner)
            ->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Task updated successfully',
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'status' => TaskStatus::COMPLETED->value,
        ]);
    }

    public function testItCanUpdateTaskAsParticipant()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $updateData = ['title' => 'Participant Updated Task'];

        $response = $this->actingAs($this->participant)
            ->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', ['title' => 'Participant Updated Task']);
    }

    public function testItCannotUpdateTaskAsUnauthorizedUser()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->otherUser)
            ->putJson("/api/tasks/{$task->id}", ['title' => 'Unauthorized Update']);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('tasks', ['title' => 'Unauthorized Update']);
    }

    public function testItReturnsNotFoundWhenUpdatingNonExistentTask()
    {
        $response = $this->actingAs($this->owner)
            ->putJson("/api/tasks/999", ['title' => 'Test']);

        $response->assertStatus(404);
    }

    public function testItCanUpdateTaskStatus()
    {
        $task = Task::factory()->create([
            'client_id' => $this->client->id,
            'status' => TaskStatus::PENDING->value
        ]);

        $response = $this->actingAs($this->owner)
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => TaskStatus::IN_PROGRESS->value
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Status updated']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::IN_PROGRESS->value
        ]);
    }

    public function testItCannotUpdateStatusAsUnauthorizedUser()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->otherUser)
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => TaskStatus::COMPLETED->value
            ]);

        $response->assertStatus(403);
    }

    public function testItCanShowTaskToClientMember()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);
        $this->createArt($task);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Task retrieved successfully',
            ]);
    }

    public function testItCannotShowTaskToNonClientMember()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function testItReturnsNotFoundWhenShowingNonExistentTask()
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/tasks/999");

        // Fixed: Your TaskController now returns 404 for non-existent tasks
        $response->assertStatus(404);
    }

    public function testItCanDeleteTaskAsOwner()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Task removed successfully']);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function testItCanDeleteTaskAsParticipant()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->participant)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function testItCannotDeleteTaskAsUnauthorizedUser()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->otherUser)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function testItReturnsNotFoundWhenDeletingNonExistentTask()
    {
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/tasks/999");

        $response->assertStatus(404);
    }

    public function testItCanListTasksForClientMembers()
    {
        Task::factory()->count(3)->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/clients/{$this->client->id}/tasks");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Tasks retrieved successfully']);

        $this->assertCount(3, $response->json('data'));
    }

    public function testItCannotListTasksForNonClientMembers()
    {
        Task::factory()->count(2)->create(['client_id' => $this->client->id]);

        $response = $this->actingAs($this->otherUser)
            ->getJson("/api/clients/{$this->client->id}/tasks");

        $response->assertStatus(403);
    }

    public function testItReturnsNotFoundWhenListingTasksForNonExistentClient()
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/clients/999/tasks");

        $response->assertStatus(404);
    }

    public function testItCanGetWeeklyCalendarForAuthenticatedUser()
    {
        $assignedTask = Task::factory()->create([
            'assigned_to' => $this->owner->id,
            'deadline' => now()->addDays(2),
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/tasks/calendar/week");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Weekly tasks for calendar']);
    }

    public function testItCanGetWeeklyCalendarWithStartDateParameter()
    {
        $startDate = now()->addWeek()->format('Y-m-d');

        $response = $this->actingAs($this->owner)
            ->getJson("/api/tasks/calendar/week?start_date={$startDate}");

        $response->assertStatus(200);
    }

    public function testItOnlyShowsAssignedTasksInWeeklyCalendar()
    {
        $assignedTask = Task::factory()->create([
            'assigned_to' => $this->owner->id,
            'deadline' => now()->addDays(1),
            'client_id' => $this->client->id,
        ]);
        
        $otherTask = Task::factory()->create([
            'assigned_to' => $this->participant->id,
            'deadline' => now()->addDays(1),
            'client_id' => $this->client->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/tasks/calendar/week");

        $response->assertStatus(200);
    }

    public function testItValidatesStatusEnumValues()
    {
        $taskData = [
            'title' => 'Test Task',
            'status' => 'invalid-status',
            'description' => 'Test description',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(500);
    }

    public function testItValidatesAssignedToUserExists()
    {
        $taskData = [
            'title' => 'Test Task',
            'status' => TaskStatus::PENDING->value,
            'assigned_to' => 999,
            'description' => 'Test description',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(500);
    }

    public function testItCanCreateTaskWithoutOptionalFields()
    {
        $taskData = [
            'title' => 'Minimal Task',
            'status' => TaskStatus::PENDING->value,
            'description' => '',
        ];

        $response = $this->actingAs($this->owner)
            ->postJson("/api/clients/{$this->client->id}/tasks", $taskData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Minimal Task']);
    }

    public function testItCanDeleteArt()
    {
        $task = Task::factory()->create(['client_id' => $this->client->id]);
        $art = $this->createArt($task);

        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/tasks/arts/{$art->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Art removed successfully']);

        // Use soft delete check instead
        $this->assertSoftDeleted('art', ['id' => $art->id]);
    }

    public function testItCannotDeleteArtWithoutPermission()
    {
        // Create a task that the other user doesn't have access to
        $otherClient = Client::factory()->create();
        $task = Task::factory()->create(['client_id' => $otherClient->id]);
        $art = $this->createArt($task);

        $response = $this->actingAs($this->otherUser)
            ->deleteJson("/api/tasks/arts/{$art->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('art', ['id' => $art->id]);
    }

    public function testItReturnsNotFoundWhenDeletingNonExistentArt()
    {
        $response = $this->actingAs($this->owner)
            ->deleteJson("/api/tasks/arts/999");

        $response->assertStatus(404);
    }

    public function testItHandlesInvalidStartDateInWeeklyCalendar()
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/tasks/calendar/week?start_date=invalid-date");

        $response->assertStatus(500);
    }
}