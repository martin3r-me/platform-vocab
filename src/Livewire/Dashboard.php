<?php

namespace Platform\Vocab\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabEntry;

class Dashboard extends Component
{
    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => null,
            'modelId' => null,
            'subject' => 'Vokabeln Dashboard',
            'description' => 'Übersicht aller Vokabellisten',
            'url' => route('vocab.dashboard'),
            'source' => 'vocab.dashboard',
            'recipients' => [],
            'meta' => [
                'view_type' => 'dashboard',
            ],
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        $listsCount = VocabList::where('team_id', $team->id)->count();
        $entriesCount = VocabEntry::whereHas('vocabList', fn ($q) => $q->where('team_id', $team->id))->count();

        $languages = VocabList::where('team_id', $team->id)
            ->selectRaw('DISTINCT target_language')
            ->pluck('target_language')
            ->filter();

        $recentLists = VocabList::where('team_id', $team->id)
            ->withCount('entries')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Language distribution for activity sidebar
        $languageStats = VocabList::where('team_id', $team->id)
            ->selectRaw('target_language, COUNT(*) as list_count')
            ->groupBy('target_language')
            ->get()
            ->map(fn ($row) => [
                'language' => Sidebar::languageName($row->target_language),
                'code' => strtoupper($row->target_language),
                'count' => $row->list_count,
            ]);

        // Level distribution for activity sidebar
        $levelStats = VocabList::where('team_id', $team->id)
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->selectRaw('level, COUNT(*) as list_count')
            ->groupBy('level')
            ->orderBy('level')
            ->get()
            ->map(fn ($row) => [
                'level' => $row->level,
                'count' => $row->list_count,
            ]);

        return view('vocab::livewire.dashboard', [
            'listsCount' => $listsCount,
            'entriesCount' => $entriesCount,
            'languagesCount' => $languages->count(),
            'recentLists' => $recentLists,
            'languageStats' => $languageStats,
            'levelStats' => $levelStats,
        ])->layout('platform::layouts.app');
    }
}
