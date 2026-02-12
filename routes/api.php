<?php
// routes/api.php - CORRIGIDO

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File; // ✅ ADICIONAR IMPORT
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrcamentoApiController;
use App\Http\Controllers\Api\DataGeneratorController;
use App\Http\Controllers\Api\UserController;

// ===== ROTAS PÚBLICAS (SEM AUTENTICAÇÃO) =====
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::prefix('data-generator')->name('api.data.')->group(function () {
    
	/********************CARENCIAS***************************/
	Route::get('/carencias/preview', [DataGeneratorController::class, 'visualizarCarencias'])->name('carencias.preview');
    Route::get('/cidades/mapeamento', [DataGeneratorController::class, 'visualizarMapeamentoCidades'])->name('cidades.mapeamento'); // ✅ NOVA
    Route::get('/carencias/gerar', [DataGeneratorController::class, 'gerarCarencias'])->name('carencias.gerar');
    Route::get('/gerar-todos', [DataGeneratorController::class, 'gerarTodos'])->name('gerar.todos');
    Route::get('/listar-arquivos', [DataGeneratorController::class, 'listarArquivos'])->name('listar.arquivos');
	Route::get('/carencias/debug', [DataGeneratorController::class, 'debugCarencias'])->name('carencias.debug'); // ✅ NOVA
	
	
	/*****************************CIDADES*********************************************/
	 Route::get('/cidades/preview', [DataGeneratorController::class, 'visualizarCidades'])->name('cidades.preview');
    Route::get('/cidades/gerar', [DataGeneratorController::class, 'gerarCidades'])->name('cidades.gerar');
    
    // Outros
    Route::get('/cidades/mapeamento', [DataGeneratorController::class, 'visualizarMapeamentoCidades'])->name('cidades.mapeamento');
    Route::post('/gerar-todos', [DataGeneratorController::class, 'gerarTodos'])->name('gerar.todos');
    Route::get('/listar-arquivos', [DataGeneratorController::class, 'listarArquivos'])->name('listar.arquivos');
	
	/*************************************************************************************/
	 // ✅ NOVAS ROTAS PARA DESCONTOS
    Route::get('/descontos/preview', [DataGeneratorController::class, 'visualizarDescontos'])->name('descontos.preview');
    Route::get('/descontos/debug', [DataGeneratorController::class, 'debugDescontos'])->name('descontos.debug');
    Route::get('/descontos/gerar', [DataGeneratorController::class, 'gerarDescontos'])->name('descontos.gerar');
	
	// ✅ NOVAS ROTAS PARA PDFS
    Route::get('/pdfs/preview', [DataGeneratorController::class, 'visualizarPdfs'])->name('pdfs.preview');
    Route::get('/pdfs/debug', [DataGeneratorController::class, 'debugPdfs'])->name('pdfs.debug');
    Route::get('/pdfs/gerar', [DataGeneratorController::class, 'gerarPdfs'])->name('pdfs.gerar');
	
	// ✅ NOVAS ROTAS PARA PLANOS DATA
    Route::get('/planos-data/preview', [DataGeneratorController::class, 'visualizarPlanosData'])->name('planos.data.preview');
    Route::get('/planos-data/debug', [DataGeneratorController::class, 'debugPlanosData'])->name('planos.data.debug');
    Route::get('/planos-data/gerar', [DataGeneratorController::class, 'gerarPlanosData'])->name('planos.data.gerar');
	
	// ✅ NOVAS ROTAS PARA TABELA DATA
    Route::get('/tabela-data/preview', [DataGeneratorController::class, 'visualizarTabelaData'])->name('tabela.data.preview');
    Route::get('/tabela-data/debug', [DataGeneratorController::class, 'debugTabelaData'])->name('tabela.data.debug');
    Route::get('/tabela-data/gerar', [DataGeneratorController::class, 'gerarTabelaData'])->name('tabela.data.gerar');
	
});





// ✅ ROTAS PARA SALVAR IMAGENS (SEM /api/ no caminho)
Route::post('/salvar-imagem', function (Request $request) {
    try {
        $arquivo = $request->file('arquivo');
        $pasta = $request->input('pasta', 'fotos_perfil');
        
        if (!$arquivo) {
            return response()->json(['error' => 'Arquivo não encontrado'], 400);
        }
        
        // Definir caminho de destino
        $caminhoDestino = public_path($pasta);
        
        // Criar pasta se não existir
        if (!File::exists($caminhoDestino)) {
            File::makeDirectory($caminhoDestino, 0755, true);
        }
        
        // Salvar arquivo
        $nomeArquivo = $arquivo->getClientOriginalName();
        $arquivo->move($caminhoDestino, $nomeArquivo);
        
        return response()->json([
            'success' => true,
            'arquivo' => $nomeArquivo,
            'caminho' => "/{$pasta}/{$nomeArquivo}"
        ]);
        
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::delete('/limpar-imagens-perfil', function () {
    try {
        $pasta = public_path('fotos_perfil');
        $removidos = 0;
        
        if (File::exists($pasta)) {
            $arquivos = File::files($pasta);
            foreach ($arquivos as $arquivo) {
                File::delete($arquivo);
                $removidos++;
            }
        }
        
        return response()->json(['success' => true, 'removidos' => $removidos]);
        
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});


Route::middleware('auth:sanctum')->get('/imagem/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    
    if (!file_exists($filePath)) {
        abort(404);
    }
    
    return response()->file($filePath);
})->where('path', '.*');








// ===== ROTAS PROTEGIDAS (COM AUTENTICAÇÃO) =====
Route::middleware('auth:sanctum')->group(function () {
    
    // ===== AUTENTICAÇÃO =====
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->name('api.refresh.token');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('api.logout.all');
    
    // ===== USUÁRIO =====
    Route::patch('/me', [OrcamentoApiController::class, 'update'])->name('api.user.update');
    Route::post('/me/foto', [UserController::class, 'atualizarImagem'])->name('api.user.foto');

    // ===== ORÇAMENTO =====
    Route::prefix('orcamento')->name('api.orcamento.')->group(function () {
        Route::get('/estados', [OrcamentoApiController::class, 'getEstados'])->name('estados');
        Route::post('/cidades', [OrcamentoApiController::class, 'getCidades'])->name('cidades');
        Route::post('/planos', [OrcamentoApiController::class, 'getPlanos'])->name('planos');
        Route::post('/cotacao', [OrcamentoApiController::class, 'calcularCotacao'])->name('cotacao');
        Route::post('/administradoras', [OrcamentoApiController::class, 'filtrarAdministradora'])->name('administradoras');
        Route::post('/gerar-documento', [OrcamentoApiController::class, 'criarDocumento'])->name('gerar.documento');
        Route::post('/tabelacompleta', [OrcamentoApiController::class, 'criarTabelaCompleta'])->name('tabela.completa');
        Route::post('/completa', [OrcamentoApiController::class, 'completa'])->name('completa');
        Route::post('/gerar-documento-v2', [OrcamentoApiController::class, 'criarDocumentoV2'])->name('gerar.documento.v2');
    });
});