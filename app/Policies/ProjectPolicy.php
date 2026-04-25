<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any projects.
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine if the user can view the project.
     */
    public function view(User $user, Project $project)
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $project->users->contains($user->id);
    }

    /**
     * Determine if the user can create projects.
     */
    public function create(User $user)
    {
        return $user->role === 'admin';
    }

    /**
     * Determine if the user can update the project.
     */
    public function update(User $user, Project $project)
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $project->created_by === $user->id;
    }

    /**
     * Determine if the user can delete the project.
     */
    public function delete(User $user, Project $project)
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $project->created_by === $user->id;
    }

    /**
     * Determine if the user can restore the project.
     */
    public function restore(User $user, Project $project)
    {
        return $this->delete($user, $project);
    }

    /**
     * Determine if the user can permanently delete the project.
     */
    public function forceDelete(User $user, Project $project)
    {
        return $this->delete($user, $project);
    }
}
