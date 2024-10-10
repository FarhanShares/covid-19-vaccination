<?php

use App\Livewire\Register;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
});
