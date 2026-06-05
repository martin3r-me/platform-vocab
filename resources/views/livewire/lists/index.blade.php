<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
            ['label' => 'Listen', 'href' => route('vocab.lists.index')],
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

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100">Vokabellisten</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Verwalte deine Vokabellisten</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui-button variant="secondary-outline" wire:click="$set('showGenerateModal', true)">
                        @svg('heroicon-o-sparkles', 'w-4 h-4')
                        KI-Generierung
                    </x-ui-button>
                    <x-ui-button variant="primary" wire:click="$set('showCreateModal', true)">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Neue Liste
                    </x-ui-button>
                </div>
            </div>

            {{-- Filters --}}
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Listen durchsuchen..."
                        class="w-full px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 placeholder-gray-400 focus:ring-2 focus:ring-violet-500/20 focus:bg-white dark:focus:bg-white/10 transition-all duration-150" />
                </div>
                <select wire:model.live="filterLanguage" class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20">
                    <option value="">Alle Sprachen</option>
                    @foreach($availableLanguages as $lang)
                        <option value="{{ $lang }}">{{ \Platform\Vocab\Livewire\Sidebar::languageName($lang) }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterLevel" class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20">
                    <option value="">Alle Level</option>
                    @foreach(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level)
                        <option value="{{ $level }}">{{ $level }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Lists grouped by language --}}
            @if($groupedLists->isEmpty())
                <x-ui-info-banner icon="book-open" title="Keine Listen vorhanden" variant="neutral">
                    Erstelle deine erste Vokabelliste oder lass sie von der KI generieren.
                </x-ui-info-banner>
            @else
                <div class="space-y-6" x-data="{ collapsed: {} }">
                    @foreach($groupedLists as $languageName => $lists)
                        <div>
                            {{-- Section Header --}}
                            <button
                                @click="collapsed['{{ $languageName }}'] = !collapsed['{{ $languageName }}']"
                                class="flex items-center gap-2 mb-3 group cursor-pointer"
                            >
                                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $languageName }}</h2>
                                <x-ui-badge variant="muted" size="xs">{{ $lists->count() }}</x-ui-badge>
                                <span class="transition-transform duration-200" :class="collapsed['{{ $languageName }}'] ? '-rotate-90' : ''">
                                    @svg('heroicon-o-chevron-down', 'w-3.5 h-3.5 text-gray-400')
                                </span>
                            </button>

                            {{-- List Grid --}}
                            <div x-show="!collapsed['{{ $languageName }}']" x-collapse>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($lists as $list)
                                    <div class="group relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
                                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/30 to-transparent"></div>
                                        <a href="{{ route('vocab.lists.show', ['uuid' => $list->uuid]) }}" wire:navigate class="block p-5">
                                            <div class="flex items-start justify-between mb-3">
                                                <div class="flex items-center gap-2">
                                                    <x-ui-badge variant="primary" size="sm">
                                                        {{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}
                                                    </x-ui-badge>
                                                    @if($list->level)
                                                        <x-ui-badge variant="success" size="sm">{{ $list->level }}</x-ui-badge>
                                                    @endif
                                                </div>
                                                @if($list->is_enrolled)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                                                        @svg('heroicon-s-bookmark', 'w-3 h-3')
                                                        {{ $list->mastery_pct }}%
                                                    </span>
                                                @endif
                                            </div>
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1 truncate">{{ $list->name }}</h3>
                                            @if($list->description)
                                            <p class="text-xs text-gray-400 mb-3 line-clamp-2">{{ $list->description }}</p>
                                            @endif
                                            @if($list->is_enrolled && $list->entries_count > 0)
                                                <div class="mt-3 h-1 rounded-full bg-black/[0.06] dark:bg-white/10 overflow-hidden">
                                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all" style="width: {{ $list->mastery_pct }}%"></div>
                                                </div>
                                            @endif
                                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-black/5 dark:border-white/5">
                                                <span class="text-xs text-gray-400">{{ $list->entries_count }} Vokabeln</span>
                                                <span class="text-xs text-gray-400">{{ $list->updated_at->diffForHumans() }}</span>
                                            </div>
                                        </a>
                                        <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex gap-1">
                                            <a href="{{ route('vocab.quiz.play', ['uuid' => $list->uuid]) }}" wire:navigate class="p-1.5 rounded-md bg-white/80 dark:bg-black/40 hover:bg-emerald-500/10 text-gray-400 hover:text-emerald-500 transition-colors" title="Quiz starten">
                                                @svg('heroicon-o-academic-cap', 'w-4 h-4')
                                            </a>
                                            <x-ui-confirm-button action="deleteList" :value="$list->id" text="" confirmText="Löschen?" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>' size="sm" class="p-1.5 rounded-md bg-white/80 dark:bg-black/40 text-gray-400" />
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </x-ui-page-container>

    {{-- Create Modal --}}
    <x-ui-modal wire:model="showCreateModal">
        <x-slot name="header">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Neue Vokabelliste</h2>
        </x-slot>

        <form wire:submit="createList" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                <input type="text" wire:model="newName" placeholder="z.B. Italienisch Essen & Trinken"
                    class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                @error('newName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Beschreibung</label>
                <textarea wire:model="newDescription" rows="2" placeholder="Optional..."
                    class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Ausgangssprache</label>
                    <input type="text" wire:model="newSourceLanguage" placeholder="de"
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Zielsprache</label>
                    <input type="text" wire:model="newTargetLanguage" placeholder="it, en, fr, es..."
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                    @error('newTargetLanguage') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Niveau</label>
                <select wire:model="newLevel" class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all">
                    <option value="">Kein Niveau</option>
                    @foreach(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $l)
                        <option value="{{ $l }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary-outline" wire:click="$set('showCreateModal', false)">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="createList">
                    Erstellen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Generate Modal --}}
    <x-ui-modal wire:model="showGenerateModal">
        <x-slot name="header">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">KI-Vokabeln generieren</h2>
                <p class="text-xs text-gray-400 mt-1">Die KI erstellt eine Vokabelliste zu deinem Thema</p>
            </div>
        </x-slot>

        <form wire:submit="generateList" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Thema</label>
                <input type="text" wire:model="generateTopic" placeholder="z.B. Essen & Trinken, Reisen, Im Restaurant..."
                    class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                @error('generateTopic') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Ausgangssprache</label>
                    <input type="text" wire:model="generateSourceLanguage" placeholder="de"
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Zielsprache</label>
                    <input type="text" wire:model="generateTargetLanguage" placeholder="it, en, fr..."
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                    @error('generateTargetLanguage') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Niveau</label>
                    <select wire:model="generateLevel" class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all">
                        @foreach(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $l)
                            <option value="{{ $l }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Anzahl</label>
                    <input type="number" wire:model="generateCount" min="5" max="50"
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                </div>
            </div>
            @error('generate') <div class="text-xs text-red-500">{{ $message }}</div> @enderror
        </form>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary-outline" wire:click="$set('showGenerateModal', false)" :disabled="$generating">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="generateList" :disabled="$generating">
                    @if($generating)
                        <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Generiere...
                    @else
                        @svg('heroicon-o-sparkles', 'w-4 h-4')
                        Generieren
                    @endif
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Activity Sidebar --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Statistik" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Listen pro Sprache</h3>
                    <div class="space-y-2">
                        @foreach($languageStats as $stat)
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ $stat['name'] }}</span>
                                <x-ui-badge variant="primary" size="xs">{{ $stat['count'] }}</x-ui-badge>
                            </div>
                        </div>
                        @endforeach
                        @if($languageStats->isEmpty())
                            <div class="text-xs text-gray-400">Noch keine Listen vorhanden.</div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

</x-ui-page>
