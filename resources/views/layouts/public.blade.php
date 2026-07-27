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
                        <x-app-logo-icon class="size-8 rounded-lg" />
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
            <x-flash-messages />

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
