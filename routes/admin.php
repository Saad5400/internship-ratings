<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetAdminLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
 * Admin panel routes. Loaded from web.php, so the `web` middleware group is
 * already applied. The panel is Livewire pages under /admin behind the single
 * `is_admin` gate (EnsureUserIsAdmin); guests are redirected to `login`.
 */
Route::prefix('admin')->middleware(SetAdminLocale::class)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::livewire('/login', 'pages::admin.login')->name('login');
    });

    // Invited admins set their own password via a temporary signed link.
    Route::livewire('/setup/{user}', 'pages::admin.setup')
        ->middleware('signed')
        ->name('admin.setup');

    Route::middleware(['auth', 'auth.session', EnsureUserIsAdmin::class])->name('admin.')->group(function () {
        Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

        Route::livewire('/ratings', 'pages::admin.ratings.index')->name('ratings.index');
        Route::livewire('/ratings/{rating}/edit', 'pages::admin.ratings.edit')->name('ratings.edit');

        Route::livewire('/companies', 'pages::admin.companies.index')->name('companies.index');
        Route::livewire('/companies/{company}', 'pages::admin.companies.show')->name('companies.show');

        Route::livewire('/users', 'pages::admin.users.index')->name('users.index');

        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        })->name('logout');
    });
});
