<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
            ['label' => 'Listen', 'href' => route('vocab.lists.index')],
        ]" />
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
                    <button wire:click="$set('showGenerateModal', true)" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white/60 dark:bg-white/5 backdrop-blur-sm border border-black/5 dark:border-white/10 rounded-lg hover:bg-white/80 dark:hover:bg-white/10 transition-all duration-150">
                        @svg('heroicon-o-sparkles', 'w-4 h-4')
                        KI-Generierung
                    </button>
                    <button wire:click="$set('showCreateModal', true)" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-indigo-500 rounded-lg shadow-sm shadow-violet-500/25 hover:shadow-md hover:shadow-violet-500/30 hover:-translate-y-0.5 transition-all duration-150">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Neue Liste
                    </button>
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
                        <option value="{{ $lang }}">{{ strtoupper($lang) }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterLevel" class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100 bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20">
                    <option value="">Alle Level</option>
                    @foreach(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level)
                        <option value="{{ $level }}">{{ $level }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Lists --}}
            @if($lists->isEmpty())
                <div class="text-center py-16">
                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-violet-500/10 mx-auto mb-4">
                        @svg('heroicon-o-book-open', 'w-8 h-8 text-violet-500')
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Keine Listen vorhanden</h3>
                    <p class="text-sm text-gray-400 mb-4">Erstelle deine erste Vokabelliste oder lass sie von der KI generieren.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($lists as $list)
                    <div class="group relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/30 to-transparent"></div>
                        <a href="{{ route('vocab.lists.show', ['uuid' => $list->uuid]) }}" wire:navigate class="block p-5">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold text-violet-600 dark:text-violet-400 bg-violet-500/10 rounded">
                                        {{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}
                                    </span>
                                    @if($list->level)
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 rounded">
                                        {{ $list->level }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1 truncate">{{ $list->name }}</h3>
                            @if($list->description)
                            <p class="text-xs text-gray-400 mb-3 line-clamp-2">{{ $list->description }}</p>
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
                            <button wire:click="deleteList({{ $list->id }})" wire:confirm="Liste '{{ $list->name }}' und alle Vokabeln wirklich löschen?" class="p-1.5 rounded-md bg-white/80 dark:bg-black/40 hover:bg-red-500/10 text-gray-400 hover:text-red-500 transition-colors" title="Löschen">
                                @svg('heroicon-o-trash', 'w-4 h-4')
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $lists->links() }}
                </div>
            @endif

        </div>
    </x-ui-page-container>

    {{-- Create Modal --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showCreateModal', false)">
        <div class="w-full max-w-lg mx-4 rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-black/10 dark:border-white/10">
            <div class="px-6 py-4 border-b border-black/5 dark:border-white/5">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Neue Vokabelliste</h2>
            </div>
            <form wire:submit="createList" class="p-6 space-y-4">
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
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white/60 dark:bg-white/5 border border-black/5 dark:border-white/10 rounded-lg hover:bg-white/80 transition-all">
                        Abbrechen
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-indigo-500 rounded-lg shadow-sm shadow-violet-500/25 hover:shadow-md transition-all">
                        Erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Generate Modal --}}
    @if($showGenerateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showGenerateModal', false)">
        <div class="w-full max-w-lg mx-4 rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-black/10 dark:border-white/10">
            <div class="px-6 py-4 border-b border-black/5 dark:border-white/5">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">KI-Vokabeln generieren</h2>
                <p class="text-xs text-gray-400 mt-1">Die KI erstellt eine Vokabelliste zu deinem Thema</p>
            </div>
            <form wire:submit="generateList" class="p-6 space-y-4">
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
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showGenerateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white/60 dark:bg-white/5 border border-black/5 dark:border-white/10 rounded-lg hover:bg-white/80 transition-all" @if($generating) disabled @endif>
                        Abbrechen
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-indigo-500 rounded-lg shadow-sm shadow-violet-500/25 hover:shadow-md transition-all" @if($generating) disabled @endif>
                        @if($generating)
                            <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Generiere...
                        @else
                            @svg('heroicon-o-sparkles', 'w-4 h-4')
                            Generieren
                        @endif
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</x-ui-page>
