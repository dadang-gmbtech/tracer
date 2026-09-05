<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Alumni extends Model
{
    use HasFactory;

    protected $table = 'alumni';

    protected $fillable = [
        'nim', 'nama', 'email', 'faculty_id', 'program_study_id',
        'graduation_year', 'nik', 'npwp', 'phone',
    ];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'program_study_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nim', 'nim');
    }

    public function tracerResponse(): HasOne
    {
        return $this->hasOne(TracerResponse::class);
    }

    public function employerResponses(): HasOne
    {
        return $this->hasOne(EmployerResponse::class)->latestOfMany();
    }

    public function questionAnswers(): MorphMany
    {
        return $this->morphMany(QuestionAnswer::class, 'answerable');
    }

    /**
     * Restrict the query to the alumni visible to the given user's scope
     * (Super Admin / Admin Universitas / Pimpinan Universitas see everything).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin Universitas', 'Pimpinan Universitas'])) {
            return $query;
        }

        if ($user->hasRole('Admin Prodi') && $user->program_study_id) {
            return $query->where('program_study_id', $user->program_study_id);
        }

        if ($user->hasAnyRole(['Admin Fakultas', 'Surveyor', 'Pimpinan Fakultas']) && $user->faculty_id) {
            return $query->where('faculty_id', $user->faculty_id);
        }

        if ($user->hasRole('Alumni')) {
            return $query->where('nim', $user->nim);
        }

        return $query->whereRaw('1 = 0');
    }
}
