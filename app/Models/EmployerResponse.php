<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmployerResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'alumni_id', 'nama_pengisi', 'jabatan', 'nama_perusahaan',
        'alamat_perusahaan', 'no_telp',
        'q1_kerja_sama_tim', 'q2_pengembangan_diri', 'q3_komunikasi',
        'q4_teknologi_informasi', 'q5_bahasa_asing', 'q6_keahlian', 'q7_integritas',
    ];

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function questionAnswers(): MorphMany
    {
        return $this->morphMany(QuestionAnswer::class, 'answerable');
    }
}
