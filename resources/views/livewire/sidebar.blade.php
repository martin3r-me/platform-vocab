<div>
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Vokabeln
    </div>

    <x-ui-sidebar-list label="Navigation">
        <x-ui-sidebar-item :href="route('vocab.dashboard')" :active="request()->routeIs('vocab.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('vocab.lists.index')" :active="request()->routeIs('vocab.lists.index')">
            @svg('heroicon-o-list-bullet', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Listen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

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
            <a href="{{ route('vocab.lists.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] {{ request()->routeIs('vocab.lists.index') ? 'bg-[var(--ui-primary-5)] text-[var(--ui-primary)]' : '' }}">
                @svg('heroicon-o-list-bullet', 'w-5 h-5')
            </a>
        </div>
    </div>
</div>
