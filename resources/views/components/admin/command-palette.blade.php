{{--
    Ctrl/Cmd-K command palette: fuzzy quick-nav plus live search across
    companies, ratings and users (via the admin.search JSON endpoint).
    Keyboard-first: ↑/↓ to move, Enter to open, Esc to close.
    No Alpine x-transition inside x-teleport (breaks x-show) — CSS animations.
--}}
<div
    x-data="{
        open: false,
        query: '',
        groups: [],
        flat: [],
        selected: 0,
        loading: false,
        timer: null,
        navItems: [
            { label: 'المراجعة', hint: 'الصفحة الرئيسية', url: '{{ route('admin.dashboard') }}' },
            { label: 'التقييمات', hint: 'صفحة', url: '{{ route('admin.ratings.index') }}' },
            { label: 'الجهات', hint: 'صفحة', url: '{{ route('admin.companies.index') }}' },
            { label: 'المستخدمون', hint: 'صفحة', url: '{{ route('admin.users.index') }}' },
            { label: 'عرض الموقع', hint: 'يفتح في تبويب جديد', url: '{{ route('home') }}', external: true },
        ],
        show() {
            this.open = true;
            this.query = '';
            this.rebuild([]);
            this.$nextTick(() => this.$refs.paletteInput?.focus());
        },
        rebuild(groups) {
            this.groups = groups;
            const nav = this.query.length === 0
                ? this.navItems
                : this.navItems.filter(item => item.label.includes(this.query));
            const flatNav = nav.length ? [{ title: 'التنقل', items: nav }] : [];
            this.groups = [...flatNav, ...groups];
            this.flat = this.groups.flatMap(group => group.items);
            this.selected = 0;
        },
        search() {
            clearTimeout(this.timer);
            this.timer = setTimeout(async () => {
                if (this.query.trim().length < 2) { this.rebuild([]); return; }
                this.loading = true;
                try {
                    const response = await fetch('{{ route('admin.search') }}?q=' + encodeURIComponent(this.query), { headers: { Accept: 'application/json' } });
                    const data = await response.json();
                    this.rebuild(data.groups ?? []);
                } finally {
                    this.loading = false;
                }
            }, 250);
        },
        move(delta) {
            if (! this.flat.length) return;
            this.selected = (this.selected + delta + this.flat.length) % this.flat.length;
            this.$nextTick(() => this.$refs.paletteList?.querySelector('[data-selected=true]')?.scrollIntoView({ block: 'nearest' }));
        },
        go(item) {
            const target = item ?? this.flat[this.selected];
            if (! target) return;
            this.open = false;
            if (target.external) { window.open(target.url, '_blank', 'noopener'); return; }
            window.Livewire?.navigate ? Livewire.navigate(target.url) : window.location.assign(target.url);
        },
    }"
    @keydown.window="if (($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); open ? open = false : show(); }"
>
    <button
        type="button"
        @click="show()"
        class="inline-flex items-center gap-2 rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 md:rounded-xl md:border md:border-slate-200 md:bg-white md:px-3 md:py-1.5 md:shadow-xs"
        aria-label="بحث سريع"
        title="بحث سريع (Ctrl+K)"
    >
        <svg class="size-5 md:size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <span class="hidden text-sm text-slate-400 md:inline">بحث...</span>
        <kbd class="hidden rounded-md border border-slate-200 bg-slate-50 px-1.5 text-[10px] font-semibold text-slate-400 md:inline" dir="ltr">Ctrl K</kbd>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            @keydown.escape.window="open = false"
            @keydown.arrow-down.prevent="move(1)"
            @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="go()"
            class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-[12vh] motion-safe:animate-overlay-in"
            role="dialog"
            aria-modal="true"
            aria-label="البحث السريع"
        >
            <div @click="open = false" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl motion-safe:animate-dialog-in">
                <div class="relative border-b border-slate-100">
                    <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-slate-400">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </span>
                    <input
                        x-ref="paletteInput"
                        x-model="query"
                        @input="search()"
                        type="text"
                        placeholder="ابحث عن جهة، تقييم، مستخدم، أو صفحة..."
                        class="w-full bg-transparent py-3.5 ps-12 pe-12 text-sm text-slate-900 placeholder-slate-400 focus:outline-none"
                        aria-label="البحث السريع"
                    />
                    <span x-show="loading" class="absolute inset-y-0 end-0 flex items-center pe-4 text-slate-400">
                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    </span>
                </div>

                <div x-ref="paletteList" class="max-h-80 overflow-y-auto p-2">
                    <template x-for="group in groups" :key="group.title">
                        <div class="mb-1">
                            <p class="px-3 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400" x-text="group.title"></p>
                            <template x-for="item in group.items" :key="item.url + item.label">
                                <button
                                    type="button"
                                    @click="go(item)"
                                    @mouseenter="selected = flat.indexOf(item)"
                                    :data-selected="flat.indexOf(item) === selected"
                                    :class="flat.indexOf(item) === selected ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50'"
                                    class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm transition-colors"
                                >
                                    <span class="truncate font-medium" x-text="item.label"></span>
                                    <span class="shrink-0 text-xs text-slate-400" x-text="item.hint"></span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <p x-show="! flat.length && query.trim().length >= 2 && ! loading" class="px-3 py-6 text-center text-sm text-slate-400">
                        لا توجد نتائج لـ«<span x-text="query"></span>»
                    </p>
                    <p x-show="! flat.length && query.trim().length < 2" class="px-3 py-6 text-center text-sm text-slate-400">
                        اكتب حرفين على الأقل للبحث في الجهات والتقييمات والمستخدمين
                    </p>
                </div>
            </div>
        </div>
    </template>
</div>
