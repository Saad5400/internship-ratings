<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetAdminLocale;
use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use App\Support\ModerationStatus;
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

    /*
     * Invited admins set their own password via a temporary signed link.
     * Relative signatures on purpose: the link must survive host/port
     * differences (localhost:8000 vs APP_URL, proxies in production) —
     * generate with `absolute: false` and prefix with url() for display.
     */
    Route::livewire('/setup/{user}', 'pages::admin.setup')
        ->middleware('signed:relative')
        ->name('admin.setup');

    Route::middleware(['auth', 'auth.session', EnsureUserIsAdmin::class])->name('admin.')->group(function () {
        Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');

        Route::livewire('/ratings', 'pages::admin.ratings.index')->name('ratings.index');
        Route::livewire('/ratings/{rating}/edit', 'pages::admin.ratings.edit')->name('ratings.edit');

        Route::livewire('/companies', 'pages::admin.companies.index')->name('companies.index');
        Route::livewire('/companies/{company}', 'pages::admin.companies.show')->name('companies.show');

        Route::livewire('/users', 'pages::admin.users.index')->name('users.index');

        // JSON backend for the Ctrl/Cmd-K command palette.
        Route::get('/search', function (Request $request) {
            $term = trim((string) $request->query('q', ''));

            if (mb_strlen($term) < 2) {
                return response()->json(['groups' => []]);
            }

            $like = '%'.$term.'%';

            $companies = Company::searchByName($term)
                ->orderByDesc('created_at')
                ->take(5)
                ->get(['id', 'name', 'status'])
                ->map(fn ($company) => [
                    'label' => $company->name,
                    'hint' => ModerationStatus::label($company->status),
                    'url' => route('admin.companies.show', $company),
                ]);

            $ratings = Rating::with('company:id,name')
                ->where(function ($query) use ($like, $term) {
                    $query->where('role_title', 'like', $like)
                        ->orWhere('reviewer_name', 'like', $like)
                        ->orWhere('city', 'like', $like)
                        ->orWhere('department', 'like', $like)
                        ->orWhereHas('company', fn ($sub) => $sub->searchByName($term));
                })
                ->orderByDesc('created_at')
                ->take(5)
                ->get()
                ->map(fn ($rating) => [
                    'label' => ($rating->role_title ?: 'تقييم').' — '.($rating->company?->name ?? ''),
                    'hint' => ModerationStatus::label($rating->status),
                    'url' => route('admin.ratings.edit', $rating),
                ]);

            $users = User::query()
                ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->take(5)
                ->get(['id', 'name', 'email'])
                ->map(fn ($user) => [
                    'label' => $user->name,
                    'hint' => $user->email,
                    'url' => route('admin.users.index', ['q' => $user->email]),
                ]);

            return response()->json([
                'groups' => collect([
                    ['title' => 'الجهات', 'items' => $companies],
                    ['title' => 'التقييمات', 'items' => $ratings],
                    ['title' => 'المستخدمون', 'items' => $users],
                ])->filter(fn ($group) => $group['items']->isNotEmpty())->values(),
            ]);
        })->name('search');

        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        })->name('logout');
    });
});
