<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientContactController;
use App\Http\Controllers\ClientOpcionalController;
use App\Http\Controllers\ClientAddressController;


Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('users', UserController::class);
    Route::resource('clients', ClientController::class);

    Route::resource('documents', DocumentController::class);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');

    Route::prefix('clients/{client}')->name('clients.')->group(function () {
        Route::post('/documents', [ClientDocumentController::class, 'store'])->name('documents.store');
        Route::delete('/documents/{document}', [ClientDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::get('/documents/{document}/download', [ClientDocumentController::class, 'download'])->name('documents.download');

        Route::post('/contacts', [ClientContactController::class, 'store'])->name('contacts.store');
        Route::put('/contacts/{contact}', [ClientContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [ClientContactController::class, 'destroy'])->name('contacts.destroy');

        Route::post('/opcionais', [ClientOpcionalController::class, 'store'])->name('opcionais.store');
        Route::put('/opcionais/{opcional}', [ClientOpcionalController::class, 'update'])->name('opcionais.update');
        Route::delete('/opcionais/{opcional}', [ClientOpcionalController::class, 'destroy'])->name('opcionais.destroy');

        Route::post('/addresses', [ClientAddressController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{address}', [ClientAddressController::class, 'update'])->name('addresses.update');
        Route::delete('/addresses/{address}', [ClientAddressController::class, 'destroy'])->name('addresses.destroy');
    });
});
