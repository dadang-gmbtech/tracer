<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const LEVEL_UNIVERSITAS = 'universitas';

    public const LEVEL_FAKULTAS = 'fakultas';

    public const LEVEL_PRODI = 'prodi';

    protected $fillable = [
        'level', 'faculty_id', 'program_study_id', 'title', 'file_path', 'year', 'uploaded_by',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'program_study_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin Universitas', 'Pimpinan Universitas'])) {
            return $query;
        }

        if ($user->hasRole('Admin Prodi') && $user->program_study_id) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('program_study_id', $user->program_study_id)
                    ->orWhere(function (Builder $q2) use ($user) {
                        $q2->where('level', self::LEVEL_FAKULTAS)->where('faculty_id', $user->faculty_id);
                    })
                    ->orWhere('level', self::LEVEL_UNIVERSITAS);
            });
        }

        if ($user->hasAnyRole(['Admin Fakultas', 'Pimpinan Fakultas']) && $user->faculty_id) {
            return $query->where(function (Builder $q) use ($user) {
                $q->where('faculty_id', $user->faculty_id)->orWhere('level', self::LEVEL_UNIVERSITAS);
            });
        }

        return $query->where('level', self::LEVEL_UNIVERSITAS);
    }
}
