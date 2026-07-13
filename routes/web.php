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
use App\Http\Controllers\CepController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ClientLookupController;
use App\Http\Controllers\Omie\ContasReceberController as OmieContasReceberController;
use App\Http\Controllers\Omie\ContasPagarController as OmieContasPagarController;
use App\Http\Controllers\Omie\BoletosController as OmieBoletosController;


Route::redirect('/', '/login');

/*
|--------------------------------------------------------------------------
| API pública (SEM auth — paridade com o projeto de origem abac_admin)
|--------------------------------------------------------------------------
| Débito técnico documentado no plano de migração Omie: mover para trás de
| middleware('auth') quando confirmarmos que só o front interno consome.
*/

// Lookup de Client por CPF/CNPJ.
Route::get('/api/users/find', [ClientLookupController::class, 'findByDocument'])
    ->name('api.users.find');

// Integração Omie (financeiro).
Route::prefix('api/omie')->name('api.omie.')->group(function () {
    // Contas a Receber — implementação real
    Route::prefix('lancamentos/contas-receber')->group(function () {
        Route::post('/create', [OmieContasReceberController::class, 'store'])->name('cr.create');
        Route::post('/edit',   [OmieContasReceberController::class, 'update'])->name('cr.edit');
        Route::get('/find',    [OmieContasReceberController::class, 'show'])->name('cr.find');
        Route::post('/paid',   [OmieContasReceberController::class, 'pay'])->name('cr.paid');
        Route::post('/cancel', [OmieContasReceberController::class, 'destroy'])->name('cr.cancel');
    });

    // Contas a Pagar — stubs 501 (URLs preservadas, HTTP correto)
    Route::prefix('lancamentos/contas-pagar')->group(function () {
        Route::post('/create', [OmieContasPagarController::class, 'store'])->name('cp.create');
        Route::post('/edit',   [OmieContasPagarController::class, 'update'])->name('cp.edit');
        Route::get('/find',    [OmieContasPagarController::class, 'show'])->name('cp.find');
    });

    // Boletos Contas a Receber — stubs 501
    Route::prefix('boletos/contas-receber')->group(function () {
        Route::post('/create', [OmieBoletosController::class, 'store'])->name('bol.create');
        Route::post('/edit',   [OmieBoletosController::class, 'update'])->name('bol.edit');
        Route::post('/cancel', [OmieBoletosController::class, 'cancel'])->name('bol.cancel');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Consulta CEP (ViaCEP + OpenCEP fallback)
    Route::get('/api/cep/{cep}', [CepController::class, 'show'])->name('cep.show')->where('cep', '[0-9\-]+');

    Route::resource('users', UserController::class);
    Route::resource('clients', ClientController::class);

    // Perfis e permissões — somente Root
    Route::middleware('root')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::post('/roles/sync', [RoleController::class, 'sync'])->name('roles.sync');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Permissões individuais por usuário
        Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions.edit');
        Route::put('/users/{user}/permissions', [UserController::class, 'syncPermissions'])->name('users.permissions.update');
        Route::put('/users/{user}/role', [UserController::class, 'changeRole'])->name('users.role.update');
    });

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
    Route::put('/listas/{aba}/{id}', [ListaController::class, 'update'])->name('listas.update');
    Route::delete('/listas/{aba}/{id}', [ListaController::class, 'destroy'])->name('listas.destroy');
});
