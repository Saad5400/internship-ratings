<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta name="theme-color" content="#f8fafc">
        <script data-navigate-once>
            (function () {
                {{-- wire:navigate swaps in a server-rendered <html> without the dark class, so re-apply on every navigation --}}
                var applyTheme = function () {
                    var isDark = localStorage.getItem('theme') === 'dark';
                    document.documentElement.classList.toggle('dark', isDark);
                    var themeColorMeta = document.querySelector('meta[name="theme-color"]');
                    if (themeColorMeta) {
                        themeColorMeta.setAttribute('content', isDark ? '#020617' : '#f8fafc');
                    }
                };
                applyTheme();
                document.addEventListener('livewire:navigated', applyTheme);
            })();
        </script>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50/50 font-sans antialiased text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <nav class="sticky top-0 z-40 border-b border-slate-200/60 bg-white/80 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/80">
            <div class="max-w-5xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold text-slate-900 transition-opacity hover:opacity-80 dark:text-slate-100">
                        <x-app-logo-icon class="size-8 rounded-lg" />
                        تقييم التدريب
                    </a>
                    <div class="flex items-center gap-1 sm:gap-2">
                        <a href="{{ route('ratings.create') }}" wire:navigate
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-xs transition-all hover:bg-blue-600 hover:shadow-sm active:scale-[0.98]">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            أضف تقييم
                        </a>
                        <button
                            type="button"
                            x-data="{ dark: localStorage.getItem('theme') === 'dark' }"
                            x-on:click="
                                dark = !dark;
                                document.documentElement.classList.toggle('dark', dark);
                                localStorage.setItem('theme', dark ? 'dark' : 'light');
                                let themeColorMeta = document.querySelector('meta[name=theme-color]');
                                if (themeColorMeta) {
                                    themeColorMeta.setAttribute('content', dark ? '#020617' : '#f8fafc');
                                }
                            "
                            class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                            aria-label="تبديل المظهر"
                        >
                            <svg x-show="!dark" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
                            <svg x-show="dark" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="min-h-[calc(100vh-16rem)] max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
            <x-flash-messages />

            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200/60 bg-white mt-12 dark:border-slate-800 dark:bg-slate-900">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="max-w-md">
                        <div class="flex items-center gap-2 text-base font-bold text-slate-900 dark:text-slate-100">
                            <x-app-logo-icon class="size-7 rounded-lg" />
                            تقييم التدريب
                        </div>
                        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                            منصّة مستقلة لتقييم جهات التدريب التعاوني والصيفي، بتجارب حقيقية من المتدربين أنفسهم.
                        </p>
                    </div>
                    <div class="flex items-center gap-4 text-sm font-medium text-slate-500 dark:text-slate-400">
                        <a href="{{ route('companies.index') }}" wire:navigate class="transition-colors hover:text-slate-900 dark:hover:text-slate-100">
                            الجهات
                        </a>
                        <a href="{{ route('ratings.create') }}" wire:navigate class="transition-colors hover:text-slate-900 dark:hover:text-slate-100">
                            أضف تقييم
                        </a>
                    </div>
                </div>
                <div class="border-t border-slate-100 pt-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-400 dark:border-slate-800 dark:text-slate-500">
                    <p>تقييم التدريب &copy; {{ date('Y') }}</p>
                    <p>صُنع بحب لمجتمع المتدربين</p>
                </div>
            </div>
        </footer>
        @if(config('turnstile.enabled'))
            <script data-navigate-once>window.onTurnstileReady = () => window.dispatchEvent(new Event('turnstile-loaded'));</script>
            <script data-navigate-once src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileReady&render=explicit" async defer></script>
        @endif
    </body>
</html>
