<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Utils\ApiResponseUtil;
use App\Enums\ArtStatus;
use App\Models\Art;
use App\Models\Task;
use App\Models\Client;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ArtController extends Controller
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

    public function store(Request $request, Task $task)
    {
        try {
            $task->load('client.users');
            
            if (!$this->checkTaskPermission($task, $request->user())) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'file' => 'required|file|mimes:jpg,jpeg,png,svg,gif|max:10240'
            ]);

            $artPath = null;
            if ($request->hasFile('file')) {
                $artPath = $request->file('file')->store('art', 'public');
            }

            $art = Art::create([
                'task_id' => $task->id,
                'title' => $validatedData['title'],
                'art_path' => $artPath,
                'status' => ArtStatus::PENDING,
            ]);

            return ApiResponseUtil::success(
                'Art created successfully',
                ['art' => $art],
                201
            );

        } catch (ValidationException $e) {
            return ApiResponseUtil::error(
                'Validation error',
                ['errors' => $e->errors()],
                422
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Server error',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $art = Art::with('task.client.users')->findOrFail($id);

            if(!$this->checkTaskPermission($art->task, $user)) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $art->delete();

            return ApiResponseUtil::success(
                'Art removed successfully',
                null,
                200
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Art not found',
                ['error' => $e->getMessage()],
                404
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to remove art',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}