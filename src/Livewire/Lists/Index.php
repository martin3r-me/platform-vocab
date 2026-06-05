<?php

namespace Platform\Vocab\Livewire\Lists;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\Vocab\Models\VocabList;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterLanguage = '';
    public string $filterLevel = '';

    // Create Modal
    public bool $showCreateModal = false;
    public string $newName = '';
    public string $newDescription = '';
    public string $newSourceLanguage = 'de';
    public string $newTargetLanguage = '';
    public string $newLevel = '';

    // Generate Modal
    public bool $showGenerateModal = false;
    public string $generateTopic = '';
    public string $generateSourceLanguage = 'de';
    public string $generateTargetLanguage = '';
    public string $generateLevel = 'A1';
    public int $generateCount = 20;
    public bool $generating = false;

    protected $queryString = ['search', 'filterLanguage', 'filterLevel'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function createList()
    {
        $this->validate([
            'newName' => 'required|string|max:255',
            'newSourceLanguage' => 'required|string|max:10',
            'newTargetLanguage' => 'required|string|max:10',
        ]);

        $team = Auth::user()->currentTeam;

        VocabList::create([
            'team_id' => $team->id,
            'name' => $this->newName,
            'description' => $this->newDescription ?: null,
            'source_language' => $this->newSourceLanguage,
            'target_language' => $this->newTargetLanguage,
            'level' => $this->newLevel ?: null,
        ]);

        $this->reset(['newName', 'newDescription', 'newTargetLanguage', 'newLevel']);
        $this->showCreateModal = false;
    }

    public function generateList()
    {
        $this->validate([
            'generateTopic' => 'required|string|max:255',
            'generateSourceLanguage' => 'required|string|max:10',
            'generateTargetLanguage' => 'required|string|max:10',
            'generateCount' => 'required|integer|min:5|max:50',
        ]);

        $this->generating = true;

        try {
            $team = Auth::user()->currentTeam;
            $openAi = app(\Platform\Core\Services\OpenAiService::class);

            $list = VocabList::create([
                'team_id' => $team->id,
                'name' => $this->generateTopic,
                'description' => "Automatisch generiert: {$this->generateTopic} ({$this->generateLevel})",
                'source_language' => $this->generateSourceLanguage,
                'target_language' => $this->generateTargetLanguage,
                'level' => $this->generateLevel ?: null,
            ]);

            $prompt = \Platform\Vocab\Prompts\VocabPrompts::generateVocab(
                $this->generateTopic,
                $this->generateSourceLanguage,
                $this->generateTargetLanguage,
                $this->generateLevel,
                $this->generateCount
            );

            $result = $openAi->chat(
                [['role' => 'user', 'content' => $prompt]],
                options: ['tools' => false, 'max_tokens' => 4000, 'temperature' => 0.7]
            );

            $content = $result['content'] ?? '';
            $jsonMatch = [];
            if (preg_match('/\[[\s\S]*\]/', $content, $jsonMatch)) {
                $vocabData = json_decode($jsonMatch[0], true);
            } else {
                $vocabData = json_decode($content, true);
            }

            if (is_array($vocabData)) {
                $sortOrder = 0;
                foreach ($vocabData as $data) {
                    if (empty($data['term']) || empty($data['translation'])) continue;
                    $sortOrder++;
                    \Platform\Vocab\Models\VocabEntry::create([
                        'vocab_list_id' => $list->id,
                        'term' => (string)$data['term'],
                        'translation' => (string)$data['translation'],
                        'gender' => $data['gender'] ?? null,
                        'plural' => $data['plural'] ?? null,
                        'word_type' => $data['word_type'] ?? null,
                        'example_sentence' => $data['example_sentence'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'pronunciation' => $data['pronunciation'] ?? null,
                        'sort_order' => $sortOrder,
                    ]);
                }
            }

            $this->reset(['generateTopic', 'generateTargetLanguage']);
            $this->showGenerateModal = false;
            $this->generating = false;

            return $this->redirect(route('vocab.lists.show', ['uuid' => $list->uuid]), navigate: true);
        } catch (\Throwable $e) {
            $this->generating = false;
            $this->addError('generate', 'Fehler beim Generieren: ' . $e->getMessage());
        }
    }

    public function deleteList(int $id)
    {
        $team = Auth::user()->currentTeam;
        $list = VocabList::where('team_id', $team->id)->findOrFail($id);
        $list->entries()->delete();
        $list->delete();
    }

    public function render()
    {
        $team = Auth::user()->currentTeam;

        $query = VocabList::where('team_id', $team->id)
            ->withCount('entries');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($this->filterLanguage) {
            $query->where('target_language', $this->filterLanguage);
        }

        if ($this->filterLevel) {
            $query->where('level', $this->filterLevel);
        }

        $lists = $query->orderBy('updated_at', 'desc')->paginate(20);

        $availableLanguages = VocabList::where('team_id', $team->id)
            ->selectRaw('DISTINCT target_language')
            ->pluck('target_language')
            ->filter()
            ->values();

        return view('vocab::livewire.lists.index', [
            'lists' => $lists,
            'availableLanguages' => $availableLanguages,
        ])->layout('platform::layouts.app');
    }
}
