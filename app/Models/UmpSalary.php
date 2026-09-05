<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmpSalary extends Model
{
    use HasFactory;

    protected $table = 'provincial_minimum_wages';

    protected $fillable = ['province_id', 'year', 'amount'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
