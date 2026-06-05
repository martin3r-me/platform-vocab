<div>
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Vokabeln
    </div>

    <x-ui-sidebar-list label="Navigation">
        <x-ui-sidebar-item :href="route('vocab.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('vocab.lists.index')">
            @svg('heroicon-o-list-bullet', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Listen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    @if(isset($recentLists) && $recentLists->isNotEmpty())
    <x-ui-sidebar-list label="Letzte Listen">
        @foreach($recentLists as $list)
            <x-ui-sidebar-item :href="route('vocab.lists.show', ['uuid' => $list->uuid])">
                @svg('heroicon-o-book-open', 'w-4 h-4 text-[var(--ui-secondary)]')
                <span class="ml-2 text-sm truncate">{{ $list->name }}</span>
            </x-ui-sidebar-item>
        @endforeach
    </x-ui-sidebar-list>
    @endif

    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('vocab.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('vocab.lists.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-list-bullet', 'w-5 h-5')
            </a>
        </div>
    </div>
</div>
