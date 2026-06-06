<?php

namespace Platform\Vocab\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Vocab\Services\SrsAlgorithm;
use Platform\Vocab\Services\VocabAchievementService;
use Symfony\Component\Uid\UuidV7;

class VocabEntryProgress extends Model
{
    protected $table = 'vocab_entry_progress';

    public const STATUS_NEW = 'new';
    public const STATUS_LEARNING = 'learning';
    public const STATUS_REVIEW = 'review';
    public const STATUS_MASTERED = 'mastered';

    protected $fillable = [
        'uuid',
        'user_id',
        'vocab_entry_id',
        'ease_factor',
        'interval_days',
        'repetitions',
        'due_at',
        'last_reviewed_at',
        'last_quality',
        'status',
        'total_reviews',
        'lapses',
    ];

    protected $casts = [
        'ease_factor' => 'decimal:2',
        'interval_days' => 'integer',
        'repetitions' => 'integer',
        'due_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'last_quality' => 'integer',
        'total_reviews' => 'integer',
        'lapses' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = UuidV7::generate();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function vocabEntry(): BelongsTo
    {
        return $this->belongsTo(VocabEntry::class, 'vocab_entry_id');
    }

    public function scopeDue(Builder $query, ?\DateTimeInterface $at = null): Builder
    {
        return $query->whereNotNull('due_at')->where('due_at', '<=', $at ?? now());
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function isDue(): bool
    {
        return $this->due_at !== null && $this->due_at->isPast();
    }

    public static function recordReview(int $userId, int $entryId, int $quality): self
    {
        $progress = self::firstOrNew([
            'user_id' => $userId,
            'vocab_entry_id' => $entryId,
        ]);

        if (!$progress->exists) {
            $progress->status = self::STATUS_NEW;
            $progress->ease_factor = SrsAlgorithm::DEFAULT_EASE_FACTOR;
            $progress->repetitions = 0;
            $progress->interval_days = 0;
            $progress->total_reviews = 0;
            $progress->lapses = 0;
            $progress->save();
        }

        SrsAlgorithm::apply($progress, $quality);

        try {
            $newlyAwarded = app(VocabAchievementService::class)->evaluate($userId);
        } catch (\Throwable $e) {
            $newlyAwarded = [];
        }

        $progress->setAttribute('newly_awarded', $newlyAwarded);

        return $progress;
    }

    public static function recordAnswer(int $userId, int $entryId, bool $correct): self
    {
        return self::recordReview(
            $userId,
            $entryId,
            $correct ? SrsAlgorithm::QUALITY_GOOD : SrsAlgorithm::QUALITY_AGAIN
        );
    }
}
