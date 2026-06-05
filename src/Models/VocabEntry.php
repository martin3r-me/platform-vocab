<?php

namespace Platform\Vocab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Symfony\Component\Uid\UuidV7;

class VocabEntry extends Model
{
    protected $table = 'vocab_entries';

    protected $fillable = [
        'uuid',
        'vocab_list_id',
        'term',
        'translation',
        'gender',
        'plural',
        'word_type',
        'example_sentence',
        'notes',
        'pronunciation',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = UuidV7::generate();
            }
        });
    }

    public function vocabList(): BelongsTo
    {
        return $this->belongsTo(VocabList::class, 'vocab_list_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(VocabEntryProgress::class, 'vocab_entry_id');
    }

    public function progressFor(int $userId): HasOne
    {
        return $this->hasOne(VocabEntryProgress::class, 'vocab_entry_id')
            ->where('user_id', $userId);
    }
}
