<?php

namespace App\Policies;

use App\Models\Directory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DirectoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Directory  $directory
     * @return bool
     */
    public function view(User $user, Directory $directory)
    {
        return $user->id === $directory->user_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Directory  $directory
     * @return bool
     */
    public function update(User $user, Directory $directory)
    {
        return $user->id === $directory->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Directory  $directory
     * @return bool
     */
    public function delete(User $user, Directory $directory)
    {
        return $user->id === $directory->user_id;
    }
}