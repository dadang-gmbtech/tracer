<?php

namespace App\Policies;

use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-questions');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-questions');
    }

    public function update(User $user): bool
    {
        return $user->can('manage-questions');
    }

    public function delete(User $user): bool
    {
        return $user->can('manage-questions');
    }
}
