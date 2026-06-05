<div>
    <x-ui-page>
        <x-slot name="navbar">
            <x-ui-page-navbar title="" />
        </x-slot>

        <x-slot name="actionbar">
            <x-ui-page-actionbar :breadcrumbs="[
                ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
                ['label' => $list->name, 'href' => route('vocab.lists.show', ['uuid' => $list->uuid])],
                ['label' => 'Quiz'],
            ]">
                <button @click="Alpine?.store('page') && (Alpine.store('page')['activityOpen'] = !Alpine.store('page')['activityOpen'])"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-sm rounded-lg text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors">
                    @svg('heroicon-o-chart-bar', 'w-4 h-4')
                    <span class="hidden sm:inline">Statistik</span>
                </button>
            </x-ui-page-actionbar>
        </x-slot>

        <x-ui-page-container>
            <div class="max-w-2xl mx-auto space-y-6">

                @if(!$quizStarted)
                    {{-- Quiz Setup --}}
                    <div class="relative overflow-hidden rounded-2xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-[var(--ui-border)] shadow-sm shadow-black/5 p-8">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-500/50 to-transparent"></div>
                        <div class="text-center mb-8">
                            <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-teal-500/10 mx-auto mb-4">
                                @svg('heroicon-o-academic-cap', 'w-8 h-8 text-emerald-500')
                            </div>
                            <h1 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100 mb-1">Quiz: {{ $list->name }}</h1>
                            <p class="text-sm text-[var(--ui-muted)]">
                                {{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}{{ $list->level ? ' · ' . $list->level : '' }}
                            </p>
                        </div>

                        <div class="space-y-4 max-w-sm mx-auto">
                            <div>
                                <label class="block text-xs font-medium text-[var(--ui-muted)] mb-1">Modus</label>
                                <select wire:model="mode" class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-emerald-500/20 transition-all">
                                    <option value="translate">Übersetzung</option>
                                    <option value="fill_blank">Lückentext</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--ui-muted)] mb-1">Richtung</label>
                                <select wire:model="direction" class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-emerald-500/20 transition-all">
                                    <option value="source_to_target">{{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}</option>
                                    <option value="target_to_source">{{ strtoupper($list->target_language) }} → {{ strtoupper($list->source_language) }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[var(--ui-muted)] mb-1">Anzahl Fragen</label>
                                <input type="number" wire:model="questionCount" min="3" max="30"
                                    class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-emerald-500/20 transition-all" />
                            </div>
                            @error('quiz') <div class="text-xs text-red-500">{{ $message }}</div> @enderror
                            <x-ui-button variant="success" size="lg" wire:click="startQuiz" wire:loading.attr="disabled" wire:target="startQuiz" class="w-full justify-center">
                                <span wire:loading wire:target="startQuiz" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Quiz wird erstellt...
                                </span>
                                <span wire:loading.remove wire:target="startQuiz" class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-play', 'w-4 h-4')
                                    Quiz starten
                                </span>
                            </x-ui-button>
                        </div>
                    </div>

                @elseif($quizFinished)
                    {{-- Results --}}
                    <div class="relative overflow-hidden rounded-2xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-[var(--ui-border)] shadow-sm shadow-black/5 p-8">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/50 to-transparent"></div>
                        <div class="text-center mb-8">
                            <div class="flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-br {{ $correctCount >= count($results) * 0.7 ? 'from-emerald-500/20 to-teal-500/20' : 'from-amber-500/20 to-orange-500/20' }} mx-auto mb-4">
                                <span class="text-3xl font-bold {{ $correctCount >= count($results) * 0.7 ? 'text-emerald-500' : 'text-amber-500' }}">{{ $correctCount }}/{{ count($results) }}</span>
                            </div>
                            <h2 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100 mb-1">
                                @if($correctCount >= count($results) * 0.9)
                                    Hervorragend!
                                @elseif($correctCount >= count($results) * 0.7)
                                    Gut gemacht!
                                @elseif($correctCount >= count($results) * 0.5)
                                    Weiter üben!
                                @else
                                    Nicht aufgeben!
                                @endif
                            </h2>
                            <p class="text-sm text-[var(--ui-muted)]">{{ $correctCount }} von {{ count($results) }} richtig ({{ count($results) > 0 ? round($correctCount / count($results) * 100) : 0 }}%)</p>
                        </div>

                        {{-- Result Details --}}
                        <div class="space-y-2 mb-6">
                            @foreach($results as $i => $result)
                            <div class="flex items-center gap-3 p-3 rounded-lg {{ $result['correct'] ? 'bg-emerald-500/5' : 'bg-red-500/5' }}">
                                <div class="flex-shrink-0">
                                    @if($result['correct'])
                                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-emerald-500')
                                    @else
                                        @svg('heroicon-o-x-circle', 'w-5 h-5 text-red-500')
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-gray-700 dark:text-gray-300">{{ $result['question'] }}</div>
                                    @if(!$result['correct'])
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            Deine Antwort: <span class="text-red-500">{{ $result['user_answer'] }}</span>
                                            &middot; Richtig: <span class="text-emerald-500">{{ $result['expected'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-center gap-3">
                            <x-ui-button variant="success" wire:click="restartQuiz">
                                @svg('heroicon-o-arrow-path', 'w-4 h-4')
                                Nochmal
                            </x-ui-button>
                            <x-ui-button variant="secondary-outline" :href="route('vocab.lists.show', ['uuid' => $list->uuid])">
                                @svg('heroicon-o-arrow-left', 'w-4 h-4')
                                Zur Liste
                            </x-ui-button>
                        </div>
                    </div>

                @else
                    {{-- Active Quiz --}}
                    {{-- Progress Bar --}}
                    <div class="relative h-2 rounded-full bg-[var(--ui-muted-5)] overflow-hidden">
                        <div class="absolute inset-y-0 left-0 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-300"
                             style="width: {{ count($questions) > 0 ? ($currentIndex / count($questions) * 100) : 0 }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-[var(--ui-muted)]">
                        <span>Frage {{ $currentIndex + 1 }} von {{ count($questions) }}</span>
                        <span>{{ $correctCount }} richtig</span>
                    </div>

                    {{-- Question Card --}}
                    <div class="relative overflow-hidden rounded-2xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-[var(--ui-border)] shadow-sm shadow-black/5 p-8">
                        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-emerald-500/40 to-transparent"></div>

                        @php $question = $questions[$currentIndex] ?? null; @endphp

                        @if($question)
                            <div class="text-center mb-6">
                                @if(!empty($question['hint']))
                                <div class="text-xs text-[var(--ui-muted)] mb-2">{{ $question['hint'] }}</div>
                                @endif
                                <h2 class="text-2xl font-medium tracking-tight text-gray-900 dark:text-gray-100">
                                    {{ $question['question'] }}
                                </h2>
                            </div>

                            @if($mode === 'multiple_choice' && !empty($question['options']))
                                {{-- Multiple Choice --}}
                                <div class="space-y-2 max-w-sm mx-auto">
                                    @foreach($question['options'] as $idx => $option)
                                    <button
                                        wire:click="selectOption({{ $idx }})"
                                        class="w-full text-left px-4 py-3 rounded-lg text-sm transition-all
                                            @if($answered && $option === ($feedback['expected'] ?? ''))
                                                bg-emerald-500/10 border-2 border-emerald-500/30 text-emerald-700 dark:text-emerald-300
                                            @elseif($answered && $userAnswer === $option && !($feedback['correct'] ?? false))
                                                bg-red-500/10 border-2 border-red-500/30 text-red-700 dark:text-red-300
                                            @else
                                                bg-black/[0.03] dark:bg-white/5 border-2 border-transparent hover:border-[var(--ui-primary-20)] text-gray-700 dark:text-gray-300
                                            @endif
                                        "
                                        @if($answered) disabled @endif
                                    >
                                        {{ $option }}
                                    </button>
                                    @endforeach
                                </div>
                            @else
                                {{-- Text Input --}}
                                <div class="max-w-sm mx-auto">
                                    <form wire:submit="submitAnswer">
                                        <input type="text" wire:model="userAnswer" placeholder="Deine Antwort..."
                                            class="w-full px-4 py-3 text-center text-lg bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-emerald-500/20 transition-all"
                                            @if($answered) disabled @endif
                                            autofocus />
                                        @if(!$answered)
                                        <x-ui-button variant="success" type="submit" wire:loading.attr="disabled" wire:target="submitAnswer" class="w-full mt-3 justify-center">
                                            <span wire:loading wire:target="submitAnswer" class="inline-flex items-center gap-2">
                                                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                Prüfe...
                                            </span>
                                            <span wire:loading.remove wire:target="submitAnswer">
                                                Prüfen
                                            </span>
                                        </x-ui-button>
                                        @endif
                                    </form>
                                </div>
                            @endif

                            {{-- Feedback --}}
                            @if($answered && $feedback)
                            <div class="mt-6 p-4 rounded-xl {{ ($feedback['correct'] ?? false) ? 'bg-emerald-500/5 border border-emerald-500/10' : 'bg-red-500/5 border border-red-500/10' }}">
                                <div class="flex items-start gap-3">
                                    @if($feedback['correct'] ?? false)
                                        @svg('heroicon-o-check-circle', 'w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5')
                                    @else
                                        @svg('heroicon-o-x-circle', 'w-5 h-5 text-red-500 flex-shrink-0 mt-0.5')
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium {{ ($feedback['correct'] ?? false) ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                            {{ ($feedback['correct'] ?? false) ? 'Richtig!' : 'Leider falsch' }}
                                        </div>
                                        @if(!empty($feedback['feedback']))
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $feedback['feedback'] }}</div>
                                        @endif
                                        @if(!($feedback['correct'] ?? false) && !empty($feedback['expected']))
                                        <div class="text-sm text-[var(--ui-muted)] mt-1">Korrekt: <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $feedback['expected'] }}</span></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 text-center">
                                <x-ui-button variant="primary" wire:click="nextQuestion">
                                    {{ $currentIndex + 1 >= count($questions) ? 'Ergebnis anzeigen' : 'Nächste Frage' }}
                                    @svg('heroicon-o-arrow-right', 'w-4 h-4')
                                </x-ui-button>
                            </div>
                            @endif
                        @endif
                    </div>
                @endif

            </div>
        </x-ui-page-container>

        {{-- Activity Sidebar --}}
        <x-slot name="activity">
            <x-ui-page-sidebar title="Quiz-Info" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
                <div class="p-5 space-y-5">
                    {{-- List Info --}}
                    <div>
                        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Liste</h3>
                        <div class="space-y-2">
                            <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $list->name }}</div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }}
                                    @if($list->level) · {{ $list->level }} @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Live Stats --}}
                    @if($quizStarted && !$quizFinished)
                    <div>
                        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Ergebnis</h3>
                        <div class="space-y-2">
                            <div class="p-3 rounded-lg bg-black/[0.02] dark:bg-white/[0.03]">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-400">Fortschritt</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $currentIndex + ($answered ? 1 : 0) }} / {{ count($questions) }}</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-emerald-500/5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400">Richtig</span>
                                    <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $correctCount }}</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-red-500/5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-red-600 dark:text-red-400">Falsch</span>
                                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ $totalAnswered - $correctCount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Quiz Finished Stats --}}
                    @if($quizFinished)
                    <div>
                        <h3 class="text-xs font-medium uppercase tracking-wider text-gray-400 mb-3">Ergebnis</h3>
                        <div class="space-y-2">
                            <div class="p-3 rounded-lg {{ $correctCount >= count($results) * 0.7 ? 'bg-emerald-500/5' : 'bg-amber-500/5' }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Quote</span>
                                    <span class="text-sm font-semibold {{ $correctCount >= count($results) * 0.7 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                        {{ count($results) > 0 ? round($correctCount / count($results) * 100) : 0 }}%
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-emerald-500/5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400">Richtig</span>
                                    <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ $correctCount }}</span>
                                </div>
                            </div>
                            <div class="p-3 rounded-lg bg-red-500/5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-red-600 dark:text-red-400">Falsch</span>
                                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ count($results) - $correctCount }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </x-ui-page-sidebar>
        </x-slot>
    </x-ui-page>
</div>
