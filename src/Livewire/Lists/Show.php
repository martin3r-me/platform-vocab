<?php

namespace Platform\Vocab\Livewire\Lists;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabEntry;
use Platform\Vocab\Models\VocabListEnrollment;

class Show extends Component
{
    public string $uuid;
    public ?VocabList $list = null;

    // Inline Add
    public string $newTerm = '';
    public string $newTranslation = '';
    public string $newGender = '';
    public string $newWordType = '';

    // Edit Entry
    public ?int $editingEntryId = null;
    public string $editTerm = '';
    public string $editTranslation = '';
    public string $editGender = '';
    public string $editPlural = '';
    public string $editWordType = '';
    public string $editExampleSentence = '';
    public string $editNotes = '';
    public string $editPronunciation = '';

    // Edit List
    public bool $showEditListModal = false;
    public string $editListName = '';
    public string $editListDescription = '';
    public string $editListLevel = '';

    // Generate More
    public bool $showGenerateModal = false;
    public string $generateTopic = '';
    public int $generateCount = 10;
    public bool $generating = false;

    // TTS
    public ?int $ttsPlayingId = null;

    // Generate Examples
    public bool $generatingExamples = false;

    public function mount(string $uuid)
    {
        $this->uuid = $uuid;
        $team = Auth::user()->currentTeam;
        $this->list = VocabList::where('team_id', $team->id)->where('uuid', $uuid)->firstOrFail();
    }

    public function enroll(): void
    {
        VocabListEnrollment::firstOrCreate(
            ['user_id' => Auth::id(), 'vocab_list_id' => $this->list->id],
            ['enrolled_at' => now()]
        );
    }

    public function unenroll(): void
    {
        VocabListEnrollment::where('user_id', Auth::id())
            ->where('vocab_list_id', $this->list->id)
            ->delete();
    }

    public function addEntry()
    {
        $this->validate([
            'newTerm' => 'required|string|max:255',
            'newTranslation' => 'required|string|max:255',
        ]);

        $maxSort = $this->list->entries()->max('sort_order') ?? 0;

        VocabEntry::create([
            'vocab_list_id' => $this->list->id,
            'term' => $this->newTerm,
            'translation' => $this->newTranslation,
            'gender' => $this->newGender ?: null,
            'word_type' => $this->newWordType ?: null,
            'sort_order' => $maxSort + 1,
        ]);

        $this->reset(['newTerm', 'newTranslation', 'newGender', 'newWordType']);
    }

    public function startEditing(int $entryId)
    {
        $entry = VocabEntry::findOrFail($entryId);
        $this->editingEntryId = $entryId;
        $this->editTerm = $entry->term;
        $this->editTranslation = $entry->translation;
        $this->editGender = $entry->gender ?? '';
        $this->editPlural = $entry->plural ?? '';
        $this->editWordType = $entry->word_type ?? '';
        $this->editExampleSentence = $entry->example_sentence ?? '';
        $this->editNotes = $entry->notes ?? '';
        $this->editPronunciation = $entry->pronunciation ?? '';
    }

    public function saveEntry()
    {
        $this->validate([
            'editTerm' => 'required|string|max:255',
            'editTranslation' => 'required|string|max:255',
        ]);

        $entry = VocabEntry::findOrFail($this->editingEntryId);
        $entry->update([
            'term' => $this->editTerm,
            'translation' => $this->editTranslation,
            'gender' => $this->editGender ?: null,
            'plural' => $this->editPlural ?: null,
            'word_type' => $this->editWordType ?: null,
            'example_sentence' => $this->editExampleSentence ?: null,
            'notes' => $this->editNotes ?: null,
            'pronunciation' => $this->editPronunciation ?: null,
        ]);

        $this->editingEntryId = null;
    }

    public function cancelEditing()
    {
        $this->editingEntryId = null;
    }

    public function deleteEntry(int $entryId)
    {
        VocabEntry::where('vocab_list_id', $this->list->id)->findOrFail($entryId)->delete();
    }

    public function openEditListModal()
    {
        $this->editListName = $this->list->name;
        $this->editListDescription = $this->list->description ?? '';
        $this->editListLevel = $this->list->level ?? '';
        $this->showEditListModal = true;
    }

    public function saveListDetails()
    {
        $this->validate(['editListName' => 'required|string|max:255']);

        $this->list->update([
            'name' => $this->editListName,
            'description' => $this->editListDescription ?: null,
            'level' => $this->editListLevel ?: null,
        ]);
        $this->list->refresh();
        $this->showEditListModal = false;
    }

    public function playTts(int $entryId)
    {
        $entry = VocabEntry::where('vocab_list_id', $this->list->id)->findOrFail($entryId);
        $this->ttsPlayingId = $entryId;

        try {
            $apiKey = config('services.openai.api_key')
                ?: config('services.openai.key')
                ?: env('OPENAI_API_KEY');

            if (!$apiKey) {
                $this->ttsPlayingId = null;
                return;
            }

            $text = $entry->term;
            if ($entry->example_sentence) {
                $text .= ' ... ' . $entry->example_sentence;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])
                ->timeout(15)
                ->post('https://api.openai.com/v1/audio/speech', [
                    'model' => 'gpt-4o-mini-tts',
                    'input' => $text,
                    'voice' => 'nova',
                    'response_format' => 'mp3',
                    'speed' => 0.9,
                    'instructions' => 'Speak clearly and naturally. First say the word, then pause briefly, then say the example sentence. Pronounce as a native speaker would.',
                ]);

            if ($response->successful()) {
                $base64 = base64_encode($response->body());
                $this->dispatch('play-tts', audio: "data:audio/mpeg;base64,{$base64}");
            }
        } catch (\Throwable $e) {
            // Silently fail - TTS is a nice-to-have
        }

        $this->ttsPlayingId = null;
    }

    public function generateExamples()
    {
        $this->generatingExamples = true;

        try {
            $entriesWithout = $this->list->entries()
                ->whereNull('example_sentence')
                ->orWhere('example_sentence', '')
                ->get();

            if ($entriesWithout->isEmpty()) {
                $this->generatingExamples = false;
                return;
            }

            $entriesData = $entriesWithout->map(fn ($e) => [
                'id' => $e->id,
                'term' => $e->term,
                'translation' => $e->translation,
                'word_type' => $e->word_type,
            ])->toArray();

            $prompt = \Platform\Vocab\Prompts\VocabPrompts::generateExamples(
                $entriesData,
                $this->list->source_language,
                $this->list->target_language,
                $this->list->level ?? 'B1'
            );

            $openAi = app(\Platform\Core\Services\OpenAiService::class);
            $result = $openAi->chat(
                [['role' => 'user', 'content' => $prompt]],
                'gpt-4o-mini',
                ['tools' => false, 'max_tokens' => 3000, 'temperature' => 0.6]
            );

            $content = $result['content'] ?? '';
            $jsonMatch = [];
            if (preg_match('/\[[\s\S]*\]/', $content, $jsonMatch)) {
                $examples = json_decode($jsonMatch[0], true);
            } else {
                $examples = json_decode($content, true);
            }

            if (is_array($examples)) {
                foreach ($examples as $item) {
                    if (empty($item['id']) || empty($item['example_sentence'])) continue;
                    VocabEntry::where('id', (int)$item['id'])
                        ->where('vocab_list_id', $this->list->id)
                        ->update(['example_sentence' => (string)$item['example_sentence']]);
                }
            }
        } catch (\Throwable $e) {
            $this->addError('examples', 'Fehler: ' . $e->getMessage());
        }

        $this->generatingExamples = false;
    }

    public function generateMore()
    {
        $this->validate([
            'generateTopic' => 'required|string|max:255',
            'generateCount' => 'required|integer|min:5|max:50',
        ]);

        $this->generating = true;

        try {
            $openAi = app(\Platform\Core\Services\OpenAiService::class);
            $prompt = \Platform\Vocab\Prompts\VocabPrompts::generateVocab(
                $this->generateTopic,
                $this->list->source_language,
                $this->list->target_language,
                $this->list->level ?? 'A1',
                $this->generateCount
            );

            $result = $openAi->chat(
                [['role' => 'user', 'content' => $prompt]],
                'gpt-4o-mini',
                ['tools' => false, 'max_tokens' => 4000, 'temperature' => 0.7]
            );

            $content = $result['content'] ?? '';
            $jsonMatch = [];
            if (preg_match('/\[[\s\S]*\]/', $content, $jsonMatch)) {
                $vocabData = json_decode($jsonMatch[0], true);
            } else {
                $vocabData = json_decode($content, true);
            }

            if (is_array($vocabData)) {
                $maxSort = $this->list->entries()->max('sort_order') ?? 0;
                foreach ($vocabData as $data) {
                    if (empty($data['term']) || empty($data['translation'])) continue;
                    $maxSort++;
                    VocabEntry::create([
                        'vocab_list_id' => $this->list->id,
                        'term' => (string)$data['term'],
                        'translation' => (string)$data['translation'],
                        'gender' => $data['gender'] ?? null,
                        'plural' => $data['plural'] ?? null,
                        'word_type' => $data['word_type'] ?? null,
                        'example_sentence' => $data['example_sentence'] ?? null,
                        'notes' => $data['notes'] ?? null,
                        'pronunciation' => $data['pronunciation'] ?? null,
                        'sort_order' => $maxSort,
                    ]);
                }
            }

            $this->showGenerateModal = false;
            $this->generating = false;
            $this->reset(['generateTopic']);
        } catch (\Throwable $e) {
            $this->generating = false;
            $this->addError('generate', 'Fehler: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $entries = $this->list->entries()->orderBy('sort_order')->get();

        // Stats for activity sidebar
        $totalEntries = $entries->count();
        $withExamples = $entries->filter(fn ($e) => !empty($e->example_sentence))->count();
        $withoutExamples = $totalEntries - $withExamples;
        $wordTypeStats = $entries->groupBy('word_type')->map->count()->filter(fn ($v, $k) => $k !== '')->sortDesc();

        $userId = Auth::id();
        $enrollment = $this->list->enrollmentFor($userId);
        $mastery = $this->list->masteryFor($userId);

        return view('vocab::livewire.lists.show', [
            'entries' => $entries,
            'totalEntries' => $totalEntries,
            'withExamples' => $withExamples,
            'withoutExamples' => $withoutExamples,
            'wordTypeStats' => $wordTypeStats,
            'enrollment' => $enrollment,
            'mastery' => $mastery,
        ])->layout('platform::layouts.app');
    }
}
