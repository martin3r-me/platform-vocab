<?php

namespace Platform\Vocab\Livewire;

use Livewire\Component;
use Platform\Vocab\Models\VocabList;

class Sidebar extends Component
{
    protected static array $languageNames = [
        'de' => 'Deutsch',
        'en' => 'Englisch',
        'it' => 'Italienisch',
        'fr' => 'Französisch',
        'es' => 'Spanisch',
        'pt' => 'Portugiesisch',
        'nl' => 'Niederländisch',
        'pl' => 'Polnisch',
        'ru' => 'Russisch',
        'ja' => 'Japanisch',
        'zh' => 'Chinesisch',
        'ko' => 'Koreanisch',
        'ar' => 'Arabisch',
        'tr' => 'Türkisch',
        'sv' => 'Schwedisch',
        'da' => 'Dänisch',
        'no' => 'Norwegisch',
        'fi' => 'Finnisch',
        'el' => 'Griechisch',
        'cs' => 'Tschechisch',
        'hr' => 'Kroatisch',
        'ro' => 'Rumänisch',
        'hu' => 'Ungarisch',
        'la' => 'Latein',
    ];

    public static function languageName(string $code): string
    {
        return static::$languageNames[strtolower($code)] ?? strtoupper($code);
    }

    public function render()
    {
        $user = auth()->user();

        if (!$user) {
            return view('vocab::livewire.sidebar', [
                'groupedLists' => collect(),
            ]);
        }

        $recentLists = VocabList::where('team_id', $user->currentTeam->id)
            ->withCount('entries')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        $groupedLists = $recentLists->groupBy(function ($list) {
            return strtoupper($list->source_language) . ' → ' . strtoupper($list->target_language);
        });

        return view('vocab::livewire.sidebar', [
            'groupedLists' => $groupedLists,
        ]);
    }
}
