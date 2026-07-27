<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-50/50 font-sans antialiased text-slate-900">
        @php
            /*
             * The panel's primary navigation, declared once and rendered twice
             * (desktop topbar + mobile bottom tab bar). Items appear only once
             * their route exists, so migration phases light up progressively.
             * The review badge = everything waiting for moderation, surfaced on
             * the front door because clearing the queue is the daily job.
             */
            $pendingReviewCount = \App\Models\Rating::pending()->count() + \App\Models\Company::pending()->count();

            $navItems = collect([
                [
                    'route' => 'admin.dashboard',
                    'active' => 'admin.dashboard',
                    'label' => 'المراجعة',
                    'badge' => $pendingReviewCount,
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                ],
                [
                    'route' => 'admin.ratings.index',
                    'active' => 'admin.ratings.*',
                    'label' => 'التقييمات',
                    'badge' => null,
                    'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                ],
                [
                    'route' => 'admin.companies.index',
                    'active' => 'admin.companies.*',
                    'label' => 'الجهات',
                    'badge' => null,
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                ],
                [
                    'route' => 'admin.users.index',
                    'active' => 'admin.users.*',
                    'label' => 'المستخدمون',
                    'badge' => null,
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                ],
            ])->filter(fn (array $item) => \Illuminate\Support\Facades\Route::has($item['route']));
        @endphp

        <header class="sticky top-0 z-40 border-b border-slate-200/60 bg-white/80 backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between gap-4">
                    <div class="flex items-center gap-6">
                        <a href="{{ route('admin.dashboard') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold text-slate-900 transition-opacity hover:opacity-80">
                            <x-app-logo-icon class="size-8 rounded-lg" />
                            <span class="hidden sm:inline">تقييم التدريب</span>
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">الإدارة</span>
                        </a>

                        <nav class="hidden items-center gap-1 md:flex" aria-label="التنقل الرئيسي">
                            @foreach($navItems as $item)
                                <a href="{{ route($item['route']) }}" wire:navigate
                                    @if(request()->routeIs($item['active'])) aria-current="page" @endif
                                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs($item['active']) ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                    {{ $item['label'] }}
                                    @if(($item['badge'] ?? 0) > 0)
                                        <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-amber-100 px-1.5 text-xs font-semibold text-amber-700">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    <div class="flex items-center gap-1 sm:gap-2">
                        <a href="{{ route('home') }}" target="_blank" rel="noopener"
                            class="rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                            title="عرض الموقع" aria-label="عرض الموقع">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>

                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" @click.outside="open = false" @keydown.escape.window="open = false"
                                class="flex items-center gap-2 rounded-full p-1 transition-colors hover:bg-slate-100"
                                aria-label="قائمة المستخدم" :aria-expanded="open">
                                <span class="flex size-8 items-center justify-center rounded-full bg-blue-500 text-sm font-semibold text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </button>

                            <div x-show="open" x-cloak
                                x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-150"
                                x-transition:enter-start="motion-safe:opacity-0 motion-safe:scale-95"
                                x-transition:enter-end="motion-safe:opacity-100 motion-safe:scale-100"
                                class="absolute end-0 top-full z-50 mt-2 w-60 origin-top-left rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-xs text-slate-500" dir="ltr">{{ auth()->user()->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.logout') }}" class="p-1">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        تسجيل الخروج
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 pb-24 pt-6 sm:px-6 sm:pt-8 md:pb-10">
            <x-flash-messages />

            {{ $slot }}
        </main>

        {{-- Mobile bottom tab bar: primary nav in the thumb zone --}}
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200/60 bg-white/95 backdrop-blur-md md:hidden"
            style="padding-bottom: env(safe-area-inset-bottom)"
            aria-label="التنقل الرئيسي للجوال">
            <div class="grid auto-cols-fr grid-flow-col">
                @foreach($navItems as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate
                        @if(request()->routeIs($item['active'])) aria-current="page" @endif
                        class="flex flex-col items-center gap-1 py-2.5 text-[11px] font-medium transition-colors {{ request()->routeIs($item['active']) ? 'text-blue-600' : 'text-slate-500 hover:text-slate-900' }}">
                        <span class="relative">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            @if(($item['badge'] ?? 0) > 0)
                                <span class="absolute -top-1 -start-2 flex min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold leading-4 text-white">{{ $item['badge'] }}</span>
                            @endif
                        </span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    </body>
</html>
