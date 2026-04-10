<?php

use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::redirect('/', '/companies')->name('home');
Route::livewire('/companies', 'pages::companies.index')->name('companies.index');
Route::get('/companies/{company}', [CompanyController::class, 'show'])->name('companies.show');

Route::livewire('/ratings/create', 'pages::ratings.create')
    ->middleware('throttle:10,60')
    ->name('ratings.create');

// Admin auth routes (from starter kit)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
