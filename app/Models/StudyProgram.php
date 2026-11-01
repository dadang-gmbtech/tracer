<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyProgram extends Model
{
    use HasFactory;

    /**
     * Jenjang values offered as filters (dashboard, export, laporan, daftar
     * alumni), spelled exactly as the national data stores them in `level`.
     */
    public const LEVELS = ['D3', 'S1', 'S2', 'S3', 'Profesi', 'Sp-1'];

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
