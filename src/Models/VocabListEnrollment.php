<?php

namespace Platform\Vocab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class VocabListEnrollment extends Model
{
    protected $table = 'vocab_list_enrollments';

    protected $fillable = [
        'uuid',
        'user_id',
        'vocab_list_id',
        'enrolled_at',
        'last_studied_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'last_studied_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = UuidV7::generate();
            }
            if (!$model->enrolled_at) {
                $model->enrolled_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function vocabList(): BelongsTo
    {
        return $this->belongsTo(VocabList::class, 'vocab_list_id');
    }

    public function touchStudied(): void
    {
        $this->forceFill(['last_studied_at' => now()])->save();
    }
}
