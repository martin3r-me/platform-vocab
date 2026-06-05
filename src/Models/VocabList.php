<?php

namespace Platform\Vocab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class VocabList extends Model
{
    protected $table = 'vocab_lists';

    protected $fillable = [
        'uuid',
        'team_id',
        'name',
        'description',
        'source_language',
        'target_language',
        'level',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = UuidV7::generate();
            }
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(VocabEntry::class, 'vocab_list_id')->orderBy('sort_order');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }
}
