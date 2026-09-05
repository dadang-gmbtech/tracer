<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function minimumWages(): HasMany
    {
        return $this->hasMany(UmpSalary::class);
    }

    public function wageForYear(int $year): ?UmpSalary
    {
        return $this->minimumWages()->where('year', $year)->first();
    }
}
