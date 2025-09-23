<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Utils\ApiResponseUtil;
use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\Carbon;
use App\Http\Resources\TaskResource;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;


class TaskController extends Controller
{   
    private function checkTaskPermission($taskOrClient, $user, array $allowedRoles = ['owner', 'participant'])
    {
        if ($taskOrClient instanceof Client) {
            $client = $taskOrClient;
        } else {
            $client = $taskOrClient->client;
        }
        
        $pivot = $client->users->firstWhere('id', $user->id)?->pivot;

        if (!$pivot || !in_array($pivot->role, $allowedRoles)) {
            return false;
        }
        return true;
    }

    public function store(Request $request, $clientId)
    {
        try {
            $currentUser = $request->user();
            $client = Client::with('users')->findOrFail($clientId);

            if (!$this->checkTaskPermission($client, $currentUser)) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'deadline' => 'nullable|date',
                'status' => 'required|string|in:' . implode(',', array_column(TaskStatus::cases(), 'value')),
                'assigned_to' => 'nullable|integer|exists:users,id',
                'image_path' => 'nullable|image|max:2048'
            ]);

            if($request->hasFile('image_path')) {
                $validated['image_path'] = $request->file('image_path')->store('tasks', 'public');
            }

            $task = $client->tasks()->create($validated);

            return ApiResponseUtil::success(
                'Task created successfully',
                $task,
                201
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to create task',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $currentUser = $request->user();

            $task = Task::with('client.users')->findOrFail($id);

            if (!$this->checkTaskPermission($task, $currentUser)) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'deadline' => 'sometimes|date',
                'status' => 'sometimes|in:' . implode(',', array_column(TaskStatus::cases(), 'value')),
                'assigned_to' => 'sometimes|nullable|exists:users,id',
                'image_path' => 'nullable|image|max:2048'
            ]);

            if ($request->hasFile('image_path')) {
                $validated['image_path'] = $request->file('image_path')->store('tasks', 'public');
            }

            $task->update($validated);

            return ApiResponseUtil::success(
                'Task updated successfully',
                new TaskResource($task),
                200
            );

        } catch (ValidationException $e) {
            return ApiResponseUtil::error(
                'Validation Failed',
                ['error' => $e->getMessage()],
                422
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Task not found',
                ['error' => $e->getMessage()],
                404
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to update task',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:' . implode(',', array_column(TaskStatus::cases(), 'value'))
            ]);

            $task = Task::findOrFail($id);

            if (!$this->checkTaskPermission($task, $request->user())) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $task->update(['status' => $request->status]);

            return ApiResponseUtil::success(
                'Status updated',
                new TaskResource($task)
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to update task status',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            $task = Task::with('client.users', 'assignedUser', 'arts')->findOrFail($id);

            $pivot = $task->client->users->firstWhere('id', $user->id)?->pivot;
            if (!$pivot) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            return ApiResponseUtil::success(
                'Task retrieved successfully',
                new TaskResource($task),
                200
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to retrieve task',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $currentUser = $request->user();

            $task = Task::with('client.users')->findOrFail($id);

            if (!$this->checkTaskPermission($task, $currentUser)) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $task->delete();

            return ApiResponseUtil::success(
                'Task removed successfully',
                null,
                200
            );
            
        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Task not found',
                ['error' => $e->getMessage()],
                404
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to remove task',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function index(Request $request, $clientId)
    {
        try {
            $currentUser = $request->user();

            $client = Client::with('users')->findOrFail($clientId);

            $pivot = $client->users->firstWhere('id', $currentUser->id);
            if (!$pivot || !$pivot->pivot) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403 
                );
            }

            $tasks = $client->tasks()->with('assignedUser')->get();

            return ApiResponseUtil::success(
                'Tasks retrieved successfully',
                TaskResource::collection($tasks),
                200
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Client not found',
                ['error' => $e->getMessage()],
                404
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to retrieve tasks',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function weeklyCalendar(Request $request)
    {
        try {
            $user = $request->user();
            
            $startDate = $request->get('start_date') 
                ? Carbon::parse($request->get('start_date'))->startOfWeek()
                : Carbon::now()->startOfWeek();
                
            $endDate = $startDate->copy()->endOfWeek();
            
            $tasks = Task::where('assigned_to', $user->id)
                ->where('deadline', '>=', $startDate)
                ->where('deadline', '<=', $endDate)
                ->with(['client:id,name'])
                ->orderBy('deadline')
                ->get();
            
            $tasksByDate = [];
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $dateKey = $date->format('Y-m-d');
                $dayTasks = $tasks->filter(function ($task) use ($dateKey) {
                    return $task->deadline && Carbon::parse($task->deadline)->format('Y-m-d') === $dateKey;
                });
                
                $tasksByDate[] = [
                    'date' => $dateKey,
                    'day_name' => $date->format('l'),
                    'day_short' => $date->format('D'),
                    'day_number' => $date->format('j'),
                    'is_today' => $date->isToday(),
                    'tasks' => $dayTasks->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'status' => $task->status,
                            'client_name' => $task->client->name,
                            'deadline' => $task->deadline,
                            'time' => Carbon::parse($task->deadline)->format('H:i')
                        ];
                    })->values()
                ];
            }
            
            return ApiResponseUtil::success(
                'Weekly tasks for calendar',
                [
                'week_start' => $startDate->format('Y-m-d'),
                'week_end' => $endDate->format('Y-m-d'),
                'current_week' => $startDate->isSameWeek(Carbon::now()),
                'days' => $tasksByDate
                ]
            );
            
        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to retrieve weekly tasks',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
