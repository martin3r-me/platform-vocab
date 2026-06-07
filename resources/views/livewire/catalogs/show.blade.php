<div>
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
            ['label' => 'Bibliothek', 'href' => route('vocab.catalogs.index')],
            ['label' => $catalog->name],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Header --}}
            <div class="relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 p-6">
                <div class="absolute inset-x-0 top-0 h-1" style="background: linear-gradient(90deg, transparent, {{ $catalog->cover_color ?? '#8b5cf6' }}, transparent)"></div>
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            @if($catalog->visibility === 'personal')
                                <x-ui-badge variant="muted" size="sm">
                                    @svg('heroicon-o-user', 'w-3 h-3 inline mr-1')
                                    Persönlich
                                </x-ui-badge>
                            @else
                                <x-ui-badge variant="primary" size="sm">
                                    @svg('heroicon-o-users', 'w-3 h-3 inline mr-1')
                                    Team
                                </x-ui-badge>
                            @endif
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $lists->count() }} {{ $lists->count() === 1 ? 'Liste' : 'Listen' }}</span>
                        </div>
                        <h1 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100 truncate">{{ $catalog->name }}</h1>
                        @if($catalog->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $catalog->description }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($isOwner)
                            <x-ui-button variant="primary" size="sm" wire:click="openAttachModal">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                Liste hinzufügen
                            </x-ui-button>
                            <button wire:click="openEditModal" class="p-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:text-violet-500 hover:bg-violet-500/10 transition-colors" title="Katalog bearbeiten">
                                @svg('heroicon-o-pencil-square', 'w-4 h-4')
                            </button>
                            <x-ui-confirm-button action="deleteCatalog" text="" confirmText="Katalog wirklich löschen? Die Listen bleiben erhalten." icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>' size="sm" class="p-1.5 rounded-md text-gray-500 dark:text-gray-400 hover:text-rose-500 hover:bg-rose-500/10 transition-colors" />
                        @endif
                    </div>
                </div>
            </div>

            {{-- Lists Grid --}}
            @if($lists->isEmpty())
                <div class="rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 p-10 text-center">
                    @svg('heroicon-o-rectangle-stack', 'w-10 h-10 text-gray-500 dark:text-gray-400 mx-auto mb-3')
                    <h2 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Noch keine Listen im Katalog</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        @if($isOwner)
                            Füge bestehende Listen hinzu, um sie hier zu bündeln.
                        @else
                            Der Katalog-Besitzer hat noch keine Listen hinzugefügt.
                        @endif
                    </p>
                    @if($isOwner)
                        <x-ui-button variant="primary-outline" size="sm" wire:click="openAttachModal">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                            Liste hinzufügen
                        </x-ui-button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
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
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">{{ $list->description }}</p>
                                @endif
                                @if($list->is_enrolled && $list->entries_count > 0)
                                    <div class="mt-3 h-1 rounded-full bg-black/[0.06] dark:bg-white/10 overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all" style="width: {{ $list->mastery_pct }}%"></div>
                                    </div>
                                @endif
                                <div class="flex items-center justify-between mt-3 pt-3 border-t border-black/5 dark:border-white/5">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $list->entries_count }} Vokabeln</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $list->updated_at->diffForHumans() }}</span>
                                </div>
                            </a>
                            <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex gap-1">
                                @if($list->is_enrolled)
                                    <button wire:click.stop="unenroll({{ $list->id }})" class="p-1.5 rounded-md bg-white/80 dark:bg-black/40 hover:bg-rose-500/10 text-emerald-500 hover:text-rose-500 transition-colors" title="Nicht mehr lernen">
                                        @svg('heroicon-s-bookmark', 'w-4 h-4')
                                    </button>
                                @else
                                    <button wire:click.stop="enroll({{ $list->id }})" class="p-1.5 rounded-md bg-white/80 dark:bg-black/40 hover:bg-emerald-500/10 text-gray-500 dark:text-gray-400 hover:text-emerald-500 transition-colors" title="Lernen">
                                        @svg('heroicon-o-bookmark', 'w-4 h-4')
                                    </button>
                                @endif
                                @if($isOwner)
                                    <x-ui-confirm-button action="detachList" :value="$list->id" text="" confirmText="Aus Katalog entfernen?" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" /></svg>' size="sm" class="p-1.5 rounded-md bg-white/80 dark:bg-black/40 text-gray-500 dark:text-gray-400 hover:text-rose-500" />
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </x-ui-page-container>

    {{-- Attach Lists Modal --}}
    @if($showAttachModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" wire:click.self="$set('showAttachModal', false)">
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-zinc-900 border border-black/5 dark:border-white/10 shadow-2xl flex flex-col max-h-[85vh]">
                <div class="p-6 border-b border-black/5 dark:border-white/10 flex items-center justify-between">
                    <h2 class="text-base font-medium text-gray-900 dark:text-gray-100">Listen zum Katalog hinzufügen</h2>
                    <button type="button" wire:click="$set('showAttachModal', false)" class="text-gray-500 dark:text-gray-400 hover:text-gray-600">
                        @svg('heroicon-o-x-mark', 'w-5 h-5')
                    </button>
                </div>

                <div class="p-4 border-b border-black/5 dark:border-white/10">
                    <input type="text" wire:model.live.debounce.250ms="attachSearch" placeholder="Listen suchen…"
                        class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20" />
                </div>

                <div class="flex-1 overflow-y-auto p-2">
                    @forelse($attachableLists as $list)
                        <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-black/[0.03] dark:hover:bg-white/5 cursor-pointer transition-colors">
                            <input type="checkbox" wire:model.live="selectedListIds" value="{{ $list->id }}"
                                class="w-4 h-4 rounded border-gray-300 text-violet-500 focus:ring-violet-500/20" />
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $list->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ strtoupper($list->source_language) }} → {{ strtoupper($list->target_language) }} · {{ $list->entries_count }} Vokabeln
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="p-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            Keine weiteren Listen verfügbar.
                        </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-black/5 dark:border-white/10 flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($selectedListIds) }} ausgewählt</span>
                    <div class="flex items-center gap-2">
                        <x-ui-button type="button" variant="secondary-outline" size="sm" wire:click="$set('showAttachModal', false)">Abbrechen</x-ui-button>
                        <x-ui-button type="button" variant="primary" size="sm" wire:click="attachLists" :disabled="empty($selectedListIds)">
                            Hinzufügen
                        </x-ui-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Catalog Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" wire:click.self="$set('showEditModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-zinc-900 border border-black/5 dark:border-white/10 shadow-2xl">
                <form wire:submit="saveCatalog" class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-medium text-gray-900 dark:text-gray-100">Katalog bearbeiten</h2>
                        <button type="button" wire:click="$set('showEditModal', false)" class="text-gray-500 dark:text-gray-400 hover:text-gray-600">
                            @svg('heroicon-o-x-mark', 'w-5 h-5')
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                        <input type="text" wire:model="editName" required
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20" />
                        @error('editName') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Beschreibung</label>
                        <textarea wire:model="editDescription" rows="3"
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Sichtbarkeit</label>
                            <select wire:model="editVisibility" class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20">
                                <option value="team">Team</option>
                                <option value="personal">Persönlich</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Farbe</label>
                            <input type="color" wire:model="editCoverColor"
                                class="w-full h-[38px] px-1 py-1 bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 cursor-pointer" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <x-ui-button type="button" variant="secondary-outline" size="sm" wire:click="$set('showEditModal', false)">Abbrechen</x-ui-button>
                        <x-ui-button type="submit" variant="primary" size="sm">Speichern</x-ui-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-ui-page>
</div>
