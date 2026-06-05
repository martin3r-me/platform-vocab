<?php

namespace Platform\Vocab\Livewire;

use Livewire\Component;
use Platform\Vocab\Models\VocabList;

class Sidebar extends Component
{
    public function render()
    {
        $user = auth()->user();

        if (!$user) {
            return view('vocab::livewire.sidebar', []);
        }

        $recentLists = VocabList::where('team_id', $user->currentTeam->id)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return view('vocab::livewire.sidebar', [
            'recentLists' => $recentLists,
        ]);
    }
}
