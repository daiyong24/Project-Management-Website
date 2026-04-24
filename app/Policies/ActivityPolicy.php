<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Activity $activity)
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'author') {
            return $activity->project && $activity->project->created_by === $user->id;
        }

        return $activity->user_id === $user->id
            || $activity->assigned_to_user_id === $user->id;
    }

    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'author'], true);
    }

    public function update(User $user, Activity $activity)
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $activity->user_id === $user->id;
    }

    public function delete(User $user, Activity $activity)
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $activity->user_id === $user->id;
    }

    public function comment(User $user, Activity $activity)
    {
        return $this->view($user, $activity);
    }

    public function removeComment(User $user, Activity $activity)
    {
        return $user->role === 'admin';
    }

    public function addComment(User $user, Activity $task)
    {
        return $this->view($user, $task);
    }

    public function updateStatus(User $user, Activity $task)
    {
        return $this->view($user, $task);
    }
}
