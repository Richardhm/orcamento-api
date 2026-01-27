<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrcamentoApiController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class,'login'])->name('api.login');

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});
Route::middleware('auth:sanctum')->patch('/me', [OrcamentoApiController::class, 'update']); // Para atualizar os dados

Route::prefix('orcamento')->group(function () {
    Route::get('/estados', [OrcamentoApiController::class, 'getEstados']);
    Route::post('/cidades', [OrcamentoApiController::class, 'getCidades']);
    Route::post('/planos', [OrcamentoApiController::class, 'getPlanos']);
    Route::post('/cotacao', [OrcamentoApiController::class, 'calcularCotacao']);
    Route::post('/administradoras', [OrcamentoApiController::class, 'filtrarAdministradora']);
    Route::post('/gerar-documento', [OrcamentoApiController::class, 'criarDocumento'])->name('api.gerar.documento');
    Route::post('/tabelacompleta', [OrcamentoApiController::class, 'criarTabelaCompleta'])->name('api.tabela.completa');
    Route::post('/completa', [OrcamentoApiController::class, 'completa'])->name('api.tabela.completa'); 
});















