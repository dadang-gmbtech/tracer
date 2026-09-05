<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name'];

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class);
    }

    public function alumni(): HasMany
    {
        return $this->hasMany(Alumni::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
