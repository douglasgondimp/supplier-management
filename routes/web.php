<?php

use App\Http\Controllers\ApiDataQueryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::get('api-data/zip-code', [ApiDataQueryController::class, 'zipCode'])->name('api-data.zip-code');
    Route::get('api-data/cnpj', [ApiDataQueryController::class, 'cnpj'])->name('api-data.cnpj');
    Route::get('api-data/estados/{estado:sigla}/cidades', [LocationController::class, 'cidades'])->name('api-data.cidades');
    Route::resource('suppliers', SupplierController::class);
});

require __DIR__.'/auth.php';
require __DIR__.'/settings.php';
