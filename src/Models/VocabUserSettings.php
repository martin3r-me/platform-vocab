<?php

namespace Platform\Vocab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VocabUserSettings extends Model
{
    protected $table = 'vocab_user_settings';

    protected $fillable = [
        'user_id',
        'team_id',
        'daily_goal',
        'auto_play_tts',
        'keyboard_shortcuts',
        'listening_first_default',
    ];

    protected $casts = [
        'daily_goal' => 'integer',
        'auto_play_tts' => 'boolean',
        'keyboard_shortcuts' => 'boolean',
        'listening_first_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }

    public static function forUser(int $userId, ?int $teamId = null): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'team_id' => $teamId],
            [
                'daily_goal' => 10,
                'auto_play_tts' => true,
                'keyboard_shortcuts' => true,
                'listening_first_default' => false,
            ]
        );
    }
}
