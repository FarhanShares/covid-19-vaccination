<?php

use App\Http\Controllers\RegistrationCompletedController;
use App\Http\Controllers\SearchController;
use App\Livewire\Register;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::post('/search', [SearchController::class, 'search'])->name('search.result');

Route::get('/register', Register::class)->name('register')->middleware('guest');
Route::get('/success', RegistrationCompletedController::class)->name('success');
