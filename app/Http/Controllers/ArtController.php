<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Utils\ApiResponseUtil;
use App\Enums\ArtStatus;
use App\Enums\ClientUserRole;
use App\Models\Art;
use App\Models\ArtFeedback;
use App\Models\Task;
use App\Models\Client;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;

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
                'status' => ArtStatus::PENDING->value,
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

    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $art = Art::with('task.client.users')->findOrFail($id);

            if (!$this->checkTaskPermission($art->task, $user)) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            if ($art->status === ArtStatus::APPROVED->value) {
                return ApiResponseUtil::error(
                    'Approved arts cannot be updated',
                    null,
                    403
                );
            }

            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'file' => 'sometimes|required|file|mimes:jpg,jpeg,png,svg,gif|max:10240'
            ]);

            if ($request->hasFile('file')) {
                if ($art->art_path && \Storage::disk('public')->exists($art->art_path)) {
                    \Storage::disk('public')->delete($art->art_path);
                }

                $newPath = $request->file('file')->store('art', 'public');
                $art->art_path = $newPath;
            }

            if (isset($validatedData['title'])) {
                $art->title = $validatedData['title'];  
            }

            $art->save();
            $art->refresh();

            return ApiResponseUtil::success(
                'Art update successfully',
                ['art' => $art],
                200
            );

        } catch (ValidationException $e) {
            return ApiResponseUtil::error(
                'Validation error',
                ['errors' => $e->errors()],
                422
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Art not found',
                null,
                404
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to update art',
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

    public function review(Request $request, $artId)
    {
        try {
            $validatedData = $request->validate([
                'status' => ['required', Rule::in(array_column(ArtStatus::cases(), 'value'))],
                'feedback' => 'nullable|string|max:1000'
            ]);

            $art = Art::with('task')->findOrFail($artId);
            $user = $request->user();
            
            if (!$this->checkTaskPermission($art->task, $user, [ClientUserRole::CLIENT->value])) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $art->status = ArtStatus::from($validatedData['status'])->value;
            $art->save();

            if(!empty($validatedData['feedback'])) {
                ArtFeedback::create([
                    'art_id' => $art->id,
                    'user_id' => $user->id,
                    'feedback' => $validatedData['feedback']
                ]);
            }

            if ($art->status === ArtStatus::APPROVED->value) {
                $art_path = basename($art->art_path);
                $newPath = "clients/{$user->id}/approved_arts/{$art_path}";
                \Storage::disk('public')->move($art->art_path, $newPath);
                $art->art_path = $newPath;
                $art->save();
            }

            return ApiResponseUtil::success(
                'Art reviewed successfully',
                [
                    'id' => $art->id,
                    'title' => $art->title,
                    'status' => $art->status,
                    'art_path' => $art->art_path,
                    'feedback' => $validatedData['feedback']
                ]
            );

        } catch (ValidationException $e) {
            return ApiResponseUtil::error(
                'Validation error',
                $e->errors(),
                422
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Art not found',
                null,
                404
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Server error',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function addComment(Request $request, $artId)
    {
        try {
            $validatedData = $request->validate([
                'x' => 'required|integer|min:0',
                'y' =>'required|integer|min:0',
                'comment' => 'required|string|max:1000'
            ]);

            $art = Art::findOrFail($artId);
            $user = $request->user();

            if (!$this->checkTaskPermission($art->task, $user, [ClientUserRole::CLIENT->value])) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $comment = $art->comments()->create([
                'user_id' => $user->id,
                'x' => $validatedData['x'],
                'y' => $validatedData['y'],
                'comment' => $validatedData['comment']
            ]);

            $art->status = ArtStatus::REVISION_REQUESTED->value;
            $art->save();

            return ApiResponseUtil::success(

                'Comment added successfully',
                ['comment' => $comment],
                201
            );

        } catch (ValidationException $e) {
            return ApiResponseUtil::error(
                'Validation error',
                ['errors' => $e->errors()],
                422
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Failed to add comment',
                ['error' => $e->getMessage()],
                500
            );  
        }
    }

    public function getComments($artId)
    {
        try {
            $art = Art::findOrFail($artId);

            $comments = $art->comments()->with('user')->get();

            return ApiResponseUtil::success(
                'Comments retrieved successfully',
                ['comments' => $comments]
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Art not found',
                ['error' => $e->getMessage()],
                404
            );
        }
    }
}