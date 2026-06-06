<div>
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
        ]">
            <button wire:click="openSettingsModal" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                @svg('heroicon-o-cog-6-tooth', 'w-4 h-4')
                <span class="hidden sm:inline">Einstellungen</span>
            </button>
            <button @click="Alpine?.store('page') && (Alpine.store('page')['activityOpen'] = !Alpine.store('page')['activityOpen'])"
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                @svg('heroicon-o-chart-bar', 'w-4 h-4')
                <span class="hidden sm:inline">Team-Statistik</span>
            </button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Hero: Streak + Today + Due CTA --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Streak --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-transparent dark:from-amber-500/20 dark:via-orange-500/10 border border-amber-500/20 p-5">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-amber-500/60 to-transparent"></div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-amber-500/15">
                            @svg('heroicon-o-fire', 'w-5 h-5 text-amber-500')
                        </div>
                        <div class="text-xs uppercase tracking-wider text-amber-700 dark:text-amber-300 font-medium">Streak</div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <div class="text-4xl font-semibold text-gray-900 dark:text-gray-100">{{ $streak['current'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $streak['current'] === 1 ? 'Tag in Folge' : 'Tage in Folge' }}</div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        @if($streak['current'] === 0)
                            Heute starten = Tag 1.
                        @elseif($streak['current'] === $streak['longest'])
                            Persönliche Bestmarke!
                        @else
                            Bestmarke: {{ $streak['longest'] }} Tage
                        @endif
                    </div>
                </div>

                {{-- Today's progress --}}
                <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-transparent dark:from-emerald-500/20 dark:via-teal-500/10 border border-emerald-500/20 p-5">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-500/60 to-transparent"></div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-500/15">
                            @svg('heroicon-o-check-circle', 'w-5 h-5 text-emerald-500')
                        </div>
                        <div class="text-xs uppercase tracking-wider text-emerald-700 dark:text-emerald-300 font-medium">Heute</div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <div class="text-4xl font-semibold text-gray-900 dark:text-gray-100">{{ $today['reviewed_today'] }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">/ {{ $settings->daily_goal }} Karten</div>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-black/[0.06] dark:bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all" style="width: {{ $todayPct }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        @if($todayPct >= 100)
                            Tagesziel erreicht. 🎯
                        @elseif($today['reviewed_today'] === 0)
                            Noch nicht gestartet.
                        @else
                            Noch {{ $settings->daily_goal - $today['reviewed_today'] }} bis zum Ziel.
                        @endif
                    </div>
                </div>

                {{-- Due CTA --}}
                <a href="{{ route('vocab.review') }}" wire:navigate class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500/10 via-fuchsia-500/5 to-transparent dark:from-violet-500/20 dark:via-fuchsia-500/10 border border-violet-500/20 p-5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150 block">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/60 to-transparent"></div>
                    <div class="flex items-center gap-3 mb-3">
                        <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-violet-500/15">
                            @svg('heroicon-o-bolt', 'w-5 h-5 text-violet-500')
                        </div>
                        <div class="text-xs uppercase tracking-wider text-violet-700 dark:text-violet-300 font-medium">Wiederholen</div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <div class="text-4xl font-semibold text-gray-900 dark:text-gray-100">{{ $dueCount }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $dueCount === 1 ? 'Karte fällig' : 'Karten fällig' }}</div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center gap-1">
                        @if($dueCount === 0)
                            Nichts fällig — gönn dir 'ne Pause.
                        @else
                            Jetzt starten
                            @svg('heroicon-o-arrow-right', 'w-3 h-3 group-hover:translate-x-0.5 transition-transform')
                        @endif
                    </div>
                </a>
            </div>

            {{-- Heatmap + Milestone --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Heatmap --}}
                <div class="lg:col-span-2 relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Letzte 90 Tage</h3>
                        <div class="flex items-center gap-1 text-[10px] text-gray-400">
                            <span>weniger</span>
                            <span class="inline-block w-2.5 h-2.5 rounded-sm bg-black/[0.04] dark:bg-white/10"></span>
                            <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500/30"></span>
                            <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500/50"></span>
                            <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500/75"></span>
                            <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-600"></span>
                            <span>mehr</span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="inline-grid grid-flow-col grid-rows-7 gap-[3px]">
                            @foreach($heatmap as $cell)
                                @php
                                    $bg = match($cell['intensity']) {
                                        0 => 'bg-black/[0.04] dark:bg-white/10',
                                        1 => 'bg-emerald-500/30',
                                        2 => 'bg-emerald-500/50',
                                        3 => 'bg-emerald-500/75',
                                        default => 'bg-emerald-600',
                                    };
                                @endphp
                                <div class="w-3 h-3 rounded-sm {{ $bg }}" title="{{ $cell['date'] }}: {{ $cell['count'] }} Reviews"></div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Milestone & totals --}}
                <div class="relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-5">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Nächster Meilenstein</h3>
                    <div class="flex items-baseline gap-2 mb-1">
                        <span class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $mastery['mastered'] }}</span>
                        <span class="text-sm text-gray-400">von {{ $milestone['target'] }} gemeistert</span>
                    </div>
                    @php
                        $milestonePct = $milestone['target'] > 0 ? (int) round($mastery['mastered'] / $milestone['target'] * 100) : 0;
                    @endphp
                    <div class="mt-2 h-2 rounded-full bg-black/[0.06] dark:bg-white/10 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-violet-400 to-fuchsia-500 transition-all" style="width: {{ $milestonePct }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Noch <strong>{{ $milestone['remaining'] }}</strong> bis zum nächsten Meilenstein
                    </div>

                    <div class="mt-5 pt-5 border-t border-black/5 dark:border-white/10 grid grid-cols-2 gap-3 text-center">
                        <div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $totalReviews }}</div>
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 mt-1">Reviews total</div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $mastery['pct'] }}%</div>
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 mt-1">Mastery</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Enrolled lists --}}
            @if($enrolledLists->isNotEmpty())
                <div class="relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Meine Listen</h3>
                        <a href="{{ route('vocab.lists.index') }}" wire:navigate class="text-xs text-violet-500 hover:text-violet-600">Alle ansehen →</a>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($enrolledLists as $list)
                            <a href="{{ route('vocab.lists.show', ['uuid' => $list->uuid]) }}" wire:navigate class="group p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.02] hover:bg-black/[0.04] dark:hover:bg-white/[0.04] transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate flex-1">{{ $list->name }}</div>
                                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 ml-2">{{ $list->mastery_pct }}%</span>
                                </div>
                                <div class="h-1 rounded-full bg-black/[0.06] dark:bg-white/10 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500" style="width: {{ $list->mastery_pct }}%"></div>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-2">{{ $list->entries_count }} Vokabeln</div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 p-8 text-center">
                    @svg('heroicon-o-bookmark', 'w-10 h-10 text-gray-400 mx-auto mb-3')
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Noch keine Liste aktiv</h3>
                    <p class="text-xs text-gray-400 mb-4">Wähle eine Liste aus und klicke „Lernen", um den Fortschritt zu tracken.</p>
                    <x-ui-button variant="primary" size="sm" :href="route('vocab.catalogs.index')">
                        @svg('heroicon-o-rectangle-stack', 'w-3.5 h-3.5')
                        Zur Bibliothek
                    </x-ui-button>
                </div>
            @endif

        </div>
    </x-ui-page-container>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Team-Übersicht" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Team-Bibliothek</h3>
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
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Settings Modal --}}
    @if($showSettingsModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" wire:click.self="$set('showSettingsModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-zinc-900 border border-black/5 dark:border-white/10 shadow-2xl">
                <form wire:submit="saveSettings" class="p-6 space-y-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-medium text-gray-900 dark:text-gray-100">Lern-Einstellungen</h2>
                        <button type="button" wire:click="$set('showSettingsModal', false)" class="text-gray-400 hover:text-gray-600">
                            @svg('heroicon-o-x-mark', 'w-5 h-5')
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tagesziel (Karten)</label>
                        <input type="number" wire:model="settingsDailyGoal" min="1" max="200"
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20" />
                        @error('settingsDailyGoal') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
                        <p class="text-[11px] text-gray-400 mt-1">Wie viele Karten möchtest du pro Tag reviewen? Realistisch sind 5–20.</p>
                    </div>

                    <label class="flex items-start gap-3 p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03] cursor-pointer">
                        <input type="checkbox" wire:model="settingsAutoPlayTts" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-violet-500 focus:ring-violet-500/20" />
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Audio automatisch abspielen</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Beim Aufdecken einer Karte wird die Aussprache automatisch abgespielt.</div>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03] cursor-pointer">
                        <input type="checkbox" wire:model="settingsKeyboardShortcuts" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-violet-500 focus:ring-violet-500/20" />
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">Tastatur-Shortcuts</div>
                            <div class="text-[11px] text-gray-400 mt-0.5">Space = aufdecken, 1/2/3/4 = Wieder/Schwer/Gut/Einfach.</div>
                        </div>
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <x-ui-button type="button" variant="secondary-outline" size="sm" wire:click="$set('showSettingsModal', false)">Abbrechen</x-ui-button>
                        <x-ui-button type="submit" variant="primary" size="sm">Speichern</x-ui-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-ui-page>
</div>
