<?php

namespace Platform\Vocab\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Symfony\Component\Uid\UuidV7;

class VocabCatalog extends Model
{
    protected $table = 'vocab_catalogs';

    public const VISIBILITY_TEAM = 'team';
    public const VISIBILITY_PERSONAL = 'personal';

    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_user_id',
        'name',
        'description',
        'visibility',
        'cover_color',
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(
            VocabList::class,
            'vocab_catalog_list',
            'vocab_catalog_id',
            'vocab_list_id'
        )
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('vocab_catalog_list.sort_order');
    }

    public function isPersonal(): bool
    {
        return $this->visibility === self::VISIBILITY_PERSONAL;
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->created_by_user_id === $userId;
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
}
