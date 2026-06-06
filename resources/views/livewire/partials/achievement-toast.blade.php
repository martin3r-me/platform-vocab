{{-- Achievement toast — listens to "achievement-earned" Livewire event and slides in from right --}}
<div
    x-data="{
        toasts: [],
        idCounter: 0,
        push(detail) {
            const id = ++this.idCounter;
            this.toasts.push({ id, ...detail });
            setTimeout(() => this.dismiss(id), 5000);
        },
        dismiss(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @achievement-earned.window="push($event.detail)"
    class="fixed top-4 right-4 z-[60] flex flex-col gap-2 pointer-events-none"
    style="max-width: 360px"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="relative pointer-events-auto rounded-xl bg-white dark:bg-zinc-900 border shadow-xl overflow-hidden"
            :class="{
                'border-amber-500/40': t.tier === 'bronze',
                'border-slate-400/50': t.tier === 'silver',
                'border-yellow-500/60': t.tier === 'gold',
            }"
        >
            <div class="absolute inset-x-0 top-0 h-0.5"
                :class="{
                    'bg-gradient-to-r from-amber-600 via-amber-400 to-amber-600': t.tier === 'bronze',
                    'bg-gradient-to-r from-slate-400 via-slate-300 to-slate-400': t.tier === 'silver',
                    'bg-gradient-to-r from-yellow-500 via-yellow-300 to-yellow-500': t.tier === 'gold',
                }"
            ></div>
            <div class="p-4 flex items-start gap-3">
                <div class="flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center"
                    :class="{
                        'bg-amber-500/15 text-amber-600': t.tier === 'bronze',
                        'bg-slate-400/15 text-slate-500': t.tier === 'silver',
                        'bg-yellow-500/15 text-yellow-600': t.tier === 'gold',
                    }"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" /></svg>
                </div>
                <div class="flex-1 min-w-0 pt-0.5">
                    <div class="text-[10px] uppercase tracking-wider font-semibold mb-0.5"
                        :class="{
                            'text-amber-600 dark:text-amber-400': t.tier === 'bronze',
                            'text-slate-500 dark:text-slate-300': t.tier === 'silver',
                            'text-yellow-600 dark:text-yellow-400': t.tier === 'gold',
                        }"
                        x-text="t.tier === 'gold' ? 'Gold-Achievement' : t.tier === 'silver' ? 'Silver-Achievement' : 'Bronze-Achievement'"
                    ></div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="t.name"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="t.description"></div>
                </div>
                <button @click="dismiss(t.id)" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>
    </template>
</div>
