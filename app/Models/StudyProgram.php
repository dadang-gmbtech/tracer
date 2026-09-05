<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgram extends Model
{
    use HasFactory;

    protected $table = 'program_studies';

    protected $fillable = ['faculty_id', 'code', 'name', 'level'];

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function alumni(): HasMany
    {
        return $this->hasMany(Alumni::class, 'program_study_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
