<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-users');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-users');
    }

    public function update(User $user, User $target): bool
    {
        return $this->canManage($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->canManage($user, $target) && $user->isNot($target);
    }

    private function canManage(User $user, User $target): bool
    {
        if (! $user->can('manage-users')) {
            return false;
        }

        if ($user->hasRole('Admin Universitas')) {
            return true;
        }

        if ($user->hasRole('Admin Fakultas')) {
            return $target->faculty_id === $user->faculty_id
                && $target->hasAnyRole(['Admin Prodi', 'Surveyor']);
        }

        if ($user->hasRole('Admin Prodi')) {
            return $target->program_study_id === $user->program_study_id
                && $target->hasRole('Surveyor');
        }

        return false;
    }
}
