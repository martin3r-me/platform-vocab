<?php

namespace Platform\Vocab\Services;

use Carbon\Carbon;
use Platform\Vocab\Models\VocabAchievement;
use Platform\Vocab\Models\VocabEntry;
use Platform\Vocab\Models\VocabEntryProgress;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabListEnrollment;
use Platform\Vocab\Models\VocabUserSettings;

class VocabAchievementService
{
    public function __construct(protected VocabStatsService $stats)
    {
    }

    public function evaluate(int $userId): array
    {
        $earnedCodes = VocabAchievement::where('user_id', $userId)->pluck('code')->all();
        $deserved = $this->deservedCodes($userId);

        $newlyAwarded = array_values(array_diff($deserved, $earnedCodes));
        if (empty($newlyAwarded)) {
            return [];
        }

        foreach ($newlyAwarded as $code) {
            VocabAchievement::firstOrCreate(
                ['user_id' => $userId, 'code' => $code],
                ['awarded_at' => now()]
            );
        }

        return $newlyAwarded;
    }

    public function earned(int $userId): \Illuminate\Support\Collection
    {
        return VocabAchievement::where('user_id', $userId)
            ->orderBy('awarded_at', 'desc')
            ->get();
    }

    public function summary(int $userId): array
    {
        $earned = $this->earned($userId);
        $earnedCodes = $earned->pluck('code')->all();
        $defs = AchievementCatalog::all();

        $items = [];
        foreach ($defs as $code => $def) {
            $earnedAt = $earned->firstWhere('code', $code)?->awarded_at;
            $items[] = array_merge($def, [
                'code' => $code,
                'earned' => in_array($code, $earnedCodes, true),
                'awarded_at' => $earnedAt,
            ]);
        }

        usort($items, fn ($a, $b) => ($a['sort'] ?? 999) <=> ($b['sort'] ?? 999));

        return [
            'items' => $items,
            'earned_count' => count($earnedCodes),
            'total_count' => count($defs),
        ];
    }

    protected function deservedCodes(int $userId): array
    {
        $codes = [];

        $totalReviews = $this->stats->totalReviews($userId);
        $mastery = $this->stats->masteryTotals($userId);
        $streak = $this->stats->streak($userId);
        $today = $this->stats->todayProgress($userId);
        $settings = VocabUserSettings::forUser($userId);

        if ($totalReviews >= 1) {
            $codes[] = 'first_review';
        }

        if (VocabListEnrollment::where('user_id', $userId)->exists()) {
            $codes[] = 'first_enrollment';
        }

        if ($settings->daily_goal > 0 && $today['reviewed_today'] >= $settings->daily_goal) {
            $codes[] = 'goal_hit';
        }

        $codes = array_merge($codes, AchievementCatalog::codesForStreak($streak['current']));
        $codes = array_merge($codes, AchievementCatalog::codesForMastered($mastery['mastered']));
        $codes = array_merge($codes, AchievementCatalog::codesForTotalReviews($totalReviews));

        $activeListCount = VocabListEnrollment::where('user_id', $userId)->count();
        if ($activeListCount >= 3) {
            $codes[] = 'three_lists';
        }

        if ($this->hasFullyMasteredList($userId)) {
            $codes[] = 'first_full_list';
        }

        $easyCount = VocabEntryProgress::where('user_id', $userId)
            ->where('last_quality', SrsAlgorithm::QUALITY_EASY)
            ->count();
        if ($easyCount >= 50) {
            $codes[] = 'easy_50';
        }

        if ($this->qualifiesForComeback($userId)) {
            $codes[] = 'comeback';
        }

        return array_values(array_unique($codes));
    }

    protected function hasFullyMasteredList(int $userId): bool
    {
        $enrolledIds = VocabListEnrollment::where('user_id', $userId)->pluck('vocab_list_id');
        foreach ($enrolledIds as $listId) {
            $list = VocabList::find($listId);
            if (!$list) continue;
            $mastery = $list->masteryFor($userId);
            if ($mastery['total'] > 0 && $mastery['pct'] >= 100) {
                return true;
            }
        }
        return false;
    }

    protected function qualifiesForComeback(int $userId): bool
    {
        $lastTwo = VocabEntryProgress::where('user_id', $userId)
            ->whereNotNull('last_reviewed_at')
            ->orderBy('last_reviewed_at', 'desc')
            ->limit(2)
            ->pluck('last_reviewed_at');

        if ($lastTwo->count() < 2) {
            return false;
        }

        $latest = Carbon::parse($lastTwo[0]);
        $previous = Carbon::parse($lastTwo[1]);

        if (!$latest->isToday() && !$latest->isYesterday()) {
            return false;
        }

        return $previous->diffInDays($latest) >= 7;
    }
}
