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
            ->count();

        $recentLists = VocabList::where('team_id', $team->id)
            ->withCount('entries')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return view('vocab::livewire.dashboard', [
            'listsCount' => $listsCount,
            'entriesCount' => $entriesCount,
            'languagesCount' => $languages,
            'recentLists' => $recentLists,
        ])->layout('platform::layouts.app');
    }
}
