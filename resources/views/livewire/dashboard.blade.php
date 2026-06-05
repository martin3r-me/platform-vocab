<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-8">

            {{-- Hero Section --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500/10 via-indigo-500/5 to-transparent dark:from-violet-500/20 dark:via-indigo-500/10 dark:to-transparent border border-white/20 dark:border-white/10 shadow-sm shadow-black/5 p-8">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/60 to-transparent"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-violet-500/10 rounded-full blur-3xl"></div>
                <div class="relative">
                    <div class="inline-flex items-center gap-2 px-3 py-1 text-xs font-medium text-violet-600 dark:text-violet-400 bg-violet-500/10 rounded-full mb-4">
                        @svg('heroicon-o-language', 'w-3.5 h-3.5')
                        <span>Vokabel-Trainer</span>
                    </div>
                    <h1 class="text-2xl font-medium tracking-tight text-gray-900 dark:text-gray-100 mb-2">
                        Vokabeln
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-lg">
                        Vokabellisten verwalten, mit KI generieren und interaktiv abfragen.
                    </p>
                </div>
            </div>

            {{-- Stat Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="group relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/50 to-transparent"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Listen</span>
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-violet-500/10">
                            @svg('heroicon-o-list-bullet', 'w-4 h-4 text-violet-500')
                        </div>
                    </div>
                    <div class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ $listsCount }}</div>
                    <div class="text-xs text-gray-400 mt-1">Vokabellisten</div>
                </div>

                <div class="group relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-500/50 to-transparent"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Vokabeln</span>
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-500/10">
                            @svg('heroicon-o-book-open', 'w-4 h-4 text-emerald-500')
                        </div>
                    </div>
                    <div class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ $entriesCount }}</div>
                    <div class="text-xs text-gray-400 mt-1">Gesamt</div>
                </div>

                <div class="group relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-sky-500/50 to-transparent"></div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-400">Sprachen</span>
                        <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-sky-500/10">
                            @svg('heroicon-o-globe-alt', 'w-4 h-4 text-sky-500')
                        </div>
                    </div>
                    <div class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">{{ $languagesCount }}</div>
                    <div class="text-xs text-gray-400 mt-1">Zielsprachen</div>
                </div>
            </div>

            {{-- Content Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Recent Lists --}}
                <div class="lg:col-span-2 relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/30 to-transparent"></div>
                    <div class="px-5 py-4 border-b border-black/5 dark:border-white/5">
                        <h2 class="text-sm font-medium tracking-tight text-gray-900 dark:text-gray-100">Letzte Listen</h2>
                    </div>
                    <div class="p-5">
                        @if($recentLists->isEmpty())
                            <div class="text-center py-8">
                                <div class="text-sm text-gray-400">Noch keine Listen vorhanden.</div>
                                <a href="{{ route('vocab.lists.index') }}" wire:navigate class="inline-flex items-center gap-2 mt-3 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-indigo-500 rounded-lg shadow-sm shadow-violet-500/25 hover:shadow-md transition-all duration-150">
                                    @svg('heroicon-o-plus', 'w-4 h-4')
                                    Erste Liste erstellen
                                </a>
                            </div>
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
                </div>

                {{-- Quick Actions --}}
                <div class="relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent"></div>
                    <div class="px-5 py-4 border-b border-black/5 dark:border-white/5">
                        <h2 class="text-sm font-medium tracking-tight text-gray-900 dark:text-gray-100">Schnellzugriff</h2>
                    </div>
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
                </div>
            </div>

        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Statistiken" width="w-80" :defaultOpen="true">
            <div class="p-5 space-y-5">
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
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
