<div class="h-full">
<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Vokabeln', 'href' => route('vocab.dashboard'), 'icon' => 'language'],
            ['label' => 'Bibliothek'],
        ]">
            <x-ui-button variant="primary" size="sm" wire:click="openCreateModal">
                @svg('heroicon-o-plus', 'w-4 h-4')
                Katalog anlegen
            </x-ui-button>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Search --}}
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-gray-500 dark:text-gray-400">
                    @svg('heroicon-o-magnifying-glass', 'w-4 h-4')
                </span>
                <input type="text" wire:model.live.debounce.250ms="search" placeholder="Kataloge durchsuchen…"
                    class="w-full pl-10 pr-3 py-2 text-sm bg-white/60 dark:bg-white/5 backdrop-blur rounded-xl border border-black/5 dark:border-white/10 focus:ring-2 focus:ring-violet-500/20 transition-all" />
            </div>

            @if($catalogs->isEmpty())
                <div class="relative overflow-hidden rounded-2xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-[var(--ui-border)] shadow-sm shadow-black/5 p-10 text-center">
                    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-violet-500/40 to-transparent"></div>
                    <div class="flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-500/10 to-fuchsia-500/10 mx-auto mb-4">
                        @svg('heroicon-o-book-open', 'w-8 h-8 text-violet-500')
                    </div>
                    <h1 class="text-xl font-medium tracking-tight text-gray-900 dark:text-gray-100 mb-1">Noch keine Kataloge</h1>
                    <p class="text-sm text-[var(--ui-muted)] mb-6">Bündele Listen zu thematischen Sammlungen, die du oder dein Team durchlernen könnt.</p>
                    <x-ui-button variant="primary" wire:click="openCreateModal">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        Ersten Katalog anlegen
                    </x-ui-button>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($catalogs as $catalog)
                        <div class="group relative overflow-hidden rounded-xl bg-white/60 dark:bg-white/5 backdrop-blur-xl border border-black/5 dark:border-white/10 shadow-sm shadow-black/5 hover:-translate-y-0.5 hover:shadow-md transition-all duration-150">
                            <div class="absolute inset-x-0 top-0 h-1" style="background: linear-gradient(90deg, transparent, {{ $catalog->cover_color ?? '#8b5cf6' }}, transparent)"></div>
                            <a href="{{ route('vocab.catalogs.show', ['uuid' => $catalog->uuid]) }}" wire:navigate class="block p-5">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
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
                                    </div>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1 truncate">{{ $catalog->name }}</h3>
                                @if($catalog->description)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3 line-clamp-2">{{ $catalog->description }}</p>
                                @endif
                                <div class="flex items-center justify-between mt-3 pt-3 border-t border-black/5 dark:border-white/5">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $catalog->lists_count }} {{ $catalog->lists_count === 1 ? 'Liste' : 'Listen' }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $catalog->updated_at->diffForHumans() }}</span>
                                </div>
                            </a>
                            @if($catalog->isOwnedBy(auth()->id()))
                                <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-150 flex gap-1">
                                    <x-ui-confirm-button action="deleteCatalog" :value="$catalog->id" text="" confirmText="Löschen?" icon='<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>' size="sm" class="p-1.5 rounded-md bg-white/80 dark:bg-black/40 text-gray-500 dark:text-gray-400" />
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </x-ui-page-container>

    {{-- Create Catalog Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" wire:click.self="$set('showCreateModal', false)">
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-zinc-900 border border-black/5 dark:border-white/10 shadow-2xl">
                <form wire:submit="createCatalog" class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-medium text-gray-900 dark:text-gray-100">Neuer Katalog</h2>
                        <button type="button" wire:click="$set('showCreateModal', false)" class="text-gray-500 dark:text-gray-400 hover:text-gray-600">
                            @svg('heroicon-o-x-mark', 'w-5 h-5')
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                        <input type="text" wire:model="newName" autofocus required
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20" />
                        @error('newName') <div class="text-xs text-red-500 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Beschreibung (optional)</label>
                        <textarea wire:model="newDescription" rows="3"
                            class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20 resize-none"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Sichtbarkeit</label>
                            <select wire:model="newVisibility" class="w-full px-3 py-2 text-sm bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 focus:ring-2 focus:ring-violet-500/20">
                                <option value="team">Team</option>
                                <option value="personal">Persönlich</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Farbe</label>
                            <input type="color" wire:model="newCoverColor"
                                class="w-full h-[38px] px-1 py-1 bg-black/[0.03] dark:bg-white/5 rounded-lg border-0 cursor-pointer" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <x-ui-button type="button" variant="secondary-outline" size="sm" wire:click="$set('showCreateModal', false)">Abbrechen</x-ui-button>
                        <x-ui-button type="submit" variant="primary" size="sm">Anlegen</x-ui-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-ui-page>
</div>
