<?php

namespace App\Policies;

use App\Models\Alumni;
use App\Models\User;

class AlumniPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'Admin Universitas', 'Admin Fakultas', 'Admin Prodi', 'Surveyor',
            'Pimpinan Universitas', 'Pimpinan Fakultas',
        ]);
    }

    public function view(User $user, Alumni $alumni): bool
    {
        return $this->inScope($user, $alumni);
    }

    public function create(User $user): bool
    {
        return $this->canManageBioData($user);
    }

    public function update(User $user, Alumni $alumni): bool
    {
        return $this->canManageBioData($user) && $this->inScope($user, $alumni);
    }

    public function fillTracer(User $user, Alumni $alumni): bool
    {
        if (! $user->can('fill-tracer')) {
            return false;
        }

        if ($user->hasRole('Alumni')) {
            return $alumni->nim === $user->nim;
        }

        return $this->inScope($user, $alumni) && ! $user->hasAnyRole(['Pimpinan Universitas', 'Pimpinan Fakultas']);
    }

    /**
     * Only the roles that actually operate the tracer form on an alumnus's
     * behalf may add/edit alumni bio data — not the alumnus's own account,
     * and not the read-only Pimpinan roles.
     */
    private function canManageBioData(User $user): bool
    {
        return $user->can('fill-tracer') && ! $user->hasAnyRole(['Alumni', 'Pimpinan Universitas', 'Pimpinan Fakultas']);
    }

    private function inScope(User $user, Alumni $alumni): bool
    {
        return match (true) {
            $user->hasAnyRole(['Admin Universitas', 'Pimpinan Universitas']) => true,
            $user->hasRole('Admin Prodi') => $alumni->program_study_id === $user->program_study_id,
            $user->hasAnyRole(['Admin Fakultas', 'Surveyor', 'Pimpinan Fakultas']) => $alumni->faculty_id === $user->faculty_id,
            $user->hasRole('Alumni') => $alumni->nim === $user->nim,
            default => false,
        };
    }
}
