<?php

namespace Platform\Vocab\Livewire;

use Livewire\Component;
use Platform\Vocab\Models\VocabEntry;
use Platform\Vocab\Models\VocabEntryProgress;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabListEnrollment;

class Sidebar extends Component
{
    protected static array $languageNames = [
        'de' => 'Deutsch',
        'en' => 'Englisch',
        'it' => 'Italienisch',
        'fr' => 'Französisch',
        'es' => 'Spanisch',
        'pt' => 'Portugiesisch',
        'nl' => 'Niederländisch',
        'pl' => 'Polnisch',
        'ru' => 'Russisch',
        'ja' => 'Japanisch',
        'zh' => 'Chinesisch',
        'ko' => 'Koreanisch',
        'ar' => 'Arabisch',
        'tr' => 'Türkisch',
        'sv' => 'Schwedisch',
        'da' => 'Dänisch',
        'no' => 'Norwegisch',
        'fi' => 'Finnisch',
        'el' => 'Griechisch',
        'cs' => 'Tschechisch',
        'hr' => 'Kroatisch',
        'ro' => 'Rumänisch',
        'hu' => 'Ungarisch',
        'la' => 'Latein',
    ];

    public static function languageName(string $code): string
    {
        return static::$languageNames[strtolower($code)] ?? strtoupper($code);
    }

    public function render()
    {
        $user = auth()->user();

        if (!$user) {
            return view('vocab::livewire.sidebar', [
                'groupedLists' => collect(),
                'enrolledLists' => collect(),
                'dueCount' => 0,
            ]);
        }

        $teamId = $user->currentTeam->id;

        $dueCount = $this->dueCardCount($user->id);

        $recentLists = VocabList::where('team_id', $teamId)
            ->withCount('entries')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        $groupedLists = $recentLists->groupBy(function ($list) {
            return strtoupper($list->source_language) . ' → ' . strtoupper($list->target_language);
        });

        $enrolledLists = VocabList::query()
            ->where('team_id', $teamId)
            ->whereHas('enrollments', fn ($q) => $q->where('user_id', $user->id))
            ->withCount('entries')
            ->limit(8)
            ->get()
            ->map(function (VocabList $list) use ($user) {
                $list->setAttribute('mastery_pct', $list->masteryFor($user->id)['pct']);
                return $list;
            });

        return view('vocab::livewire.sidebar', [
            'groupedLists' => $groupedLists,
            'enrolledLists' => $enrolledLists,
            'dueCount' => $dueCount,
        ]);
    }

    protected function dueCardCount(int $userId): int
    {
        $enrolledListIds = VocabListEnrollment::where('user_id', $userId)->pluck('vocab_list_id');
        if ($enrolledListIds->isEmpty()) {
            return 0;
        }

        $entryIds = VocabEntry::whereIn('vocab_list_id', $enrolledListIds)->pluck('id');
        if ($entryIds->isEmpty()) {
            return 0;
        }

        $dueProgress = VocabEntryProgress::query()
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

        return $dueProgress + min($newCount, 10);
    }
}
