<?php

namespace Platform\Vocab\Livewire\Catalogs;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Vocab\Models\VocabCatalog;

class Index extends Component
{
    public string $search = '';

    public bool $showCreateModal = false;
    public string $newName = '';
    public string $newDescription = '';
    public string $newVisibility = VocabCatalog::VISIBILITY_TEAM;
    public string $newCoverColor = '#8b5cf6';

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newDescription']);
        $this->newVisibility = VocabCatalog::VISIBILITY_TEAM;
        $this->newCoverColor = '#8b5cf6';
        $this->showCreateModal = true;
    }

    public function createCatalog()
    {
        $this->validate([
            'newName' => 'required|string|max:255',
            'newVisibility' => 'required|in:team,personal',
            'newCoverColor' => 'nullable|string|max:7',
        ]);

        $user = Auth::user();

        $catalog = VocabCatalog::create([
            'team_id' => $user->currentTeam->id,
            'created_by_user_id' => $user->id,
            'name' => $this->newName,
            'description' => $this->newDescription ?: null,
            'visibility' => $this->newVisibility,
            'cover_color' => $this->newCoverColor ?: null,
        ]);

        $this->showCreateModal = false;

        return $this->redirect(
            route('vocab.catalogs.show', ['uuid' => $catalog->uuid]),
            navigate: true
        );
    }

    public function deleteCatalog(int $id)
    {
        $user = Auth::user();
        $catalog = VocabCatalog::visibleTo($user->id, $user->currentTeam->id)
            ->findOrFail($id);

        if (!$catalog->isOwnedBy($user->id) && $catalog->isPersonal()) {
            return;
        }

        $catalog->delete();
    }

    public function render()
    {
        $user = Auth::user();

        $query = VocabCatalog::query()
            ->visibleTo($user->id, $user->currentTeam->id)
            ->withCount('lists');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $catalogs = $query->orderBy('sort_order')->orderBy('name')->get();

        return view('vocab::livewire.catalogs.index', [
            'catalogs' => $catalogs,
        ])->layout('platform::layouts.app');
    }
}
