<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'nim', 'tanggal_lahir', 'no_telp', 'status', 'faculty_id', 'program_study_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tanggal_lahir' => 'date',
            'password' => 'hashed',
        ];
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class, 'program_study_id');
    }

    public function tracerResponsesSubmitted(): HasMany
    {
        return $this->hasMany(TracerResponse::class, 'submitted_by_user_id');
    }

    public function alumni(): HasOne
    {
        return $this->hasOne(Alumni::class, 'nim', 'nim');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Where to send this user right after they log in: alumni go straight
     * into their own tracer questionnaire, everyone else to the dashboard.
     */
    public function postLoginUrl(): string
    {
        if ($this->hasRole('Alumni') && $this->alumni) {
            return route('tracer.edit', $this->alumni);
        }

        return route('dashboard');
    }
}
