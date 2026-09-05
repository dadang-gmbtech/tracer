<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QuestionAnswer extends Model
{
    protected $fillable = ['question_id', 'answerable_type', 'answerable_id', 'value'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answerable(): MorphTo
    {
        return $this->morphTo();
    }
}
