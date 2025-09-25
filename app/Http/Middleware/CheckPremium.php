<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Utils\ApiResponseUtil;
use App\Models\Task;
use App\Models\Art;
use App\Enums\ClientUserRole;

class CheckPremium
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $task = null;


        if ($request->route('task')) {
            $task = $request->route('task');
        } 

        elseif ($request->route('artId')) {
            $art = Art::with('task.client.users')->find($request->route('artId'));
            if (!$art) {
                return ApiResponseUtil::error(
                    'Art not found',
                    null,
                    404
                );
            }
            $task = $art->task;
        }

        if (!$task) {
            return ApiResponseUtil::error(
                'Task not found',
                null,
                404
            );
        }

        $task->loadMissing('client.users');

        $client = $task->client;

        if (!$client) {
            return ApiResponseUtil::error(
                'Client not found',
                null,
                404
            );
        }

        $owner = $client->users
            ->first(fn($u) => $u->pivot->role === ClientUserRole::OWNER->value);

        if (!$owner) {
            return ApiResponseUtil::error(
                'Owner not found',
                null,
                404
            );
        }

        if ($user->isPremium() || $owner->isPremium()) {
            return $next($request);
        }

        return ApiResponseUtil::error(
            'Premium feature',
            null,
            403
        );
    }
}
