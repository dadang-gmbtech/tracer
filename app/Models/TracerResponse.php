<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TracerResponse extends Model
{
    use HasFactory;

    // Status Anda saat ini (F8)
    public const STATUS_BEKERJA = 1;

    public const STATUS_BELUM_MEMUNGKINKAN = 2;

    public const STATUS_WIRASWASTA = 3;

    public const STATUS_MELANJUTKAN_STUDI = 4;

    public const STATUS_MENCARI_KERJA = 5;

    protected $fillable = [
        'alumni_id', 'work_province_id', 'work_city_id',
        'submitted_by_user_id', 'submitted_at',
        'f8', 'f504', 'f502', 'f505', 'f506', 'f5a1', 'f5a2',
        'f1101', 'f1102', 'f5b', 'f5c', 'f5d',
        'f18a', 'f18b', 'f18c', 'f18d',
        'f1201', 'f1202', 'f14', 'f15',
        'f301', 'f302', 'f303', 'f416',
        'f6', 'f7', 'f7a', 'f1001', 'f1002', 'f1614',
    ];

    public function __construct(array $attributes = [])
    {
        foreach (array_merge(
            range(1761, 1774),
            range(21, 27),
            range(401, 415),
            range(1601, 1613),
        ) as $code) {
            $this->fillable[] = "f{$code}";
        }

        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        $casts = [
            'f505' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];

        foreach (array_merge(range(401, 415), range(1601, 1613)) as $code) {
            $casts["f{$code}"] = 'boolean';
        }

        return $casts;
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function workProvince(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'work_province_id');
    }

    public function workCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'work_city_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function isBekerja(): bool
    {
        return (int) $this->f8 === self::STATUS_BEKERJA;
    }

    public function isWiraswasta(): bool
    {
        return (int) $this->f8 === self::STATUS_WIRASWASTA;
    }

    public function isMelanjutkanStudi(): bool
    {
        return (int) $this->f8 === self::STATUS_MELANJUTKAN_STUDI;
    }
}
