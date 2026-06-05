<?php

namespace Platform\Vocab\Livewire\Catalogs;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Vocab\Models\VocabCatalog;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabListEnrollment;

class Show extends Component
{
    public string $uuid;
    public ?VocabCatalog $catalog = null;

    public bool $showAttachModal = false;
    public string $attachSearch = '';
    public array $selectedListIds = [];

    public bool $showEditModal = false;
    public string $editName = '';
    public string $editDescription = '';
    public string $editVisibility = VocabCatalog::VISIBILITY_TEAM;
    public string $editCoverColor = '#8b5cf6';

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
        $user = Auth::user();
        $this->catalog = VocabCatalog::visibleTo($user->id, $user->currentTeam->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function enroll(int $listId): void
    {
        VocabListEnrollment::firstOrCreate(
            ['user_id' => Auth::id(), 'vocab_list_id' => $listId],
            ['enrolled_at' => now()]
        );
    }

    public function unenroll(int $listId): void
    {
        VocabListEnrollment::where('user_id', Auth::id())
            ->where('vocab_list_id', $listId)
            ->delete();
    }

    public function openAttachModal(): void
    {
        $this->reset(['attachSearch', 'selectedListIds']);
        $this->showAttachModal = true;
    }

    public function attachLists(): void
    {
        $this->ensureOwner();

        if (empty($this->selectedListIds)) {
            return;
        }

        $teamId = Auth::user()->currentTeam->id;

        $maxSort = (int) $this->catalog->lists()->max('vocab_catalog_list.sort_order');

        $valid = VocabList::where('team_id', $teamId)
            ->whereIn('id', $this->selectedListIds)
            ->pluck('id');

        $sync = [];
        foreach ($valid as $listId) {
            $maxSort++;
            $sync[$listId] = ['sort_order' => $maxSort];
        }

        $this->catalog->lists()->syncWithoutDetaching($sync);

        $this->showAttachModal = false;
        $this->reset(['attachSearch', 'selectedListIds']);
    }

    public function detachList(int $listId): void
    {
        $this->ensureOwner();
        $this->catalog->lists()->detach($listId);
    }

    public function openEditModal(): void
    {
        $this->ensureOwner();
        $this->editName = $this->catalog->name;
        $this->editDescription = $this->catalog->description ?? '';
        $this->editVisibility = $this->catalog->visibility;
        $this->editCoverColor = $this->catalog->cover_color ?? '#8b5cf6';
        $this->showEditModal = true;
    }

    public function saveCatalog(): void
    {
        $this->ensureOwner();

        $this->validate([
            'editName' => 'required|string|max:255',
            'editVisibility' => 'required|in:team,personal',
        ]);

        $this->catalog->update([
            'name' => $this->editName,
            'description' => $this->editDescription ?: null,
            'visibility' => $this->editVisibility,
            'cover_color' => $this->editCoverColor ?: null,
        ]);
        $this->catalog->refresh();
        $this->showEditModal = false;
    }

    public function deleteCatalog()
    {
        $this->ensureOwner();
        $this->catalog->delete();

        return $this->redirect(route('vocab.catalogs.index'), navigate: true);
    }

    protected function ensureOwner(): void
    {
        abort_unless($this->catalog->isOwnedBy(Auth::id()), 403);
    }

    public function render()
    {
        $user = Auth::user();
        $userId = $user->id;
        $teamId = $user->currentTeam->id;

        $lists = $this->catalog->lists()
            ->withCount('entries')
            ->withCount(['enrollments as is_enrolled' => fn ($q) => $q->where('user_id', $userId)])
            ->get();

        $lists->each(function (VocabList $list) use ($userId) {
            $list->setAttribute(
                'mastery_pct',
                $list->is_enrolled ? $list->masteryFor($userId)['pct'] : null
            );
        });

        $attachableLists = collect();
        if ($this->showAttachModal) {
            $existingIds = $this->catalog->lists()->pluck('vocab_lists.id');
            $query = VocabList::visibleTo($userId, $teamId)
                ->whereNotIn('id', $existingIds)
                ->withCount('entries');
            if ($this->attachSearch) {
                $query->where('name', 'like', "%{$this->attachSearch}%");
            }
            $attachableLists = $query->orderBy('name')->limit(50)->get();
        }

        $isOwner = $this->catalog->isOwnedBy($userId);

        return view('vocab::livewire.catalogs.show', [
            'lists' => $lists,
            'attachableLists' => $attachableLists,
            'isOwner' => $isOwner,
        ])->layout('platform::layouts.app');
    }
}
