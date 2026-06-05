<div>
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Vokabeln
    </div>

    <x-ui-sidebar-list label="Navigation">
        <x-ui-sidebar-item :href="route('vocab.dashboard')" :active="request()->routeIs('vocab.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('vocab.catalogs.index')" :active="request()->routeIs('vocab.catalogs.*')">
            @svg('heroicon-o-rectangle-stack', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Bibliothek</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('vocab.lists.index')" :active="request()->routeIs('vocab.lists.index')">
            @svg('heroicon-o-list-bullet', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Listen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('vocab.review')" :active="request()->routeIs('vocab.review')">
            @svg('heroicon-o-bolt', 'w-4 h-4 text-emerald-500')
            <span class="ml-2 text-sm">Wiederholen</span>
            @if(($dueCount ?? 0) > 0)
                <x-slot name="trailing">
                    <x-ui-badge variant="success" size="xs">{{ $dueCount }}</x-ui-badge>
                </x-slot>
            @endif
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    @if(isset($enrolledLists) && $enrolledLists->isNotEmpty())
        <x-ui-sidebar-list label="Meine Listen">
            @foreach($enrolledLists as $list)
                <x-ui-sidebar-item :href="route('vocab.lists.show', ['uuid' => $list->uuid])" :active="request()->is('*/vocab/lists/' . $list->uuid)">
                    @svg('heroicon-o-bookmark', 'w-4 h-4 text-emerald-500')
                    <span class="ml-2 text-sm truncate">{{ $list->name }}</span>
                    <x-slot name="trailing">
                        <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">{{ $list->mastery_pct }}%</span>
                    </x-slot>
                </x-ui-sidebar-item>
            @endforeach
        </x-ui-sidebar-list>
    @endif

    @if($groupedLists->isNotEmpty())
        @foreach($groupedLists as $langPair => $lists)
            <x-ui-sidebar-list :label="$langPair">
                @foreach($lists as $list)
                    <x-ui-sidebar-item :href="route('vocab.lists.show', ['uuid' => $list->uuid])" :active="request()->is('*/vocab/lists/' . $list->uuid)">
                        @svg('heroicon-o-book-open', 'w-4 h-4 text-[var(--ui-secondary)]')
                        <span class="ml-2 text-sm truncate">{{ $list->name }}</span>
                        <x-slot name="trailing">
                            <x-ui-badge variant="muted" size="xs">{{ $list->entries_count }}</x-ui-badge>
                        </x-slot>
                    </x-ui-sidebar-item>
                @endforeach
            </x-ui-sidebar-list>
        @endforeach
    @endif

    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('vocab.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] {{ request()->routeIs('vocab.dashboard') ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)]' : '' }}">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('vocab.catalogs.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] {{ request()->routeIs('vocab.catalogs.*') ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)]' : '' }}">
                @svg('heroicon-o-rectangle-stack', 'w-5 h-5')
            </a>
            <a href="{{ route('vocab.lists.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] {{ request()->routeIs('vocab.lists.index') ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)]' : '' }}">
                @svg('heroicon-o-list-bullet', 'w-5 h-5')
            </a>
            <a href="{{ route('vocab.review') }}" wire:navigate class="relative flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] {{ request()->routeIs('vocab.review') ? 'bg-emerald-500/10 text-emerald-600' : '' }}">
                @svg('heroicon-o-bolt', 'w-5 h-5')
                @if(($dueCount ?? 0) > 0)
                    <span class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 rounded-full text-[9px] font-semibold bg-emerald-500 text-white flex items-center justify-center leading-none">{{ $dueCount > 99 ? '99+' : $dueCount }}</span>
                @endif
            </a>
        </div>
    </div>
</div>
