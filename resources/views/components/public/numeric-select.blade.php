@props([
    'label',
    'name',
    'options' => [],
    'placeholder' => 'ابحث بالأرقام...',
    'required' => false,
])

<x-public.form-field :label="$label" :name="$name" :required="$required">
    <div
        x-data="{
            value: @entangle($attributes->wire('model')).live,
            open: false,
            query: '',
            options: @js($options),
            get selectedOption() {
                return this.options.find((option) => String(option.id) === String(this.value)) ?? null;
            },
            get filteredOptions() {
                const query = this.query.trim();

                if (query === '') {
                    return this.options;
                }

                return this.options.filter((option) => String(option.name).includes(query));
            },
            openMenu() {
                this.open = true;

                this.$nextTick(() => this.$refs.search?.focus());
            },
            closeMenu() {
                this.open = false;
                this.query = '';
            },
            selectOption(option) {
                this.value = option.id;
                this.closeMenu();
            },
            clearValue() {
                this.value = null;
                this.openMenu();
            },
        }"
        class="relative"
        @click.outside="closeMenu()"
        @keydown.escape.stop="closeMenu()"
    >
        <button
            x-show="selectedOption && !open"
            x-cloak
            type="button"
            @click="openMenu()"
            class="flex min-h-11 w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-xs transition-all hover:border-slate-300 focus:outline-none focus-visible:border-blue-500 focus-visible:ring-2 focus-visible:ring-blue-500/20"
        >
            <span class="inline-flex max-w-full items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-sm font-medium text-slate-800">
                <span x-text="selectedOption?.name"></span>
                <span
                    @click.stop="clearValue()"
                    class="inline-flex shrink-0 rounded-full text-slate-400 transition-colors hover:text-red-600"
                    aria-label="مسح الاختيار"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </span>
            </span>
            <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
            </svg>
        </button>

        <div
            x-show="open || !selectedOption"
            x-cloak
            class="rounded-lg border border-slate-200 bg-white shadow-xs transition-all focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/20"
        >
            <div class="flex items-center gap-2 px-3 py-2">
                <input
                    x-ref="search"
                    id="{{ $name }}_search"
                    type="number"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    x-model="query"
                    @focus="open = true"
                    @input="open = true"
                    @keydown.enter.prevent="filteredOptions[0] && selectOption(filteredOptions[0])"
                    placeholder="{{ $placeholder }}"
                    class="w-full border-0 bg-transparent p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0"
                />

                <button
                    x-show="value !== null"
                    x-cloak
                    type="button"
                    @click="clearValue()"
                    class="inline-flex shrink-0 rounded-full text-slate-400 transition-colors hover:text-red-600"
                    aria-label="مسح الاختيار"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div x-show="open" x-cloak class="border-t border-slate-200">
                <div class="max-h-56 overflow-y-auto py-1">
                    <template x-if="filteredOptions.length === 0">
                        <p class="px-3 py-2 text-sm text-slate-500">لا توجد نتائج.</p>
                    </template>

                    <template x-for="option in filteredOptions" :key="option.id">
                        <button
                            type="button"
                            @click="selectOption(option)"
                            class="flex w-full items-center justify-between px-3 py-2 text-right text-sm transition-colors hover:bg-slate-50"
                            :class="String(option.id) === String(value) ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-700'"
                        >
                            <span x-text="option.name"></span>
                            <svg
                                x-show="String(option.id) === String(value)"
                                class="size-4 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-public.form-field>
