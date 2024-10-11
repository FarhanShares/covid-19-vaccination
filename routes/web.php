<?php

use App\Http\Controllers\SearchController;
use App\Livewire\Register;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
});
