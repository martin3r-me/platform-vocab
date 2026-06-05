<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
        ]">
            <button @click="Alpine?.store('page') && (Alpine.store('page')['activityOpen'] = !Alpine.store('page')['activityOpen'])"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                @svg('heroicon-o-chart-bar', 'w-4 h-4')
                <span class="hidden sm:inline">Statistik</span>
            </button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Hero Section --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500/10 via-indigo-500/5 to-transparent dark:from-violet-500/20 dark:via-indigo-500/10 dark:to-transparent border border-white/20 dark:border-white/10 shadow-sm shadow-black/5 p-5">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/60 to-transparent"></div>
                <div class="relative flex items-center gap-4">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-violet-500/10">
                        @svg('heroicon-o-language', 'w-5 h-5 text-violet-500')
                    </div>
                    <div>
                        <h1 class="text-lg font-medium tracking-tight text-gray-900 dark:text-gray-100">
                            Vokabeln
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Vokabellisten verwalten, mit KI generieren und interaktiv abfragen.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Stat Cards --}}
            <x-ui-stats-grid :cols="3">
                <x-ui-dashboard-tile
                    title="Listen"
                    :count="$listsCount"
                    icon="list-bullet"
                    variant="primary"
                    description="Vokabellisten"
                    :href="route('vocab.lists.index')"
                />
                <x-ui-dashboard-tile
                    title="Vokabeln"
                    :count="$entriesCount"
                    icon="book-open"
                    variant="success"
                    description="Gesamt"
                />
                <x-ui-dashboard-tile
                    title="Sprachen"
                    :count="$languagesCount"
                    icon="globe-alt"
                    variant="info"
                    description="Zielsprachen"
                />
            </x-ui-stats-grid>

            {{-- Content Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Recent Lists --}}
                <div class="lg:col-span-2">
                    <x-ui-panel title="Letzte Listen">
                        <div class="p-5">
                            @if($recentLists->isEmpty())
                                <x-ui-info-banner icon="book-open" title="Keine Listen vorhanden" variant="neutral">
                                    Erstelle deine erste Vokabelliste oder lass sie von der KI generieren.
                                    <x-slot name="actions">
                                        <x-ui-button variant="primary" :href="route('vocab.lists.index')">
                                            @svg('heroicon-o-plus', 'w-4 h-4')
                                            Erste Liste erstellen
                                        </x-ui-button>
                                    </x-slot>
                                </x-ui-info-banner>
                            @else
                                <div class="space-y-3">
                                    @foreach($recentLists as $list)
                                    <a href="{{ route('vocab.lists.show', ['uuid' => $list->uuid]) }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.02] hover:bg-black/[0.04] dark:hover:bg-white/[0.04] transition-colors duration-150 group">
                                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-violet-500/20 to-indigo-500/20 flex items-center justify-center">
                                            <span class="text-xs font-bold text-violet-500 uppercase">{{ strtoupper($list->target_language) }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ $list->name }}</div>
                                            <div class="text-xs text-gray-400">{{ $list->entries_count }} Vokabeln &middot; {{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}{{ $list->level ? ' · ' . $list->level : '' }}</div>
                                        </div>
                                        @svg('heroicon-o-chevron-right', 'w-4 h-4 text-gray-300 dark:text-gray-600 ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-150')
                                    </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </x-ui-panel>
                </div>

                {{-- Quick Actions --}}
                <x-ui-panel title="Schnellzugriff">
                    <div class="p-4 space-y-2">
                        <a href="{{ route('vocab.lists.index') }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg hover:bg-black/[0.03] dark:hover:bg-white/[0.03] transition-colors duration-150 group">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-violet-500/10 to-indigo-500/10 group-hover:from-violet-500/20 group-hover:to-indigo-500/20 transition-colors duration-150">
                                @svg('heroicon-o-list-bullet', 'w-4.5 h-4.5 text-violet-500')
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Alle Listen</div>
                                <div class="text-xs text-gray-400">Listen verwalten & erstellen</div>
                            </div>
                            @svg('heroicon-o-chevron-right', 'w-4 h-4 text-gray-300 dark:text-gray-600 ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-150')
                        </a>
                        @if($recentLists->isNotEmpty())
                        <a href="{{ route('vocab.quiz.play', ['uuid' => $recentLists->first()->uuid]) }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg hover:bg-black/[0.03] dark:hover:bg-white/[0.03] transition-colors duration-150 group">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-emerald-500/10 to-teal-500/10 group-hover:from-emerald-500/20 group-hover:to-teal-500/20 transition-colors duration-150">
                                @svg('heroicon-o-academic-cap', 'w-4.5 h-4.5 text-emerald-500')
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Quiz starten</div>
                                <div class="text-xs text-gray-400">{{ $recentLists->first()->name }}</div>
                            </div>
                            @svg('heroicon-o-chevron-right', 'w-4 h-4 text-gray-300 dark:text-gray-600 ml-auto opacity-0 group-hover:opacity-100 transition-opacity duration-150')
                        </a>
                        @endif
                    </div>
                </x-ui-panel>
            </div>

        </div>
    </x-ui-page-container>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                {{-- Overview Stats --}}
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Übersicht</h3>
                    <div class="space-y-2">
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Listen</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $listsCount }}</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Vokabeln</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $entriesCount }}</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Sprachen</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $languagesCount }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Language Distribution --}}
                @if($languageStats->isNotEmpty())
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Sprach-Verteilung</h3>
                    <div class="space-y-2">
                        @foreach($languageStats as $stat)
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $stat['language'] }}</span>
                                <x-ui-badge variant="primary" size="xs">{{ $stat['count'] }} {{ $stat['count'] === 1 ? 'Liste' : 'Listen' }}</x-ui-badge>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Level Distribution --}}
                @if($levelStats->isNotEmpty())
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Level-Verteilung</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($levelStats as $stat)
                            <x-ui-badge variant="success" size="sm">{{ $stat['level'] }}: {{ $stat['count'] }}</x-ui-badge>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
