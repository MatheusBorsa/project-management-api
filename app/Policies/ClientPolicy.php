<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{

    use HandlesAuthorization;

    public function inviteCollaborator(User $user, Client $client): bool
    {
        $users = $client->relationLoaded('users') ? $client->users : $client->users()->get();

        if (!$users->contains('id', $user->id)) {
            return false;
        }

        $owner = $users->firstWhere('pivot.role', 'owner');
        if (!$owner) return false;

        if ($owner->role === UserRole::FREE && $users->count() >= 3) {
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
