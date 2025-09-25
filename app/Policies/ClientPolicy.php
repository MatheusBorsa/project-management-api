<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ClientUserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{

    use HandlesAuthorization;

    public function viewCollaborators(User $user, Client $client)
    {
        $users = $client->relationLoaded('users') ? $client->users : $client->users()->get();

        if (!$users->contains('id', $user->id)) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You are not a collaborator of this project.');
        }

        $owner = $users->firstWhere('pivot.role', ClientUserRole::OWNER->value);

        $isPremium = $owner ? $owner->isPremium() : false;

        $maxCollaborators = $isPremium ? 10 : 3;

        return [
            'users' => $users,
            'collaborators_count' => $users->count(),
            'max_collaborators' => $maxCollaborators
        ];
    }
    
    public function inviteCollaborator(User $user, Client $client, string $role): bool
    {
        $users = $client->relationLoaded('users') ? $client->users : $client->users()->get();

        if (!$users->contains('id', $user->id)) {
            return false;
        }

        $owner = $users->firstWhere('pivot.role', ClientUserRole::OWNER->value);
        if (!$owner) return false;

        $isPremium = $owner->isPremium();

        if (!$isPremium && $users->count() >= 3) {
            return false;
        }

        if ($role === ClientUserRole::CLIENT->value && !$isPremium) {
            return false;
        }
        
        return true;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Client $client): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Client $client): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Client $client): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Client $client): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Client $client): bool
    {
        return false;
    }
}
