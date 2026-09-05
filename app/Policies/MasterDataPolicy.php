<?php

namespace App\Policies;

use App\Models\User;

/**
 * Shared policy for Faculty, StudyProgram, Province, City and UmpSalary — all
 * master data is managed only by Super Admin (via Gate::before) or Admin
 * Universitas. Registered for each model in AppServiceProvider.
 */
class MasterDataPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage-master-data');
    }

    public function create(User $user): bool
    {
        return $user->can('manage-master-data');
    }

    public function update(User $user): bool
    {
        return $user->can('manage-master-data');
    }

    public function delete(User $user): bool
    {
        return $user->can('manage-master-data');
    }
}
