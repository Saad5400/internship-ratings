<?php

use App\Models\Company;
use Illuminate\Support\Facades\Route;

// Public routes
Route::redirect('/', '/companies')->name('home');
Route::livewire('/companies', 'pages::companies.index')->name('companies.index');
Route::livewire('/companies/{company}', 'pages::companies.show')->name('companies.show');

// XML sitemap of public, indexable pages.
Route::get('/sitemap.xml', function () {
    $companies = Company::approved()
        ->select(['id', 'updated_at'])
        ->latest('updated_at')
        ->get();

    return response()
        ->view('sitemap', ['companies' => $companies])
        ->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::livewire('/ratings/create', 'pages::ratings.create')
    ->middleware(app()->isLocal() ? 'web' : 'throttle:10,60')
    ->name('ratings.create');

// Admin auth routes (from starter kit)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
