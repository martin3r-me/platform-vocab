<div
    x-data="{ audio: null }"
    @play-tts.window="
        try { if (audio) { audio.pause(); audio.currentTime = 0; } } catch(e) {}
        audio = new Audio($event.detail.audio);
        audio.play().catch(() => {});
    "
    @if($keyboardShortcuts && !$finished && $total > 0)
    @keydown.window="
        const tag = ($event.target?.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
        if ($event.metaKey || $event.ctrlKey || $event.altKey) return;
        @if(!$showAnswer)
            if ($event.code === 'Space' || $event.code === 'Enter') { $event.preventDefault(); $wire.reveal(); }
        @else
            if ($event.key === '1') { $event.preventDefault(); $wire.rate(1); }
            else if ($event.key === '2') { $event.preventDefault(); $wire.rate(3); }
            else if ($event.key === '3') { $event.preventDefault(); $wire.rate(4); }
            else if ($event.key === '4') { $event.preventDefault(); $wire.rate(5); }
        @endif
    "
    @endif
>
    @include('vocab::livewire.partials.achievement-toast')
    <x-ui-page>
        <x-slot name="navbar">
            <x-ui-page-navbar title="" />
        </x-slot>

        <x-slot name="actionbar">
            <x-ui-page-actionbar :breadcrumbs="[
                ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
                ['label' => 'Wiederholen'],
            ]">
                @if(!$finished && $total > 0)
                    <button wire:click="toggleListeningFirst" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg transition-colors {{ $listeningFirst ? 'text-violet-600 dark:text-violet-400 bg-violet-500/10' : 'text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]' }}" title="Hör-Modus {{ $listeningFirst ? 'deaktivieren' : 'aktivieren' }}">
                        @if($listeningFirst)
                            @svg('heroicon-o-musical-note', 'w-4 h-4')
                            <span class="hidden sm:inline">Hören</span>
                        @else
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            <span class="hidden sm:inline">Lesen</span>
                        @endif
                    </button>
                    <button wire:click="toggleMute" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors" title="Audio {{ $muteAudio ? 'aktivieren' : 'stummschalten' }}">
                        @if($muteAudio)
                            @svg('heroicon-o-speaker-x-mark', 'w-4 h-4')
                        @else
                            @svg('heroicon-o-speaker-wave', 'w-4 h-4')
                        @endif
                    </button>
                @endif
            </x-ui-page-actionbar>
        </x-slot>

        <x-ui-page-container>
            <div class="max-w-2xl mx-auto space-y-6">

                @if($total === 0)
                    {{-- Empty state --}}
                    <div class="relative overflow-hidden rounded-2xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-[var(--ui-border)] shadow-sm shadow-black/5 p-10 text-center">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-500/40 to-transparent"></div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 mx-auto mb-4">
                            @svg('heroicon-o-sparkles', 'w-8 h-8 text-emerald-500')
                        </div>
                        <h1 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100 mb-1">Alles erledigt</h1>
                        <p class="text-sm text-[var(--ui-muted)] mb-6">
                            Keine fälligen Karten und keine neuen Karten in deinen abonnierten Listen.
                        </p>
                        <x-ui-button variant="primary-outline" :href="route('vocab.lists.index')">
                            Listen ansehen
                        </x-ui-button>
                    </div>

                @elseif($finished)
                    {{-- Summary --}}
                    <div class="relative overflow-hidden rounded-2xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-[var(--ui-border)] shadow-sm shadow-black/5 p-10 text-center">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-500/50 to-transparent"></div>
                        <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 mx-auto mb-4">
                            @svg('heroicon-o-check-badge', 'w-8 h-8 text-emerald-500')
                        </div>
                        <h1 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100 mb-1">Session abgeschlossen</h1>
                        <p class="text-sm text-[var(--ui-muted)] mb-8">
                            {{ $done }} Karten reviewed
                        </p>

                        <div class="grid grid-cols-2 gap-4 max-w-sm mx-auto mb-8">
                            <div class="rounded-xl p-4 bg-emerald-500/5 border border-emerald-500/10">
                                <div class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $correctCount }}</div>
                                <div class="text-xs text-[var(--ui-muted)] mt-1">gewusst</div>
                            </div>
                            <div class="rounded-xl p-4 bg-rose-500/5 border border-rose-500/10">
                                <div class="text-2xl font-semibold text-rose-600 dark:text-rose-400">{{ $againCount }}</div>
                                <div class="text-xs text-[var(--ui-muted)] mt-1">wiederholen</div>
                            </div>
                        </div>

                        <div class="flex items-center justify-center gap-2">
                            <x-ui-button variant="primary-outline" wire:click="startAgain">
                                @svg('heroicon-o-arrow-path', 'w-4 h-4')
                                Nochmal laden
                            </x-ui-button>
                            <x-ui-button variant="success" :href="route('vocab.dashboard')">
                                Zum Dashboard
                            </x-ui-button>
                        </div>
                    </div>

                @else
                    {{-- Progress bar --}}
                    <div>
                        <div class="flex items-center justify-between text-xs text-[var(--ui-muted)] mb-2">
                            <span>{{ $done }} / {{ $total }}</span>
                            <span>{{ $progressPct }}%</span>
                        </div>
                        <div class="h-1 rounded-full bg-black/[0.06] dark:bg-white/10 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all" style="width: {{ $progressPct }}%"></div>
                        </div>
                    </div>

                    {{-- Card --}}
                    <div class="relative overflow-hidden rounded-2xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-[var(--ui-border)] shadow-sm shadow-black/5">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/40 to-transparent"></div>

                        <div class="p-8 min-h-[280px] flex flex-col">
                            {{-- Meta --}}
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-2">
                                    <x-ui-badge variant="muted" size="sm">{{ $current['target_language'] }}</x-ui-badge>
                                    @if($current['list_name'])
                                        <span class="text-xs text-[var(--ui-muted)] truncate max-w-[200px]">{{ $current['list_name'] }}</span>
                                    @endif
                                </div>
                                <span class="text-xs text-[var(--ui-muted)]">{{ $done + 1 }} / {{ $total }}</span>
                            </div>

                            {{-- Term --}}
                            <div class="flex-1 flex flex-col items-center justify-center text-center">
                                @if($listeningFirst && !$showAnswer)
                                    <button wire:click="playCurrentTts" class="group flex flex-col items-center gap-3 px-8 py-6 rounded-2xl bg-gradient-to-br from-violet-500/10 to-fuchsia-500/10 hover:from-violet-500/20 hover:to-fuchsia-500/20 border border-violet-500/20 transition-all">
                                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-violet-500/15 group-hover:bg-violet-500/25 transition-colors">
                                            @svg('heroicon-o-speaker-wave', 'w-8 h-8 text-violet-500 group-hover:scale-110 transition-transform')
                                        </div>
                                        <div class="text-sm font-medium text-violet-700 dark:text-violet-300">Karte anhören</div>
                                        <div class="text-[11px] text-[var(--ui-muted)]">Klick zum erneut Abspielen</div>
                                    </button>
                                @else
                                    <div class="text-2xl font-medium text-gray-900 dark:text-gray-100">{{ $current['term'] }}</div>
                                @endif

                                @if($showAnswer)
                                    <div class="w-full max-w-md mt-6 pt-6 border-t border-black/5 dark:border-white/10 space-y-3">
                                        <div class="text-lg text-emerald-600 dark:text-emerald-400">{{ $current['translation'] }}</div>
                                        @if($current['example_sentence'])
                                            <div class="text-sm italic text-[var(--ui-muted)]">{{ $current['example_sentence'] }}</div>
                                        @endif
                                        @if($current['notes'])
                                            <div class="text-xs text-[var(--ui-muted)] mt-2 px-3 py-2 rounded-lg bg-black/[0.03] dark:bg-white/5">{{ $current['notes'] }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="border-t border-black/5 dark:border-white/10 p-4 bg-black/[0.02] dark:bg-white/[0.02]">
                            @if(!$showAnswer)
                                <x-ui-button variant="primary" size="lg" wire:click="reveal" class="w-full justify-center">
                                    <span>Lösung zeigen</span>
                                    @if($keyboardShortcuts)
                                        <kbd class="ml-2 px-1.5 py-0.5 text-[10px] font-mono rounded bg-white/20 text-white/80">Space</kbd>
                                    @endif
                                </x-ui-button>
                            @else
                                <div class="grid grid-cols-4 gap-2">
                                    <button wire:click="rate(1)" class="relative flex flex-col items-center gap-1 px-2 py-3 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-700 dark:text-rose-300 transition-colors">
                                        @if($keyboardShortcuts)
                                            <kbd class="absolute top-1 right-1 px-1 text-[9px] font-mono rounded bg-rose-500/20 text-rose-600">1</kbd>
                                        @endif
                                        <span class="text-xs font-medium">Wieder</span>
                                        <span class="text-[10px] text-[var(--ui-muted)]">&lt; 1 Tag</span>
                                    </button>
                                    <button wire:click="rate(3)" class="relative flex flex-col items-center gap-1 px-2 py-3 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 text-amber-700 dark:text-amber-300 transition-colors">
                                        @if($keyboardShortcuts)
                                            <kbd class="absolute top-1 right-1 px-1 text-[9px] font-mono rounded bg-amber-500/20 text-amber-600">2</kbd>
                                        @endif
                                        <span class="text-xs font-medium">Schwer</span>
                                        <span class="text-[10px] text-[var(--ui-muted)]">kurzer Abstand</span>
                                    </button>
                                    <button wire:click="rate(4)" class="relative flex flex-col items-center gap-1 px-2 py-3 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 transition-colors">
                                        @if($keyboardShortcuts)
                                            <kbd class="absolute top-1 right-1 px-1 text-[9px] font-mono rounded bg-emerald-500/20 text-emerald-600">3</kbd>
                                        @endif
                                        <span class="text-xs font-medium">Gut</span>
                                        <span class="text-[10px] text-[var(--ui-muted)]">Standard</span>
                                    </button>
                                    <button wire:click="rate(5)" class="relative flex flex-col items-center gap-1 px-2 py-3 rounded-lg bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/20 text-sky-700 dark:text-sky-300 transition-colors">
                                        @if($keyboardShortcuts)
                                            <kbd class="absolute top-1 right-1 px-1 text-[9px] font-mono rounded bg-sky-500/20 text-sky-600">4</kbd>
                                        @endif
                                        <span class="text-xs font-medium">Einfach</span>
                                        <span class="text-[10px] text-[var(--ui-muted)]">lang aufschieben</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="text-center text-xs text-[var(--ui-muted)] flex items-center justify-center gap-3">
                        <span class="inline-flex items-center gap-1">
                            @svg('heroicon-o-information-circle', 'w-3.5 h-3.5')
                            Erst überlegen, dann Lösung zeigen
                        </span>
                        @if($showAnswer && !$muteAudio)
                            <button wire:click="playCurrentTts" class="inline-flex items-center gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                                @svg('heroicon-o-speaker-wave', 'w-3.5 h-3.5')
                                Erneut anhören
                            </button>
                        @endif
                    </div>
                @endif

            </div>
        </x-ui-page-container>
    </x-ui-page>
</div>
