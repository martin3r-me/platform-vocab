<?php

namespace Platform\Vocab\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Vocab\Models\VocabEntry;
use Platform\Vocab\Models\VocabEntryProgress;
use Platform\Vocab\Models\VocabListEnrollment;
use Platform\Vocab\Models\VocabUserSettings;
use Platform\Vocab\Services\AchievementCatalog;
use Platform\Vocab\Services\SrsAlgorithm;
use Platform\Vocab\Services\TtsService;

class Review extends Component
{
    public int $limit = 20;
    public int $newCardsLimit = 10;

    public array $queue = [];
    public int $currentIndex = 0;
    public bool $showAnswer = false;
    public bool $finished = false;

    public array $results = [];

    public bool $autoPlayTts = true;
    public bool $keyboardShortcuts = true;
    public bool $muteAudio = false;
    public bool $listeningFirst = false;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user) {
            $settings = VocabUserSettings::forUser($user->id, $user->currentTeam?->id);
            $this->autoPlayTts = $settings->auto_play_tts;
            $this->keyboardShortcuts = $settings->keyboard_shortcuts;
            $this->listeningFirst = $settings->listening_first_default;
        }
        $this->loadQueue();

        if ($this->listeningFirst && !$this->muteAudio && !empty($this->queue)) {
            $this->playCurrentTts();
        }
    }

    public function toggleMute(): void
    {
        $this->muteAudio = !$this->muteAudio;
    }

    public function toggleListeningFirst(): void
    {
        $this->listeningFirst = !$this->listeningFirst;
        if ($this->listeningFirst && !$this->muteAudio && !$this->showAnswer) {
            $this->playCurrentTts();
        }
    }

    protected function loadQueue(): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $enrolledListIds = VocabListEnrollment::where('user_id', $userId)
            ->pluck('vocab_list_id');

        if ($enrolledListIds->isEmpty()) {
            return;
        }

        $entryIds = VocabEntry::whereIn('vocab_list_id', $enrolledListIds)->pluck('id');
        if ($entryIds->isEmpty()) {
            return;
        }

        $dueEntryIds = VocabEntryProgress::query()
            ->where('user_id', $userId)
            ->whereIn('vocab_entry_id', $entryIds)
            ->where(function ($q) {
                $q->whereNull('due_at')->orWhere('due_at', '<=', now());
            })
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END, due_at ASC')
            ->limit($this->limit)
            ->pluck('vocab_entry_id');

        $remaining = max(0, $this->limit - $dueEntryIds->count());
        $newEntryIds = collect();

        if ($remaining > 0) {
            $progressedEntryIds = VocabEntryProgress::query()
                ->where('user_id', $userId)
                ->whereIn('vocab_entry_id', $entryIds)
                ->pluck('vocab_entry_id');

            $newEntryIds = $entryIds->diff($progressedEntryIds)
                ->take(min($remaining, $this->newCardsLimit));
        }

        $orderedIds = $dueEntryIds->merge($newEntryIds);

        $entries = VocabEntry::with('vocabList:id,name,source_language,target_language')
            ->whereIn('id', $orderedIds)
            ->get()
            ->keyBy('id');

        $this->queue = $orderedIds
            ->map(function ($id) use ($entries) {
                $entry = $entries->get($id);
                if (!$entry) {
                    return null;
                }
                return [
                    'entry_id' => $entry->id,
                    'list_id' => $entry->vocab_list_id,
                    'term' => $entry->term,
                    'translation' => $entry->translation,
                    'example_sentence' => $entry->example_sentence,
                    'notes' => $entry->notes,
                    'list_name' => $entry->vocabList?->name,
                    'source_language' => strtoupper($entry->vocabList?->source_language ?? ''),
                    'target_language' => strtoupper($entry->vocabList?->target_language ?? ''),
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function reveal(): void
    {
        $this->showAnswer = true;

        if ($this->autoPlayTts && !$this->muteAudio) {
            $this->playCurrentTts();
        }
    }

    public function playCurrentTts(): void
    {
        $card = $this->queue[$this->currentIndex] ?? null;
        if (!$card) {
            return;
        }

        $audio = app(TtsService::class)->synthesizeTermAndExample(
            $card['term'],
            $card['example_sentence'] ?? null
        );

        if ($audio) {
            $this->dispatch('play-tts', audio: $audio);
        }
    }

    public function rate(int $quality): void
    {
        if (!$this->showAnswer || $this->finished) {
            return;
        }

        $card = $this->queue[$this->currentIndex] ?? null;
        if (!$card) {
            return;
        }

        $progress = VocabEntryProgress::recordReview(Auth::id(), $card['entry_id'], $quality);

        $this->results[] = [
            'entry_id' => $card['entry_id'],
            'list_id' => $card['list_id'],
            'quality' => $quality,
        ];

        foreach ($progress->getAttribute('newly_awarded') ?? [] as $code) {
            $def = AchievementCatalog::get($code);
            if (!$def) continue;
            $this->dispatch('achievement-earned',
                code: $code,
                name: $def['name'],
                description: $def['description'],
                icon: $def['icon'],
                tier: $def['tier'],
            );
        }

        $this->currentIndex++;
        $this->showAnswer = false;

        if ($this->currentIndex >= count($this->queue)) {
            $this->finished = true;
            $this->touchStudiedEnrollments();
        } elseif ($this->listeningFirst && !$this->muteAudio) {
            $this->playCurrentTts();
        }
    }

    protected function touchStudiedEnrollments(): void
    {
        $listIds = collect($this->results)->pluck('list_id')->filter()->unique();
        if ($listIds->isEmpty()) {
            return;
        }

        VocabListEnrollment::where('user_id', Auth::id())
            ->whereIn('vocab_list_id', $listIds)
            ->update(['last_studied_at' => now()]);
    }

    public function startAgain(): void
    {
        $this->currentIndex = 0;
        $this->showAnswer = false;
        $this->finished = false;
        $this->results = [];
        $this->loadQueue();
    }

    public function render()
    {
        $current = $this->queue[$this->currentIndex] ?? null;
        $total = count($this->queue);
        $done = count($this->results);

        $correct = collect($this->results)->where('quality', '>=', SrsAlgorithm::QUALITY_HARD)->count();
        $again = collect($this->results)->where('quality', '<', SrsAlgorithm::QUALITY_HARD)->count();

        return view('vocab::livewire.review', [
            'current' => $current,
            'total' => $total,
            'done' => $done,
            'correctCount' => $correct,
            'againCount' => $again,
            'progressPct' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ])->layout('platform::layouts.app');
    }
}
