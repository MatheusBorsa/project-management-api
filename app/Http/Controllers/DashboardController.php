<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Utils\ApiResponseUtil;
use App\Models\Task;
use App\Models\Art;
use App\Enums\ArtStatus;
use Exception;
use App\Enums\TaskStatus;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $myClients = $user->clients()
            ->select('clients.id', 'clients.name')
            ->withCount([
                'tasks',
                'tasks as pending_tasks_count' => fn($q) => $q->where('status', TaskStatus::PENDING->value),
                'tasks as in_progress_tasks_count' => fn($q) => $q->where('status', TaskStatus::IN_PROGRESS->value),
                'tasks as completed_tasks_count' => fn($q) => $q->where('status', TaskStatus::COMPLETED->value)
            ])->get();

            $taskSummary = [
                'pending' => Task::where('assigned_to', $user->id)->where('status', TaskStatus::PENDING->value)->count(),
                'in_progress' => Task::where('assigned_to', $user->id)->where('status', TaskStatus::IN_PROGRESS->value)->count(),
                'completed' => Task::where('assigned_to', $user->id)->where('status', TaskStatus::COMPLETED->value)->count(),
                'overdue' => Task::where('assigned_to', $user->id)
                    ->where('deadline', '<', now())
                    ->where('status', '!=', 'completed')
                    ->count()
            ];

            $artsSummary = [
                'pending_review' => Art::whereHas('task', fn($q) => $q->where('assigned_to', $user->id))
                    ->where('status', ArtStatus::PENDING->value)
                    ->count(),
                'approved' => Art::whereHas('task', fn($q) => $q->where('assigned_to', $user->id))
                    ->where('status', ArtStatus::APPROVED->value)
                    ->count(),
                'rejected' => Art::whereHas('task', fn($q) => $q->where('assigned_to', $user->id))
                    ->where('status', ArtStatus::REJECTED->value)
                    ->count(),
                'revision_requested' => Art::whereHas('task', fn($q) => $q->where('assigned_to', $user->id))
                    ->where('status', ArtStatus::REVISION_REQUESTED->value)
                    ->count()
            ];

            $totalTasks = max(1, Task::where('assigned_to', $user->id)->count());
            $completedTasks = Task::where('assigned_to', $user->id)
                ->where('status', TaskStatus::COMPLETED->value)
                ->count();
            $completedThisWeek = Task::where('assigned_to', $user->id)
                ->where('status', TaskStatus::COMPLETED->value)
                ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count();

            $taskStats = [
                'completion_rate' => round(($completedTasks / $totalTasks) * 100, 2),
                'completed_this_week' => $completedThisWeek
            ];

            $upcomingTasks = Task::where('assigned_to', $user->id)
                ->where('status', '!=', 'completed')
                ->orderBy('deadline')
                ->limit(10)
                ->with(['client' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->get(['id', 'client_id', 'title', 'deadline']);

            return ApiResponseUtil::success(
                'Dashboard data',
                [
                    'my_clients' => $myClients,
                    'task_summary' => $taskSummary,
                    'arts_summary' => $artsSummary,
                    'task_stats' => $taskStats,
                    'upcoming_tasks' => $upcomingTasks
                ]
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to retrieve dashboard data',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
