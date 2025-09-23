<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Utils\ApiResponseUtil;
use App\Enums\ArtStatus;
use App\Models\Art;
use App\Models\Task;
use Exception;

class ArtController extends Controller
{
    public function store(Request $request, Task $task)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'file' => 'required|file|mimes:jpg,jpeg,png,svg,gif|max:10240'
            ]);

        $artPath = null;
        if ($request->hasFile('file')) {
            $artPath = $request->file('file')->store('arts', 'public');
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
                ['error' => $e->getMessage()],
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
}