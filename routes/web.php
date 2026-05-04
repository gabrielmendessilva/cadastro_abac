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
use App\Http\Controllers\ClientFiliacaoController;
use App\Http\Controllers\ClientRedeSocialController;
use App\Http\Controllers\ClientContratoController;
use App\Http\Controllers\ClientSocioController;
use App\Http\Controllers\ClientJuridicoContatoController;
use App\Http\Controllers\ClientComiteController;
use App\Http\Controllers\ClientTagController;
use App\Http\Controllers\ListaController;


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

        // Histórico de filiações ABAC/SINAC
        Route::post('/filiacoes', [ClientFiliacaoController::class, 'store'])->name('filiacoes.store');
        Route::put('/filiacoes/{filiacao}', [ClientFiliacaoController::class, 'update'])->name('filiacoes.update');
        Route::delete('/filiacoes/{filiacao}', [ClientFiliacaoController::class, 'destroy'])->name('filiacoes.destroy');

        // Redes sociais
        Route::post('/redes-sociais', [ClientRedeSocialController::class, 'store'])->name('redes.store');
        Route::put('/redes-sociais/{rede}', [ClientRedeSocialController::class, 'update'])->name('redes.update');
        Route::delete('/redes-sociais/{rede}', [ClientRedeSocialController::class, 'destroy'])->name('redes.destroy');

        // Contratos
        Route::post('/contratos', [ClientContratoController::class, 'store'])->name('contratos.store');
        Route::put('/contratos/{contrato}', [ClientContratoController::class, 'update'])->name('contratos.update');
        Route::delete('/contratos/{contrato}', [ClientContratoController::class, 'destroy'])->name('contratos.destroy');

        // Sócios
        Route::post('/socios', [ClientSocioController::class, 'store'])->name('socios.store');
        Route::put('/socios/{socio}', [ClientSocioController::class, 'update'])->name('socios.update');
        Route::delete('/socios/{socio}', [ClientSocioController::class, 'destroy'])->name('socios.destroy');

        // Contatos jurídico/SINAC
        Route::post('/juridico-contatos', [ClientJuridicoContatoController::class, 'store'])->name('juridico.store');
        Route::put('/juridico-contatos/{contato}', [ClientJuridicoContatoController::class, 'update'])->name('juridico.update');
        Route::delete('/juridico-contatos/{contato}', [ClientJuridicoContatoController::class, 'destroy'])->name('juridico.destroy');

        // Comitês
        Route::post('/comites', [ClientComiteController::class, 'store'])->name('comites.store');
        Route::put('/comites/{comite}', [ClientComiteController::class, 'update'])->name('comites.update');
        Route::delete('/comites/{comite}', [ClientComiteController::class, 'destroy'])->name('comites.destroy');

        // Tags (sync)
        Route::post('/tags', [ClientTagController::class, 'sync'])->name('tags.sync');
    });

    // Listas (tabelas de domínio + relatórios)
    Route::get('/listas', [ListaController::class, 'index'])->name('listas.index');
    Route::post('/listas/{aba}', [ListaController::class, 'store'])->name('listas.store');
    Route::delete('/listas/{aba}/{id}', [ListaController::class, 'destroy'])->name('listas.destroy');
});
