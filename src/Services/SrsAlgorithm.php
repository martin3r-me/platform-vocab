<?php

namespace Platform\Vocab\Services;

use Platform\Vocab\Models\VocabEntryProgress;

class SrsAlgorithm
{
    public const QUALITY_AGAIN = 1;
    public const QUALITY_HARD = 3;
    public const QUALITY_GOOD = 4;
    public const QUALITY_EASY = 5;

    public const MIN_EASE_FACTOR = 1.30;
    public const DEFAULT_EASE_FACTOR = 2.50;
    public const MASTERED_THRESHOLD_DAYS = 21;

    public static function apply(VocabEntryProgress $progress, int $quality): VocabEntryProgress
    {
        $quality = max(0, min(5, $quality));
        $correct = $quality >= 3;

        $ef = (float) ($progress->ease_factor ?: self::DEFAULT_EASE_FACTOR);
        $reps = (int) $progress->repetitions;
        $interval = (int) $progress->interval_days;

        if ($correct) {
            $reps++;
            if ($reps === 1) {
                $interval = 1;
            } elseif ($reps === 2) {
                $interval = 6;
            } else {
                $interval = (int) max(1, round($interval * $ef));
            }
        } else {
            $progress->lapses = (int) $progress->lapses + 1;
            $reps = 0;
            $interval = 1;
        }

        $ef = $ef + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));
        $ef = max(self::MIN_EASE_FACTOR, $ef);

        $progress->ease_factor = round($ef, 2);
        $progress->repetitions = $reps;
        $progress->interval_days = $interval;
        $progress->due_at = now()->addDays($interval);
        $progress->last_reviewed_at = now();
        $progress->last_quality = $quality;
        $progress->total_reviews = (int) $progress->total_reviews + 1;
        $progress->status = self::deriveStatus($correct, $reps, $interval);

        $progress->save();

        return $progress;
    }

    protected static function deriveStatus(bool $correct, int $reps, int $interval): string
    {
        if (!$correct) {
            return VocabEntryProgress::STATUS_LEARNING;
        }
        if ($interval >= self::MASTERED_THRESHOLD_DAYS) {
            return VocabEntryProgress::STATUS_MASTERED;
        }
        if ($reps >= 2) {
            return VocabEntryProgress::STATUS_REVIEW;
        }
        return VocabEntryProgress::STATUS_LEARNING;
    }
}
