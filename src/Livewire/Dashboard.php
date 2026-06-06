<?php

namespace Platform\Vocab\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Vocab\Models\VocabEntry;
use Platform\Vocab\Models\VocabList;
use Platform\Vocab\Models\VocabUserSettings;
use Platform\Vocab\Services\VocabStatsService;

class Dashboard extends Component
{
    public bool $showSettingsModal = false;
    public int $settingsDailyGoal = 10;
    public bool $settingsAutoPlayTts = true;
    public bool $settingsKeyboardShortcuts = true;

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
            'meta' => ['view_type' => 'dashboard'],
        ]);
    }

    public function openSettingsModal(): void
    {
        $user = Auth::user();
        $settings = VocabUserSettings::forUser($user->id, $user->currentTeam->id);
        $this->settingsDailyGoal = $settings->daily_goal;
        $this->settingsAutoPlayTts = $settings->auto_play_tts;
        $this->settingsKeyboardShortcuts = $settings->keyboard_shortcuts;
        $this->showSettingsModal = true;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'settingsDailyGoal' => 'required|integer|min:1|max:200',
        ]);

        $user = Auth::user();
        $settings = VocabUserSettings::forUser($user->id, $user->currentTeam->id);
        $settings->update([
            'daily_goal' => $this->settingsDailyGoal,
            'auto_play_tts' => $this->settingsAutoPlayTts,
            'keyboard_shortcuts' => $this->settingsKeyboardShortcuts,
        ]);

        $this->showSettingsModal = false;
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $stats = app(VocabStatsService::class);

        $settings = VocabUserSettings::forUser($user->id, $team->id);

        $streak = $stats->streak($user->id);
        $today = $stats->todayProgress($user->id);
        $mastery = $stats->masteryTotals($user->id);
        $heatmap = $stats->heatmap($user->id, 90);
        $milestone = $stats->nextMilestone($mastery['mastered']);
        $dueCount = $stats->dueCount($user->id);
        $totalReviews = $stats->totalReviews($user->id);

        $todayPct = $settings->daily_goal > 0
            ? min(100, (int) round($today['reviewed_today'] / $settings->daily_goal * 100))
            : 0;

        // Enrolled lists with mastery
        $enrolledLists = VocabList::query()
            ->whereHas('enrollments', fn ($q) => $q->where('user_id', $user->id))
            ->withCount('entries')
            ->limit(5)
            ->get()
            ->map(function (VocabList $list) use ($user) {
                $list->setAttribute('mastery_pct', $list->masteryFor($user->id)['pct']);
                return $list;
            });

        // Team-wide stats (sidebar)
        $listsCount = VocabList::where('team_id', $team->id)->count();
        $entriesCount = VocabEntry::whereHas('vocabList', fn ($q) => $q->where('team_id', $team->id))->count();

        $languages = VocabList::where('team_id', $team->id)
            ->selectRaw('DISTINCT target_language')
            ->pluck('target_language')
            ->filter();

        $languageStats = VocabList::where('team_id', $team->id)
            ->selectRaw('target_language, COUNT(*) as list_count')
            ->groupBy('target_language')
            ->get()
            ->map(fn ($row) => [
                'language' => Sidebar::languageName($row->target_language),
                'code' => strtoupper($row->target_language),
                'count' => $row->list_count,
            ]);

        return view('vocab::livewire.dashboard', [
            'streak' => $streak,
            'today' => $today,
            'todayPct' => $todayPct,
            'mastery' => $mastery,
            'heatmap' => collect($heatmap),
            'milestone' => $milestone,
            'dueCount' => $dueCount,
            'totalReviews' => $totalReviews,
            'enrolledLists' => $enrolledLists,
            'settings' => $settings,
            'listsCount' => $listsCount,
            'entriesCount' => $entriesCount,
            'languagesCount' => $languages->count(),
            'languageStats' => $languageStats,
        ])->layout('platform::layouts.app');
    }
}
