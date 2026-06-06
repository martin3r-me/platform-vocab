<?php

namespace Platform\Vocab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VocabAchievement extends Model
{
    protected $table = 'vocab_achievements';

    protected $fillable = [
        'user_id',
        'code',
        'awarded_at',
    ];

    protected $casts = [
        'awarded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
