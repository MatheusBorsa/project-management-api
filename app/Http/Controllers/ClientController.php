<?php

namespace App\Http\Controllers;


use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Utils\ApiResponseUtil;
use App\Enums\ClientUserRole;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClientController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'contact_name' => 'nullable|string|max:255',
                'email' => 'required|email|max:255|unique:clients',
                'phone' => 'required|string|max:30',
                'notes' => 'required|string'
            ]);

            $client = Client::create($validatedData);

            $request->user()->clients()->attach($client->id, [
                'role' => ClientUserRole::OWNER->value,
            ]);

            return ApiResponseUtil::success(
                'Client created successfully',
                [
                    'client' => $client,
                    'Owner' => $request->user()
                ],
                201
            );

        } catch (ValidationException $e) {
            return ApiResponseUtil::error(
                'Validation Error',
                $e->errors(),
                422
            );
        
        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Server Error',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function show($id)
    {
        try {
            $client = Client::with(['users' => function ($query) {
                $query->select('users.id', 'users.name', 'users.email')
                    ->withPivot('role');
            }])->findOrFail($id);

            return ApiResponseUtil::success(
                'Client retrieved successfully',
                $client,
                200
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Error retrieving client',
                ['error' => $e->getMessage()],
                500
            );

        }
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $clients = $user->clients()->with(['users' => function ($query) {
                $query->select('users.id', 'users.name', 'users.email')
                    ->withPivot('role');
            }])->get();

            return ApiResponseUtil::success(
                'Clients retrieved successfully',
                $clients,
                200
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Error retrieving clients',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            $client = $user->clients()->findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'contact_name' => 'sometimes|nullable|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:clients',
                'phone' => 'sometimes|required|string|max:30',
                'notes' => 'sometimes|required|string'
            ]);

            $client->update($validatedData);

            return ApiResponseUtil::success(
                'Client updated successfully',
                $client,
                200
            );

        } catch (ModelNotFoundException $e) {
            return ApiResponseUtil::error(
                'Client not found',
                ['error' => $e->getMessage()],
                404
            );
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            $client = $user->clients()->findOrFail($id);

            $client->delete();

            return ApiResponseUtil::success(
                'Client removed successfully',
                null,
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
                'Error removing client',
                ['error' => $e->getMessage()],
                500
            );   
        }
    }

    public function users(Request $request, $id)
    {
        try {
            $client = Client::with('users')->findOrFail($id);
            $isMember = $client->users()->where('users.id', $request->user()->id)->exists();
            if (!$isMember) {
                return ApiResponseUtil::error(
                    'Unauthorized',
                    null,
                    403
                );
            }

            return ApiResponseUtil::success(
                'Retrieved collaborators successfully',
                ['users' => $client->users]
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Error retrieving client collaborators',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function updateUser(Request $request, $clientId, $userId)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|in:' . implode(',', array_column(ClientUserRole::cases(), 'value'))
            ]);

            $currentUser = $request->user();

            $client = Client::findOrFail($clientId);

            $client = Client::with('users')->findOrFail($clientId);

            $pivot = $client->users->firstWhere('id', $currentUser->id)?->pivot;

            if (!$pivot || (string) $pivot->role !== (string) ClientUserRole::OWNER->value) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $targetUser = $client->users()->where('users.id', $userId)->first();

            if (!$targetUser) {
                return ApiResponseUtil::error(
                    'User not found',
                    null,
                    404
                );
            }

            $client->users()->updateExistingPivot($userId, [
                'role' => $validated['role'],
                'updated_at' => now()
            ]);

            return ApiResponseUtil::success(
                'User role updated successfully',
                [
                'client' => $client->name,
                'user' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'role' => $validated['role']
                    ]
                ],
                200
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to update user role',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function removeUser(Request $request, $clientId, $userId)
    {
        try {
            $currentUser = $request->user();

            $client = Client::with('users')->findOrFail($clientId);

            $currentUserPivot = $client->users->firstWhere('id', $currentUser->id)?->pivot;

            if(!$currentUserPivot || (string) $currentUserPivot->role !== (string) ClientUserRole::OWNER->value) {
                return ApiResponseUtil::error(
                    'You are not authorized',
                    null,
                    403
                );
            }

            $targetUserPivot = $client->users->firstWhere('id', $userId)?->pivot;

            if (!$targetUserPivot) {
                return ApiResponseUtil::error(
                    'User not found',
                    null,
                    404
                );
            }

            $client->users()->detach($userId);

            return ApiResponseUtil::success(
                'User removed from client successfully',
                [
                    'client_id' => $clientId,
                    'removed_user_id' => $userId
                ],
                200
            );

        } catch (Exception $e) {
            return ApiResponseUtil::error(
                'Failed to remove user from client',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}
