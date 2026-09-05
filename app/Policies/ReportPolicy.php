<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        return match (true) {
            $user->hasAnyRole(['Admin Universitas', 'Pimpinan Universitas']) => true,
            $report->level === Report::LEVEL_UNIVERSITAS => true,
            $user->hasRole('Admin Prodi') => $report->program_study_id === $user->program_study_id
                || ($report->level === Report::LEVEL_FAKULTAS && $report->faculty_id === $user->faculty_id),
            $user->hasAnyRole(['Admin Fakultas', 'Pimpinan Fakultas']) => $report->faculty_id === $user->faculty_id,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->can('upload-report');
    }

    public function delete(User $user, Report $report): bool
    {
        return $user->can('upload-report') && $this->view($user, $report) && $report->uploaded_by === $user->id;
    }
}
