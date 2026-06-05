<?php

namespace Platform\Vocab\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class VocabList extends Model
{
    protected $table = 'vocab_lists';

    public const VISIBILITY_TEAM = 'team';
    public const VISIBILITY_PERSONAL = 'personal';

    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_user_id',
        'name',
        'description',
        'source_language',
        'target_language',
        'level',
        'tags',
        'visibility',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    public function catalogs(): BelongsToMany
    {
        return $this->belongsToMany(
            VocabCatalog::class,
            'vocab_catalog_list',
            'vocab_list_id',
            'vocab_catalog_id'
        )
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(VocabListEnrollment::class, 'vocab_list_id');
    }

    public function isPersonal(): bool
    {
        return $this->visibility === self::VISIBILITY_PERSONAL;
    }

    public function scopeVisibleTo(Builder $query, int $userId, int $teamId): Builder
    {
        return $query->where('team_id', $teamId)
            ->where(function (Builder $q) use ($userId) {
                $q->where('visibility', self::VISIBILITY_TEAM)
                    ->orWhere(function (Builder $personal) use ($userId) {
                        $personal->where('visibility', self::VISIBILITY_PERSONAL)
                            ->where('created_by_user_id', $userId);
                    });
            });
    }

    public function masteryFor(int $userId): array
    {
        $entryIds = $this->entries()->pluck('id');
        $total = $entryIds->count();

        if ($total === 0) {
            return ['total' => 0, 'mastered' => 0, 'reviewed' => 0, 'pct' => 0];
        }

        $progress = VocabEntryProgress::query()
            ->where('user_id', $userId)
            ->whereIn('vocab_entry_id', $entryIds)
            ->get(['status']);

        $mastered = $progress->whereIn('status', [
            VocabEntryProgress::STATUS_REVIEW,
            VocabEntryProgress::STATUS_MASTERED,
        ])->count();

        return [
            'total' => $total,
            'mastered' => $mastered,
            'reviewed' => $progress->count(),
            'pct' => (int) round($mastered / $total * 100),
        ];
    }

    public function enrollmentFor(int $userId): ?VocabListEnrollment
    {
        return $this->enrollments()->where('user_id', $userId)->first();
    }
}
