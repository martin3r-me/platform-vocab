<?php

namespace Platform\Vocab\Livewire\Quiz;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabEntry;
use Platform\Vocab\Prompts\VocabPrompts;

class Play extends Component
{
    public string $uuid;
    public ?VocabList $list = null;

    public string $mode = 'translate';
    public string $direction = 'source_to_target';
    public int $questionCount = 10;

    // Quiz State
    public bool $quizStarted = false;
    public bool $quizFinished = false;
    public array $questions = [];
    public int $currentIndex = 0;
    public string $userAnswer = '';
    public bool $answered = false;
    public ?array $feedback = null;
    public array $results = [];

    public function mount(string $uuid)
    {
        $this->uuid = $uuid;
        $team = Auth::user()->currentTeam;
        $this->list = VocabList::where('team_id', $team->id)->where('uuid', $uuid)->firstOrFail();
    }

    public function startQuiz()
    {
        try {
            $entries = $this->list->entries()->get();
            if ($entries->isEmpty()) {
                $this->addError('quiz', 'Die Liste enthält keine Vokabeln.');
                return;
            }

            $entriesData = $entries->map(fn ($e) => [
                'id' => $e->id,
                'term' => $e->term,
                'translation' => $e->translation,
                'gender' => $e->gender,
                'word_type' => $e->word_type,
                'example_sentence' => $e->example_sentence,
            ])->toArray();

            $count = min($this->questionCount, count($entriesData));

            $prompt = match ($this->mode) {
                'fill_blank' => VocabPrompts::quizFillBlank($entriesData, $count),
                'multiple_choice' => VocabPrompts::quizMultipleChoice($entriesData, $count),
                default => VocabPrompts::quizTranslate($entriesData, $this->direction, $this->list->source_language, $this->list->target_language, $count),
            };

            $openAi = app(\Platform\Core\Services\OpenAiService::class);
            $result = $openAi->chat(
                [['role' => 'user', 'content' => $prompt]],
                'gpt-4o-mini',
                ['tools' => false, 'max_tokens' => 3000, 'temperature' => 0.5]
            );

            $content = $result['content'] ?? '';
            $jsonMatch = [];
            if (preg_match('/\[[\s\S]*\]/', $content, $jsonMatch)) {
                $this->questions = json_decode($jsonMatch[0], true) ?? [];
            } else {
                $this->questions = json_decode($content, true) ?? [];
            }

            if (empty($this->questions)) {
                $this->addError('quiz', 'Konnte kein Quiz generieren. Bitte erneut versuchen.');
                return;
            }

            $this->quizStarted = true;
            $this->currentIndex = 0;
            $this->results = [];
            $this->quizFinished = false;
        } catch (\Throwable $e) {
            $this->addError('quiz', 'Fehler: ' . $e->getMessage());
        }
    }

    public function selectOption(int $index)
    {
        if ($this->answered) return;

        $question = $this->questions[$this->currentIndex] ?? null;
        if (!$question || empty($question['options'][$index])) return;

        $this->userAnswer = $question['options'][$index];
        $this->submitAnswer();
    }

    public function submitAnswer()
    {
        if ($this->answered || empty($this->userAnswer)) return;

        $this->answered = true;

        try {
            $question = $this->questions[$this->currentIndex];
            $entryId = $question['entry_id'] ?? 0;
            $entry = VocabEntry::find($entryId);

            if ($entry) {
                $prompt = VocabPrompts::checkAnswer(
                    $entry->term,
                    $question['correct_answer'] ?? $entry->translation,
                    $this->userAnswer,
                    $this->mode
                );

                $openAi = app(\Platform\Core\Services\OpenAiService::class);
                $result = $openAi->chat(
                    [['role' => 'user', 'content' => $prompt]],
                    'gpt-4o-mini',
                    ['tools' => false, 'max_tokens' => 500, 'temperature' => 0.3]
                );

                $content = $result['content'] ?? '';
                $jsonMatch = [];
                if (preg_match('/\{[\s\S]*\}/', $content, $jsonMatch)) {
                    $this->feedback = json_decode($jsonMatch[0], true);
                }
            }

            if (!$this->feedback) {
                $correct = strtolower(trim($this->userAnswer)) === strtolower(trim($question['correct_answer'] ?? ''));
                $this->feedback = [
                    'correct' => $correct,
                    'expected' => $question['correct_answer'] ?? '',
                    'feedback' => $correct ? 'Richtig!' : 'Die korrekte Antwort wäre: ' . ($question['correct_answer'] ?? ''),
                ];
            }

            $this->results[] = [
                'question' => $question['question'] ?? '',
                'user_answer' => $this->userAnswer,
                'correct' => (bool)($this->feedback['correct'] ?? false),
                'expected' => $this->feedback['expected'] ?? '',
            ];
        } catch (\Throwable $e) {
            $this->feedback = [
                'correct' => false,
                'expected' => $this->questions[$this->currentIndex]['correct_answer'] ?? '',
                'feedback' => 'Fehler bei der Prüfung.',
            ];
            $this->results[] = [
                'question' => $this->questions[$this->currentIndex]['question'] ?? '',
                'user_answer' => $this->userAnswer,
                'correct' => false,
                'expected' => '',
            ];
        }
    }

    public function nextQuestion()
    {
        $this->currentIndex++;
        $this->userAnswer = '';
        $this->answered = false;
        $this->feedback = null;

        if ($this->currentIndex >= count($this->questions)) {
            $this->quizFinished = true;
        }
    }

    public function restartQuiz()
    {
        $this->quizStarted = false;
        $this->quizFinished = false;
        $this->questions = [];
        $this->currentIndex = 0;
        $this->results = [];
        $this->userAnswer = '';
        $this->answered = false;
        $this->feedback = null;
    }

    public function render()
    {
        $correctCount = collect($this->results)->where('correct', true)->count();
        $totalAnswered = count($this->results);

        return view('vocab::livewire.quiz.play', [
            'correctCount' => $correctCount,
            'totalAnswered' => $totalAnswered,
        ])->layout('platform::layouts.app');
    }
}
