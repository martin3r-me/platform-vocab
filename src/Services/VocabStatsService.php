<?php

namespace Platform\Vocab\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Platform\Vocab\Models\VocabEntry;
use Platform\Vocab\Models\VocabEntryProgress;
use Platform\Vocab\Models\VocabListEnrollment;

class VocabStatsService
{
    public function streak(int $userId): array
    {
        $rows = VocabEntryProgress::query()
            ->where('user_id', $userId)
            ->whereNotNull('last_reviewed_at')
            ->selectRaw('DATE(last_reviewed_at) as day, COUNT(*) as reviews')
            ->groupBy('day')
            ->orderBy('day', 'desc')
            ->get();

        if ($rows->isEmpty()) {
            return ['current' => 0, 'longest' => 0, 'last_active_at' => null];
        }

        $days = $rows->pluck('day')->map(fn ($d) => Carbon::parse($d)->startOfDay())->all();
        $lastActive = $days[0];

        $current = 0;
        $today = Carbon::today();
        $cursor = $today->copy();

        if ($lastActive->equalTo($today) || $lastActive->equalTo($today->copy()->subDay())) {
            if ($lastActive->equalTo($today->copy()->subDay())) {
                $cursor = $today->copy()->subDay();
            }
            foreach ($days as $day) {
                if ($day->equalTo($cursor)) {
                    $current++;
                    $cursor = $cursor->copy()->subDay();
                } elseif ($day->lt($cursor)) {
                    break;
                }
            }
        }

        $longest = $this->longestStreak($days);

        return [
            'current' => $current,
            'longest' => max($longest, $current),
            'last_active_at' => $lastActive,
        ];
    }

    protected function longestStreak(array $days): int
    {
        if (empty($days)) {
            return 0;
        }
        $longest = 1;
        $run = 1;
        for ($i = 1; $i < count($days); $i++) {
            $prev = $days[$i - 1];
            $cur = $days[$i];
            if ($prev->copy()->subDay()->equalTo($cur)) {
                $run++;
                $longest = max($longest, $run);
            } else {
                $run = 1;
            }
        }
        return $longest;
    }

    public function todayProgress(int $userId): array
    {
        $todayReviews = VocabEntryProgress::query()
            ->where('user_id', $userId)
            ->whereDate('last_reviewed_at', Carbon::today())
            ->count();

        return ['reviewed_today' => $todayReviews];
    }

    public function masteryTotals(int $userId): array
    {
        $enrolledListIds = VocabListEnrollment::where('user_id', $userId)->pluck('vocab_list_id');
        if ($enrolledListIds->isEmpty()) {
            return ['total_entries' => 0, 'mastered' => 0, 'reviewed' => 0, 'pct' => 0];
        }

        $entryIds = VocabEntry::whereIn('vocab_list_id', $enrolledListIds)->pluck('id');
        $total = $entryIds->count();
        if ($total === 0) {
            return ['total_entries' => 0, 'mastered' => 0, 'reviewed' => 0, 'pct' => 0];
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
            'total_entries' => $total,
            'mastered' => $mastered,
            'reviewed' => $progress->count(),
            'pct' => (int) round($mastered / $total * 100),
        ];
    }

    public function heatmap(int $userId, int $days = 90): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $rows = VocabEntryProgress::query()
            ->where('user_id', $userId)
            ->whereNotNull('last_reviewed_at')
            ->where('last_reviewed_at', '>=', $from)
            ->selectRaw('DATE(last_reviewed_at) as day, COUNT(*) as reviews')
            ->groupBy('day')
            ->get()
            ->keyBy(fn ($r) => $r->day);

        $grid = [];
        for ($i = 0; $i < $days; $i++) {
            $day = Carbon::today()->subDays($days - 1 - $i);
            $key = $day->toDateString();
            $count = (int) ($rows[$key]->reviews ?? 0);
            $grid[] = [
                'date' => $key,
                'count' => $count,
                'intensity' => $this->intensity($count),
                'weekday' => $day->dayOfWeekIso,
            ];
        }

        return $grid;
    }

    protected function intensity(int $count): int
    {
        return match (true) {
            $count === 0 => 0,
            $count <= 4 => 1,
            $count <= 10 => 2,
            $count <= 20 => 3,
            default => 4,
        };
    }

    public function nextMilestone(int $mastered): array
    {
        $milestones = [10, 25, 50, 100, 200, 300, 500, 1000];
        foreach ($milestones as $m) {
            if ($mastered < $m) {
                return ['target' => $m, 'remaining' => $m - $mastered];
            }
        }
        $next = ((int) floor($mastered / 1000) + 1) * 1000;
        return ['target' => $next, 'remaining' => $next - $mastered];
    }

    public function dueCount(int $userId, int $newCardsCap = 10): int
    {
        $enrolledListIds = VocabListEnrollment::where('user_id', $userId)->pluck('vocab_list_id');
        if ($enrolledListIds->isEmpty()) {
            return 0;
        }

        $entryIds = VocabEntry::whereIn('vocab_list_id', $enrolledListIds)->pluck('id');
        if ($entryIds->isEmpty()) {
            return 0;
        }

        $due = VocabEntryProgress::query()
            ->where('user_id', $userId)
            ->whereIn('vocab_entry_id', $entryIds)
            ->where(function ($q) {
                $q->whereNull('due_at')->orWhere('due_at', '<=', now());
            })
            ->count();

        $progressedIds = VocabEntryProgress::query()
            ->where('user_id', $userId)
            ->whereIn('vocab_entry_id', $entryIds)
            ->pluck('vocab_entry_id');

        $newCount = $entryIds->diff($progressedIds)->count();

        return $due + min($newCount, $newCardsCap);
    }

    public function totalReviews(int $userId): int
    {
        return (int) VocabEntryProgress::query()
            ->where('user_id', $userId)
            ->sum('total_reviews');
    }
}
