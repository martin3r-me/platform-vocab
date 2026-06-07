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
            <div class="relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-6">
                <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/40 to-transparent"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <x-ui-badge variant="primary" size="sm">
                                {{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}
                            </x-ui-badge>
                            @if($list->level)
                                <x-ui-badge variant="success" size="sm">{{ $list->level }}</x-ui-badge>
                            @endif
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entries->count() }} Vokabeln</span>
                        </div>
                        <h1 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100">{{ $list->name }}</h1>
                        @if($list->description)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $list->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @php $missingExamples = $entries->filter(fn($e) => empty($e->example_sentence))->count(); @endphp
                        @if($missingExamples > 0)
                        <x-ui-button variant="warning-outline" size="sm" wire:click="generateExamples" wire:loading.attr="disabled" wire:target="generateExamples">
                            <span wire:loading wire:target="generateExamples">
                                <svg class="animate-spin w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            </span>
                            <span wire:loading.remove wire:target="generateExamples">
                                @svg('heroicon-o-chat-bubble-bottom-center-text', 'w-3.5 h-3.5')
                            </span>
                            {{ $missingExamples }}x Beispiele
                        </x-ui-button>
                        @endif
                        <x-ui-button variant="secondary-outline" size="sm" wire:click="$set('showGenerateModal', true)">
                            @svg('heroicon-o-sparkles', 'w-3.5 h-3.5')
                            KI-Generieren
                        </x-ui-button>
                        @if($enrollment)
                            <x-ui-button variant="secondary-outline" size="sm" wire:click="unenroll" wire:confirm="Lerne diese Liste nicht mehr aktiv?">
                                @svg('heroicon-o-check-circle', 'w-3.5 h-3.5 text-emerald-500')
                                Aktiv
                            </x-ui-button>
                        @else
                            <x-ui-button variant="primary-outline" size="sm" wire:click="enroll">
                                @svg('heroicon-o-bookmark', 'w-3.5 h-3.5')
                                Lernen
                            </x-ui-button>
                        @endif
                        <x-ui-button variant="success" size="sm" :href="route('vocab.quiz.play', ['uuid' => $list->uuid])">
                            @svg('heroicon-o-academic-cap', 'w-3.5 h-3.5')
                            Quiz
                        </x-ui-button>
                        <button wire:click="openEditListModal" class="p-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:text-violet-500 hover:bg-violet-500/10 transition-colors">
                            @svg('heroicon-o-pencil-square', 'w-4 h-4')
                        </button>
                    </div>
                </div>

                @if($enrollment && $mastery['total'] > 0)
                    <div class="mt-5 pt-4 border-t border-black/5 dark:border-white/10">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Lernfortschritt</span>
                                <span class="text-gray-500 dark:text-gray-400">·</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $mastery['mastered'] }} von {{ $mastery['total'] }} sitzen</span>
                                @if($enrollment->last_studied_at)
                                    <span class="text-gray-500 dark:text-gray-400">·</span>
                                    <span class="text-gray-500 dark:text-gray-400">zuletzt {{ $enrollment->last_studied_at->diffForHumans() }}</span>
                                @endif
                            </div>
                            <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $mastery['pct'] }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-black/[0.06] dark:bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all" style="width: {{ $mastery['pct'] }}%"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Add Entry Inline --}}
            <form wire:submit="addEntry" class="rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-4">
                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ strtoupper($list->target_language) }} (Vokabel)</label>
                        <input type="text" wire:model="newTerm" placeholder="Neues Wort..."
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ strtoupper($list->source_language) }} (Übersetzung)</label>
                        <input type="text" wire:model="newTranslation" placeholder="Übersetzung..."
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all" />
                    </div>
                    <div class="w-20">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Genus</label>
                        <select wire:model="newGender" class="w-full px-2 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all">
                            <option value="">-</option>
                            <option value="m">m</option>
                            <option value="f">f</option>
                            <option value="n">n</option>
                        </select>
                    </div>
                    <div class="w-28">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Wortart</label>
                        <select wire:model="newWordType" class="w-full px-2 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 transition-all">
                            <option value="">-</option>
                            <option value="noun">Nomen</option>
                            <option value="verb">Verb</option>
                            <option value="adjective">Adjektiv</option>
                            <option value="adverb">Adverb</option>
                            <option value="phrase">Phrase</option>
                        </select>
                    </div>
                    <x-ui-button variant="primary" type="submit">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                    </x-ui-button>
                </div>
            </form>

            {{-- Entries Table --}}
            <div class="rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 overflow-hidden">
                @if($entries->isEmpty())
                    <div class="text-center py-12">
                        <x-ui-info-banner icon="book-open" title="Noch keine Vokabeln" variant="neutral">
                            Füge welche hinzu oder nutze die KI-Generierung.
                        </x-ui-info-banner>
                    </div>
                @else
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-black/5 dark:border-white/5">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ strtoupper($list->target_language) }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ strtoupper($list->source_language) }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 w-16">Typ</th>
                                <th class="px-4 py-3 w-20"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5">
                            @foreach($entries as $entry)
                            <tr class="hover:bg-black/[0.02] dark:hover:bg-white/[0.02] transition-colors group">
                                @if($editingEntryId === $entry->id)
                                    {{-- Edit Mode --}}
                                    <td class="px-4 py-2" colspan="2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" wire:model="editTerm" class="w-full px-2 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20" placeholder="Term..." />
                                            <input type="text" wire:model="editTranslation" class="w-full px-2 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20" placeholder="Übersetzung..." />
                                        </div>
                                        <input type="text" wire:model="editExampleSentence" class="w-full mt-1.5 px-2 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20" placeholder="Beispielsatz..." />
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="space-y-1.5">
                                            <select wire:model="editWordType" class="w-full px-1 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20">
                                                <option value="">-</option>
                                                <option value="noun">Nomen</option>
                                                <option value="verb">Verb</option>
                                                <option value="adjective">Adj.</option>
                                                <option value="adverb">Adv.</option>
                                                <option value="phrase">Phrase</option>
                                            </select>
                                            <select wire:model="editGender" class="w-full px-1 py-1 text-sm bg-black/[0.03] dark:bg-white/5 rounded border-0 focus:ring-2 focus:ring-violet-500/20">
                                                <option value="">-</option>
                                                <option value="m">m</option>
                                                <option value="f">f</option>
                                                <option value="n">n</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex gap-1">
                                            <button wire:click="saveEntry" class="p-1 rounded text-emerald-500 hover:bg-emerald-500/10 transition-colors">
                                                @svg('heroicon-o-check', 'w-4 h-4')
                                            </button>
                                            <button wire:click="cancelEditing" class="p-1 rounded text-gray-500 dark:text-gray-400 hover:bg-gray-500/10 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-4 h-4')
                                            </button>
                                        </div>
                                    </td>
                                @else
                                    {{-- View Mode --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-start gap-1.5">
                                            <button wire:click="playTts({{ $entry->id }})" wire:loading.attr="disabled" wire:target="playTts({{ $entry->id }})" class="flex-shrink-0 p-1 mt-0.5 rounded-md text-gray-400 dark:text-gray-500 hover:text-violet-500 hover:bg-violet-500/10 transition-colors" title="Aussprache anhören">
                                                <span wire:loading wire:target="playTts({{ $entry->id }})">
                                                    <svg class="animate-spin w-3.5 h-3.5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                </span>
                                                <span wire:loading.remove wire:target="playTts({{ $entry->id }})">
                                                    @svg('heroicon-o-speaker-wave', 'w-3.5 h-3.5')
                                                </span>
                                            </button>
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $entry->term }}</span>
                                                    @if($entry->gender)
                                                        <x-ui-badge variant="{{ $entry->gender === 'm' ? 'info' : ($entry->gender === 'f' ? 'danger' : 'muted') }}" size="xs">{{ $entry->gender }}</x-ui-badge>
                                                    @endif
                                                    @if($entry->plural)
                                                    <span class="text-[10px] text-gray-500 dark:text-gray-400">(Pl: {{ $entry->plural }})</span>
                                                    @endif
                                                </div>
                                                @if($entry->example_sentence)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 italic">{{ $entry->example_sentence }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 align-top">{{ $entry->translation }}</td>
                                    <td class="px-4 py-3 align-top">
                                        @if($entry->word_type)
                                            <x-ui-badge variant="muted" size="xs">{{ $entry->word_type }}</x-ui-badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button wire:click="startEditing({{ $entry->id }})" class="p-1 rounded text-gray-500 dark:text-gray-400 hover:text-violet-500 hover:bg-violet-500/10 transition-colors">
                                                @svg('heroicon-o-pencil', 'w-3.5 h-3.5')
                                            </button>
                                            <x-ui-confirm-button action="deleteEntry" :value="$entry->id" text="" confirmText="Löschen?" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>' size="sm" class="p-1 rounded text-gray-500 dark:text-gray-400" />
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
    <x-ui-modal wire:model="showEditListModal">
        <x-slot name="header">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Liste bearbeiten</h2>
        </x-slot>

        <form wire:submit="saveListDetails" class="space-y-4">
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
        </form>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary-outline" wire:click="$set('showEditListModal', false)">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="saveListDetails">
                    Speichern
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Generate More Modal --}}
    <x-ui-modal wire:model="showGenerateModal">
        <x-slot name="header">
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Weitere Vokabeln generieren</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Die KI generiert Vokabeln und fügt sie zu "{{ $list->name }}" hinzu</p>
            </div>
        </x-slot>

        <form wire:submit="generateMore" class="space-y-4">
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
        </form>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-ui-button variant="secondary-outline" wire:click="$set('showGenerateModal', false)" :disabled="$generating">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="generateMore" :disabled="$generating">
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
                {{-- List Info --}}
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Listeninfo</h3>
                    <div class="space-y-2">
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Sprachen</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}</span>
                            </div>
                        </div>
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Vokabeln</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $totalEntries }}</span>
                            </div>
                        </div>
                        @if($list->level)
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Niveau</span>
                                <x-ui-badge variant="success" size="sm">{{ $list->level }}</x-ui-badge>
                            </div>
                        </div>
                        @endif
                        <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Mit Beispielsatz</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $withExamples }} / {{ $totalEntries }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Word Type Distribution --}}
                @if($wordTypeStats->isNotEmpty())
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Wortarten</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($wordTypeStats as $type => $count)
                            <x-ui-badge variant="muted" size="sm">{{ $type }}: {{ $count }}</x-ui-badge>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Quick Actions --}}
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">Schnellzugriff</h3>
                    <div class="space-y-2">
                        <a href="{{ route('vocab.quiz.play', ['uuid' => $list->uuid]) }}" wire:navigate class="flex items-center gap-2 p-2 rounded-lg hover:bg-black/[0.03] dark:hover:bg-white/[0.03] transition-colors text-sm text-gray-600 dark:text-gray-300">
                            @svg('heroicon-o-academic-cap', 'w-4 h-4 text-emerald-500')
                            Quiz starten
                        </a>
                        @if($withoutExamples > 0)
                        <button wire:click="generateExamples" class="flex items-center gap-2 p-2 rounded-lg hover:bg-black/[0.03] dark:hover:bg-white/[0.03] transition-colors text-sm text-gray-600 dark:text-gray-300 w-full text-left">
                            @svg('heroicon-o-chat-bubble-bottom-center-text', 'w-4 h-4 text-amber-500')
                            {{ $withoutExamples }} Beispielsätze generieren
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
</div>
