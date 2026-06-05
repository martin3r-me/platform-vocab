<div>
    <x-ui-page>
        <x-slot name="navbar">
            <x-ui-page-navbar title="" />
        </x-slot>

        <x-slot name="actionbar">
            <x-ui-page-actionbar :breadcrumbs="[
                ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
                ['label' => 'Wiederholen'],
            ]" />
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
                                <div class="text-2xl font-medium text-gray-900 dark:text-gray-100">{{ $current['term'] }}</div>

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
                                    Lösung zeigen
                                </x-ui-button>
                            @else
                                <div class="grid grid-cols-4 gap-2">
                                    <button wire:click="rate(1)" class="flex flex-col items-center gap-1 px-2 py-3 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-700 dark:text-rose-300 transition-colors">
                                        <span class="text-xs font-medium">Wieder</span>
                                        <span class="text-[10px] text-[var(--ui-muted)]">&lt; 1 Tag</span>
                                    </button>
                                    <button wire:click="rate(3)" class="flex flex-col items-center gap-1 px-2 py-3 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 text-amber-700 dark:text-amber-300 transition-colors">
                                        <span class="text-xs font-medium">Schwer</span>
                                        <span class="text-[10px] text-[var(--ui-muted)]">kurzer Abstand</span>
                                    </button>
                                    <button wire:click="rate(4)" class="flex flex-col items-center gap-1 px-2 py-3 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 transition-colors">
                                        <span class="text-xs font-medium">Gut</span>
                                        <span class="text-[10px] text-[var(--ui-muted)]">Standard</span>
                                    </button>
                                    <button wire:click="rate(5)" class="flex flex-col items-center gap-1 px-2 py-3 rounded-lg bg-sky-500/10 hover:bg-sky-500/20 border border-sky-500/20 text-sky-700 dark:text-sky-300 transition-colors">
                                        <span class="text-xs font-medium">Einfach</span>
                                        <span class="text-[10px] text-[var(--ui-muted)]">lang aufschieben</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="text-center text-xs text-[var(--ui-muted)]">
                        <span class="inline-flex items-center gap-1">
                            @svg('heroicon-o-information-circle', 'w-3.5 h-3.5')
                            Erst überlegen, dann Lösung zeigen
                        </span>
                    </div>
                @endif

            </div>
        </x-ui-page-container>
    </x-ui-page>
</div>
