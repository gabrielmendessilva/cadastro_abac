<?php

use App\Http\Controllers\ClientLookupController;
use App\Http\Controllers\Omie\BoletosController;
use App\Http\Controllers\Omie\ContasPagarController;
use App\Http\Controllers\Omie\ContasReceberController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Registradas via bootstrap/app.php → withRouting(api: ...). Recebem
| automaticamente prefixo `/api` e o middleware group `api`
| (SubstituteBindings + throttle:api), sem sessão nem CSRF.
|
| ⚠️ SEM AUTENTICAÇÃO — paridade com abac_admin. Débito técnico:
| trocar para auth:sanctum + permissão spatie quando confirmarmos o
| perfil de consumidor. Ver plano de migração Omie.
*/

// Lookup público de Client por CPF/CNPJ.
Route::get('/users/find', [ClientLookupController::class, 'findByDocument'])
    ->name('api.users.find');

// Integração Omie (financeiro).
Route::prefix('omie')->name('api.omie.')->group(function () {
    // Contas a Receber — implementação real
    Route::prefix('lancamentos/contas-receber')->group(function () {
        Route::post('/create', [ContasReceberController::class, 'store'])->name('cr.create');
        Route::post('/edit',   [ContasReceberController::class, 'update'])->name('cr.edit');
        Route::get('/find',    [ContasReceberController::class, 'show'])->name('cr.find');
        Route::post('/paid',   [ContasReceberController::class, 'pay'])->name('cr.paid');
        Route::post('/cancel', [ContasReceberController::class, 'destroy'])->name('cr.cancel');
    });

    // Contas a Pagar — stubs 501 (URLs preservadas)
    Route::prefix('lancamentos/contas-pagar')->group(function () {
        Route::post('/create', [ContasPagarController::class, 'store'])->name('cp.create');
        Route::post('/edit',   [ContasPagarController::class, 'update'])->name('cp.edit');
        Route::get('/find',    [ContasPagarController::class, 'show'])->name('cp.find');
    });

    // Boletos Contas a Receber — stubs 501
    Route::prefix('boletos/contas-receber')->group(function () {
        Route::post('/create', [BoletosController::class, 'store'])->name('bol.create');
        Route::post('/edit',   [BoletosController::class, 'update'])->name('bol.edit');
        Route::post('/cancel', [BoletosController::class, 'cancel'])->name('bol.cancel');
    });
});
