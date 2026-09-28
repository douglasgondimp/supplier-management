<?php

use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::resource('suppliers', SupplierController::class);
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
