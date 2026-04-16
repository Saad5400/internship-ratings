<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50/50 font-sans antialiased text-slate-900">
        <nav class="sticky top-0 z-40 border-b border-slate-200/60 bg-white/80 backdrop-blur-md">
            <div class="max-w-5xl mx-auto px-4 sm:px-6">
                <div class="flex items-center justify-between h-16">
                    <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold text-slate-900 transition-opacity hover:opacity-80">
                        <span class="flex size-8 items-center justify-center rounded-lg bg-blue-500 text-white">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </span>
                        تقييم التدريب
                    </a>
                    <div class="flex items-center gap-2 sm:gap-3">
{{--                        <a href="{{ route('companies.index') }}" wire:navigate--}}
{{--                            class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 {{ request()->routeIs('companies.*') ? 'bg-slate-100 text-slate-900' : '' }}">--}}
{{--                            الجهات--}}
{{--                        </a>--}}
                        <a href="{{ route('ratings.create') }}" wire:navigate
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-xs transition-all hover:bg-blue-600 hover:shadow-sm active:scale-[0.98]">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            أضف تقييم
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-10">
            @if(session('success'))
                <div role="status"
                    x-data="{ show: true }"
                    x-init="setTimeout(() => show = false, 6000)"
                    x-show="show"
                    x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-300"
                    x-transition:enter-start="motion-safe:opacity-0 motion-safe:-translate-y-2"
                    x-transition:enter-end="motion-safe:opacity-100 motion-safe:translate-y-0"
                    x-transition:leave="motion-safe:transition motion-safe:ease-in motion-safe:duration-200"
                    x-transition:leave-start="motion-safe:opacity-100"
                    x-transition:leave-end="motion-safe:opacity-0"
                    class="mb-6 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    <svg class="size-5 shrink-0 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button type="button" @click="show = false" class="shrink-0 rounded text-green-600/70 transition-colors hover:text-green-800" aria-label="إغلاق">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div role="alert"
                    x-data="{ show: true }"
                    x-init="setTimeout(() => show = false, 8000)"
                    x-show="show"
                    x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-300"
                    x-transition:enter-start="motion-safe:opacity-0 motion-safe:-translate-y-2"
                    x-transition:enter-end="motion-safe:opacity-100 motion-safe:translate-y-0"
                    x-transition:leave="motion-safe:transition motion-safe:ease-in motion-safe:duration-200"
                    x-transition:leave-start="motion-safe:opacity-100"
                    x-transition:leave-end="motion-safe:opacity-0"
                    class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <svg class="size-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button type="button" @click="show = false" class="shrink-0 rounded text-red-600/70 transition-colors hover:text-red-800" aria-label="إغلاق">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif

            {{ $slot }}
        </main>

        <footer class="border-t border-slate-200/60 bg-white mt-12">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
                <p class="text-sm text-slate-400 text-center">
                    تقييم التدريب &copy; {{ date('Y') }}
                </p>
            </div>
        </footer>
        @if(config('turnstile.enabled'))
            <script data-navigate-once>window.onTurnstileReady = () => window.dispatchEvent(new Event('turnstile-loaded'));</script>
            <script data-navigate-once src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileReady&render=explicit" async defer></script>
        @endif
    </body>
</html>
