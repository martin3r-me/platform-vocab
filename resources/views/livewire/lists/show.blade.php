<div x-data @play-tts.window="
    const audio = new Audio($event.detail.audio);
    audio.play().catch(() => {});
">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
            ['label' => 'Listen', 'href' => route('vocab.lists.index')],
            ['label' => $list->name],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Header --}}
            <div class="relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-6">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/40 to-transparent"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-semibold text-violet-600 dark:text-violet-400 bg-violet-500/10 rounded">
                                {{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}
                            </span>
                            @if($list->level)
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 rounded">
                                {{ $list->level }}
                            </span>
                            @endif
                            <span class="text-xs text-gray-400">{{ $entries->count() }} Vokabeln</span>
                        </div>
                        <h1 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100">{{ $list->name }}</h1>
                        @if($list->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $list->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="$set('showGenerateModal', true)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-white/60 dark:bg-white/5 border border-black/5 dark:border-white/10 rounded-lg hover:bg-white/80 transition-all">
                            @svg('heroicon-o-sparkles', 'w-3.5 h-3.5')
                            KI-Generieren
                        </button>
                        <a href="{{ route('vocab.quiz.play', ['uuid' => $list->uuid]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg shadow-sm hover:shadow-md transition-all">
                            @svg('heroicon-o-academic-cap', 'w-3.5 h-3.5')
                            Quiz
                        </a>
                        <button wire:click="openEditListModal" class="p-1.5 rounded-md text-gray-400 hover:text-violet-500 hover:bg-violet-500/10 transition-colors">
                            @svg('heroicon-o-pencil-square', 'w-4 h-4')
                        </button>
                    </div>
                </div>
            </div>

            {{-- Add Entry Inline --}}
            <form wire:submit="addEntry" class="rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-4">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-400 mb-1">{{ strtoupper($list->target_language) }} (Vokabel)</label>
                        <input type="text" wire:model="newTerm" placeholder="Neues Wort..."
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-400 mb-1">{{ strtoupper($list->source_language) }} (Übersetzung)</label>
                        <input type="text" wire:model="newTranslation" placeholder="Übersetzung..."
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                    </div>
                    <div class="w-20">
                        <label class="block text-xs font-medium text-gray-400 mb-1">Genus</label>
                        <select wire:model="newGender" class="w-full px-2 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all">
                            <option value="">-</option>
                            <option value="m">m</option>
                            <option value="f">f</option>
                            <option value="n">n</option>
                        </select>
                    </div>
                    <div class="w-28">
                        <label class="block text-xs font-medium text-gray-400 mb-1">Wortart</label>
                        <select wire:model="newWordType" class="w-full px-2 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all">
                            <option value="">-</option>
                            <option value="noun">Nomen</option>
                            <option value="verb">Verb</option>
                            <option value="adjective">Adjektiv</option>
                            <option value="adverb">Adverb</option>
                            <option value="phrase">Phrase</option>
                        </select>
                    </div>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-indigo-500 rounded-lg shadow-sm shadow-violet-500/25 hover:shadow-md transition-all">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                    </button>
                </div>
            </form>

            {{-- Entries Table --}}
            <div class="rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 overflow-hidden">
                @if($entries->isEmpty())
                    <div class="text-center py-12">
                        <div class="text-sm text-gray-400">Noch keine Vokabeln. Füge welche hinzu oder nutze die KI-Generierung.</div>
                    </div>
                @else
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-black/5 dark:border-white/5">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">{{ strtoupper($list->target_language) }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">{{ strtoupper($list->source_language) }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400 w-16">Genus</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Wortart</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-400">Beispiel</th>
                                <th class="px-4 py-3 w-20"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5">
                            @foreach($entries as $entry)
                            <tr class="hover:bg-black/[0.02] dark:hover:bg-white/[0.02] transition-colors group">
                                @if($editingEntryId === $entry->id)
                                    {{-- Edit Mode --}}
                                    <td class="px-4 py-2">
                                        <input type="text" wire:model="editTerm" class="w-full px-2 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" wire:model="editTranslation" class="w-full px-2 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20" />
                                    </td>
                                    <td class="px-4 py-2">
                                        <select wire:model="editGender" class="w-full px-1 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20">
                                            <option value="">-</option>
                                            <option value="m">m</option>
                                            <option value="f">f</option>
                                            <option value="n">n</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <select wire:model="editWordType" class="w-full px-1 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20">
                                            <option value="">-</option>
                                            <option value="noun">Nomen</option>
                                            <option value="verb">Verb</option>
                                            <option value="adjective">Adjektiv</option>
                                            <option value="adverb">Adverb</option>
                                            <option value="phrase">Phrase</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" wire:model="editExampleSentence" class="w-full px-2 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20" placeholder="Beispielsatz..." />
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex gap-1">
                                            <button wire:click="saveEntry" class="p-1 rounded text-emerald-500 hover:bg-emerald-500/10 transition-colors">
                                                @svg('heroicon-o-check', 'w-4 h-4')
                                            </button>
                                            <button wire:click="cancelEditing" class="p-1 rounded text-gray-400 hover:bg-gray-500/10 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    </td>
                                @else
                                    {{-- View Mode --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <button wire:click="playTts({{ $entry->id }})" wire:loading.attr="disabled" wire:target="playTts({{ $entry->id }})" class="flex-shrink-0 p-1 rounded-md text-gray-300 hover:text-violet-500 hover:bg-violet-500/10 transition-colors" title="Aussprache anhören">
                                                <span wire:loading wire:target="playTts({{ $entry->id }})">
                                                    <svg class="animate-spin w-3.5 h-3.5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                                <span wire:loading.remove wire:target="playTts({{ $entry->id }})">
                                                    @svg('heroicon-o-speaker-wave', 'w-3.5 h-3.5')
                                                </span>
                                            </button>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $entry->term }}</span>
                                            @if($entry->plural)
                                            <span class="text-xs text-gray-400">(Pl: {{ $entry->plural }})</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $entry->translation }}</td>
                                    <td class="px-4 py-3">
                                        @if($entry->gender)
                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded
                                            {{ $entry->gender === 'm' ? 'text-blue-600 bg-blue-500/10' : '' }}
                                            {{ $entry->gender === 'f' ? 'text-pink-600 bg-pink-500/10' : '' }}
                                            {{ $entry->gender === 'n' ? 'text-gray-600 bg-gray-500/10' : '' }}
                                        ">{{ $entry->gender }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $entry->word_type }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-400 truncate max-w-xs" title="{{ $entry->example_sentence }}">{{ $entry->example_sentence }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button wire:click="startEditing({{ $entry->id }})" class="p-1 rounded text-gray-400 hover:text-violet-500 hover:bg-violet-500/10 transition-colors">
                                                @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                            </button>
                                            <button wire:click="deleteEntry({{ $entry->id }})" wire:confirm="Vokabel '{{ $entry->term }}' löschen?" class="p-1 rounded text-gray-400 hover:text-red-500 hover:bg-red-500/10 transition-colors">
                                                @svg('heroicon-o-trash', 'w-3.5 h-3.5')
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </x-ui-page-container>

    {{-- Edit List Modal --}}
    @if($showEditListModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showEditListModal', false)">
        <div class="w-full max-w-lg mx-4 rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-black/10 dark:border-white/10">
            <div class="px-6 py-4 border-b border-black/5 dark:border-white/5">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Liste bearbeiten</h2>
            </div>
            <form wire:submit="saveListDetails" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                    <input type="text" wire:model="editListName"
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Beschreibung</label>
                    <textarea wire:model="editListDescription" rows="2"
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Niveau</label>
                    <select wire:model="editListLevel" class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all">
                        <option value="">Kein Niveau</option>
                        @foreach(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $l)
                            <option value="{{ $l }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showEditListModal', false)" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white/60 border border-black/5 rounded-lg hover:bg-white/80 transition-all">
                        Abbrechen
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-indigo-500 rounded-lg shadow-sm hover:shadow-md transition-all">
                        Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Generate More Modal --}}
    @if($showGenerateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('showGenerateModal', false)">
        <div class="w-full max-w-lg mx-4 rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-black/10 dark:border-white/10">
            <div class="px-6 py-4 border-b border-black/5 dark:border-white/5">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Weitere Vokabeln generieren</h2>
                <p class="text-xs text-gray-400 mt-1">Die KI generiert Vokabeln und fügt sie zu "{{ $list->name }}" hinzu</p>
            </div>
            <form wire:submit="generateMore" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Thema</label>
                    <input type="text" wire:model="generateTopic" placeholder="z.B. Im Restaurant, Einkaufen..."
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                    @error('generateTopic') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Anzahl</label>
                    <input type="number" wire:model="generateCount" min="5" max="50"
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                </div>
                @error('generate') <div class="text-xs text-red-500">{{ $message }}</div> @enderror
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showGenerateModal', false)" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white/60 border border-black/5 rounded-lg hover:bg-white/80 transition-all" @if($generating) disabled @endif>
                        Abbrechen
                    </button>
                    <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-violet-500 to-indigo-500 rounded-lg shadow-sm hover:shadow-md transition-all" @if($generating) disabled @endif>
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

    {{-- Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Details" width="w-80" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Listeninfo</h3>
                    <div class="space-y-2">
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Sprachen</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Vokabeln</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $entries->count() }}</span>
                            </div>
                        </div>
                        @if($list->level)
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">Niveau</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $list->level }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
</div>
