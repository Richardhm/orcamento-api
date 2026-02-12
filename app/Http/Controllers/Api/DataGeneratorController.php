<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DataGeneratorController extends Controller
{
	
	
	public function gerarPdfs()
{
    try {
        // Buscar todos os PDFs com LEFT JOIN para incluir registros com tabela_origens_id NULL
        $pdfs = DB::select("
            SELECT 
                p.id,
                p.plano_id,
                p.administradora_id,
                p.tabela_origens_id,
                p.linha01,
                p.linha02,
                p.linha03,
                p.consultas_eletivas_total,
                p.consultas_de_urgencia_total,
                p.exames_simples_total,
                p.exames_complexos_total,
                p.terapias_especiais_total,
                p.demais_terapias_total,
                p.internacoes_total,
                p.cirurgia_total,
                p.consultas_eletivas_parcial,
                p.consultas_de_urgencia_parcial,
                p.exames_simples_parcial,
                p.exames_complexos_parcial,
                p.terapias_especiais_parcial,
                p.demais_terapias_parcial,
                p.internacoes_parcial,
                p.cirurgia_parcial,
                t.nome as cidade_nome
            FROM pdf p
            LEFT JOIN tabela_origens t ON p.tabela_origens_id = t.id
            ORDER BY p.id
        ");

        // Converter para array com apelidos das cidades
        $pdfsArray = [];
        $cidadesEncontradas = [];
        $registrosComCidade = 0;
        $registrosSemCidade = 0;
        
        foreach ($pdfs as $pdf) {
            // Se tem cidade, converter para apelido, senão deixar null
            $tabelaOrigem = null;
            if ($pdf->cidade_nome) {
                $tabelaOrigem = $this->converterNomeParaApelido($pdf->cidade_nome);
                $registrosComCidade++;
                
                // Registrar cidade encontrada
                if (!in_array($pdf->cidade_nome, $cidadesEncontradas)) {
                    $cidadesEncontradas[] = $pdf->cidade_nome;
                }
            } else {
                $registrosSemCidade++;
            }
            
            $pdfsArray[] = [
                'id' => (int) $pdf->id,
                'plano_id' => (int) $pdf->plano_id,
                'tabela_origem' => $tabelaOrigem,
                'linha01' => $pdf->linha01,
                'linha02' => $pdf->linha02,
                'linha03' => $pdf->linha03,
                'consultas_eletivas_total' => $pdf->consultas_eletivas_total,
                'consultas_de_urgencia_total' => $pdf->consultas_de_urgencia_total,
                'exames_simples_total' => $pdf->exames_simples_total,
                'exames_complexos_total' => $pdf->exames_complexos_total,
                'terapias_especiais_total' => $pdf->terapias_especiais_total,
                'demais_terapias_total' => $pdf->demais_terapias_total,
                'internacoes_total' => $pdf->internacoes_total,
                'cirurgia_total' => $pdf->cirurgia_total,
                'consultas_eletivas_parcial' => $pdf->consultas_eletivas_parcial,
                'consultas_de_urgencia_parcial' => $pdf->consultas_de_urgencia_parcial,
                'exames_simples_parcial' => $pdf->exames_simples_parcial,
                'exames_complexos_parcial' => $pdf->exames_complexos_parcial,
                'terapias_especiais_parcial' => $pdf->terapias_especiais_parcial,
                'demais_terapias_parcial' => $pdf->demais_terapias_parcial,
                'internacoes_parcial' => $pdf->internacoes_parcial,
                'cirurgia_parcial' => $pdf->cirurgia_parcial
            ];
        }

        // Salvar arquivo JSON
        $jsonContent = json_encode($pdfsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filePath = public_path('data/pdfs.json');
        
        // Criar diretório se não existir
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        
        File::put($filePath, $jsonContent);

        return response()->json([
            'success' => true,
            'message' => 'Arquivo pdfs.json gerado com sucesso!',
            'total_registros' => count($pdfsArray),
            'registros_com_cidade' => $registrosComCidade,
            'registros_sem_cidade' => $registrosSemCidade,
            'total_cidades_encontradas' => count($cidadesEncontradas),
            'cidades_encontradas' => $cidadesEncontradas,
            'arquivo' => '/data/pdfs.json'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao gerar arquivo pdfs.json',
            'error' => $e->getMessage()
        ], 500);
    }
}
	
	
	public function visualizarPdfs()
{
    try {
        $pdfs = DB::select("
            SELECT 
                p.id,
                p.plano_id,
                p.administradora_id,
                p.tabela_origens_id,
                p.linha01,
                p.linha02,
                p.linha03,
                t.nome as cidade_nome
            FROM pdf p
            LEFT JOIN tabela_origens t ON p.tabela_origens_id = t.id
            ORDER BY p.id
            LIMIT 10
        ");

        $preview = [];
        foreach ($pdfs as $pdf) {
            $tabelaOrigem = $pdf->cidade_nome ? $this->converterNomeParaApelido($pdf->cidade_nome) : null;
            
            $preview[] = [
                'id' => (int) $pdf->id,
                'plano_id' => (int) $pdf->plano_id,
                'administradora_id' => $pdf->administradora_id,
                'tabela_origens_id' => $pdf->tabela_origens_id,
                'cidade_original' => $pdf->cidade_nome,
                'tabela_origem' => $tabelaOrigem,
                'linha01' => $pdf->linha01,
                'linha02' => $pdf->linha02,
                'linha03' => $pdf->linha03
            ];
        }

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'total_preview' => count($preview),
            'message' => 'Preview dos primeiros 10 PDFs'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao visualizar PDFs',
            'error' => $e->getMessage()
        ], 500);
    }
}
	
	
public function debugPdfs()
{
    try {
        // Verificar quantos PDFs existem por cidade
        $pdfsPorCidade = DB::select("
            SELECT 
                t.id,
                t.nome,
                COUNT(p.id) as total_pdfs
            FROM tabela_origens t
            LEFT JOIN pdf p ON t.id = p.tabela_origens_id
            GROUP BY t.id, t.nome
            HAVING COUNT(p.id) > 0
            ORDER BY COUNT(p.id) DESC
        ");
        
        // Verificar PDFs sem cidade (tabela_origens_id NULL)
        $pdfsSemCidade = DB::select("
            SELECT COUNT(*) as total
            FROM pdf p
            WHERE p.tabela_origens_id IS NULL
        ");
        
        // Verificar total de PDFs
        $totalPdfs = DB::select("SELECT COUNT(*) as total FROM pdf")[0]->total;
        
        // Verificar IDs únicos de tabela_origens_id nos PDFs
        $idsUsados = DB::select("
            SELECT DISTINCT tabela_origens_id, COUNT(*) as total
            FROM pdf 
            WHERE tabela_origens_id IS NOT NULL
            GROUP BY tabela_origens_id
            ORDER BY tabela_origens_id
        ");
        
        return response()->json([
            'success' => true,
            'debug' => [
                'total_pdfs' => $totalPdfs,
                'pdfs_sem_cidade' => $pdfsSemCidade[0]->total,
                'pdfs_com_cidade' => $totalPdfs - $pdfsSemCidade[0]->total,
                'total_cidades_com_pdfs' => count($idsUsados),
                'detalhes_por_cidade' => $pdfsPorCidade,
                'ids_usados_nos_pdfs' => $idsUsados
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro no debug de PDFs',
            'error' => $e->getMessage()
        ], 500);
    }
}	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	public function gerarCarencias()
{
    try {
        // ✅ PRIMEIRO: BUSCAR TODAS AS CIDADES E CRIAR MAPEAMENTO AUTOMÁTICO
        $cidades = DB::select("SELECT id, nome FROM tabela_origens ORDER BY id");
        
        $mapeamentoCidades = [];
        foreach ($cidades as $cidade) {
            // Converter nome para apelido automaticamente
            $apelido = $this->converterNomeParaApelido($cidade->nome);
            $mapeamentoCidades[$cidade->id] = $apelido;
        }

        // ✅ SEGUNDO: BUSCAR TODAS AS CARÊNCIAS
        $carencias = DB::select("
            SELECT 
                c.id,
                c.plano_id,
                c.tabela_origens_id,
                c.tempo,
                c.detalhe,
                c.frase,
                t.nome as cidade_nome
            FROM carencias c
            INNER JOIN tabela_origens t ON c.tabela_origens_id = t.id
            ORDER BY c.id
        ");

        // ✅ TERCEIRO: CONVERTER PARA ARRAY COM APELIDOS
        $carenciasArray = [];
        foreach ($carencias as $carencia) {
            $tabelaOrigem = $mapeamentoCidades[$carencia->tabela_origens_id] ?? 
                           $this->converterNomeParaApelido($carencia->cidade_nome);
            
            $carenciasArray[] = [
                'id' => (int) $carencia->id,
                'plano_id' => (int) $carencia->plano_id,
                'tabela_origem' => $tabelaOrigem,
                'tempo' => (int) $carencia->tempo,
                'detalhe' => $carencia->detalhe,
                'frase' => $carencia->frase
            ];
        }

        // ✅ SALVAR ARQUIVO JSON
        $jsonContent = json_encode($carenciasArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filePath = public_path('data/carencias.json');
        
        // Criar diretório se não existir
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        
        File::put($filePath, $jsonContent);

        return response()->json([
            'success' => true,
            'message' => 'Arquivo carencias.json gerado com sucesso!',
            'total_registros' => count($carenciasArray),
            'total_cidades' => count($mapeamentoCidades),
            'cidades_mapeadas' => $mapeamentoCidades,
            'arquivo' => '/data/carencias.json'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao gerar arquivo carencias.json',
            'error' => $e->getMessage()
        ], 500);
    }
}

	

// app/Http/Controllers/Api/DataGeneratorController.php - ADICIONAR MÉTODO

/**
 * ✅ GERAR ARQUIVO CIDADES.JSON
 */
public function gerarCidades()
{
    try {
        // Buscar todas as cidades da tabela_origens
        $cidades = DB::select("
            SELECT id, nome, uf 
            FROM tabela_origens 
            WHERE nome IS NOT NULL 
            ORDER BY uf, nome
        ");

        // Organizar por UF
        $cidadesPorUf = [];
        
        foreach ($cidades as $cidade) {
            $uf = $cidade->uf;
            $apelido = $this->converterNomeParaApelido($cidade->nome);
            
            if (!isset($cidadesPorUf[$uf])) {
                $cidadesPorUf[$uf] = [];
            }
            
            $cidadesPorUf[$uf][] = [
                'id' => (int) $cidade->id,
                'nome' => $cidade->nome,
                'apelido' => $apelido
            ];
        }

        // Ordenar UFs alfabeticamente
        ksort($cidadesPorUf);

        // Salvar arquivo JSON
        $jsonContent = json_encode($cidadesPorUf, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filePath = public_path('data/cidades.json');
        
        // Criar diretório se não existir
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        
        File::put($filePath, $jsonContent);

        // Contar totais
        $totalCidades = count($cidades);
        $totalUfs = count($cidadesPorUf);

        return response()->json([
            'success' => true,
            'message' => 'Arquivo cidades.json gerado com sucesso!',
            'total_cidades' => $totalCidades,
            'total_ufs' => $totalUfs,
            'cidades_por_uf' => array_map(function($uf, $cidades) {
                return ['uf' => $uf, 'total' => count($cidades)];
            }, array_keys($cidadesPorUf), $cidadesPorUf),
            'arquivo' => '/data/cidades.json'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao gerar arquivo cidades.json',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ VISUALIZAR PREVIEW DAS CIDADES
 */
public function visualizarCidades()
{
    try {
        $cidades = DB::select("
            SELECT id, nome, uf 
            FROM tabela_origens 
            WHERE nome IS NOT NULL 
            ORDER BY uf, nome
            LIMIT 20
        ");

        $preview = [];
        foreach ($cidades as $cidade) {
            $apelido = $this->converterNomeParaApelido($cidade->nome);
            $preview[] = [
                'id' => (int) $cidade->id,
                'nome' => $cidade->nome,
                'uf' => $cidade->uf,
                'apelido_gerado' => $apelido
            ];
        }

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'total_preview' => count($preview),
            'message' => 'Preview das primeiras 20 cidades'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao visualizar cidades',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function gerarPlanosData()
{
    try {
        // Buscar dados das tabelas relacionadas
        $administradoraPlanos = DB::select("
            SELECT 
                ap.plano_id,
                ap.administradora_id,
                ap.tabela_origens_id,
                p.nome as plano_nome,
                p.imagem as plano_imagem,
                p.empresarial,
                t.nome as cidade_nome,
                a.nome as administradora_nome
            FROM administradora_planos ap
            INNER JOIN planos p ON ap.plano_id = p.id
            INNER JOIN tabela_origens t ON ap.tabela_origens_id = t.id
            INNER JOIN administradoras a ON ap.administradora_id = a.id
            ORDER BY ap.administradora_id, t.nome, ap.plano_id
        ");

        // Organizar dados por administradora-cidade
        $planosDataArray = [];
        $estatisticas = [
            'total_combinacoes' => 0,
            'administradoras_usadas' => [],
            'cidades_usadas' => []
        ];
        
        foreach ($administradoraPlanos as $item) {
            // Converter nome da cidade para apelido
            $apelidoCidade = $this->converterNomeParaApelido($item->cidade_nome);
            
            // Criar chave no formato: administradora_id-apelido_cidade
            $chave = $item->administradora_id . '-' . $apelidoCidade;
            
            // Se não existe a chave, criar array vazio
            if (!isset($planosDataArray[$chave])) {
                $planosDataArray[$chave] = [];
                $estatisticas['total_combinacoes']++;
            }
            
            // Adicionar plano ao array da chave
            $planosDataArray[$chave][] = [
                'id' => (int) $item->plano_id,
                'nome' => $item->plano_nome,
                'imagem' => $item->plano_imagem ?: 'planos/default.png',
                'empresarial' => (int) $item->empresarial
            ];
            
            // Estatísticas
            if (!in_array($item->administradora_nome, $estatisticas['administradoras_usadas'])) {
                $estatisticas['administradoras_usadas'][] = $item->administradora_nome;
            }
            if (!in_array($item->cidade_nome, $estatisticas['cidades_usadas'])) {
                $estatisticas['cidades_usadas'][] = $item->cidade_nome;
            }
        }

        // Ordenar as chaves alfabeticamente
        ksort($planosDataArray);

        // Salvar arquivo JSON
        $jsonContent = json_encode($planosDataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filePath = public_path('data/planosData.json');
        
        // Criar diretório se não existir
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        
        File::put($filePath, $jsonContent);

        return response()->json([
            'success' => true,
            'message' => 'Arquivo planosData.json gerado com sucesso!',
            'total_combinacoes' => $estatisticas['total_combinacoes'],
            'total_administradoras' => count($estatisticas['administradoras_usadas']),
            'total_cidades' => count($estatisticas['cidades_usadas']),
            'administradoras_usadas' => $estatisticas['administradoras_usadas'],
            'cidades_usadas' => $estatisticas['cidades_usadas'],
            'arquivo' => '/data/planosData.json'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao gerar arquivo planosData.json',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ VISUALIZAR PREVIEW DOS PLANOS DATA
 */
public function visualizarPlanosData()
{
    try {
        $administradoraPlanos = DB::select("
            SELECT 
                ap.plano_id,
                ap.administradora_id,
                ap.tabela_origens_id,
                p.nome as plano_nome,
                p.imagem as plano_imagem,
                p.empresarial,
                t.nome as cidade_nome,
                a.nome as administradora_nome
            FROM administradora_planos ap
            INNER JOIN planos p ON ap.plano_id = p.id
            INNER JOIN tabela_origens t ON ap.tabela_origens_id = t.id
            INNER JOIN administradoras a ON ap.administradora_id = a.id
            ORDER BY ap.administradora_id, t.nome, ap.plano_id
            LIMIT 20
        ");

        $preview = [];
        foreach ($administradoraPlanos as $item) {
            $apelidoCidade = $this->converterNomeParaApelido($item->cidade_nome);
            $chave = $item->administradora_id . '-' . $apelidoCidade;
            
            $preview[] = [
                'chave_gerada' => $chave,
                'administradora_id' => (int) $item->administradora_id,
                'administradora_nome' => $item->administradora_nome,
                'cidade_original' => $item->cidade_nome,
                'cidade_apelido' => $apelidoCidade,
                'plano_id' => (int) $item->plano_id,
                'plano_nome' => $item->plano_nome,
                'plano_imagem' => $item->plano_imagem,
                'empresarial' => (int) $item->empresarial
            ];
        }

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'total_preview' => count($preview),
            'message' => 'Preview dos primeiros 20 registros de planosData'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao visualizar planosData',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ DEBUG PLANOS DATA
 */
public function debugPlanosData()
{
    try {
        // Verificar quantos registros existem na tabela administradora_planos
        $totalRegistros = DB::select("SELECT COUNT(*) as total FROM administradora_planos")[0]->total;
        
        // Verificar quantas administradoras têm planos
        $administradorasComPlanos = DB::select("
            SELECT 
                a.id,
                a.nome,
                COUNT(ap.id) as total_planos
            FROM administradoras a
            LEFT JOIN administradora_planos ap ON a.id = ap.administradora_id
            GROUP BY a.id, a.nome
            ORDER BY COUNT(ap.id) DESC
        ");
        
        // Verificar quantas cidades têm planos
        $cidadesComPlanos = DB::select("
            SELECT 
                t.id,
                t.nome,
                COUNT(ap.id) as total_planos
            FROM tabela_origens t
            LEFT JOIN administradora_planos ap ON t.id = ap.tabela_origens_id
            GROUP BY t.id, t.nome
            HAVING COUNT(ap.id) > 0
            ORDER BY COUNT(ap.id) DESC
        ");
        
        return response()->json([
            'success' => true,
            'debug' => [
                'total_registros_administradora_planos' => $totalRegistros,
                'total_administradoras_com_planos' => count(array_filter($administradorasComPlanos, function($item) { return $item->total_planos > 0; })),
                'total_cidades_com_planos' => count($cidadesComPlanos),
                'administradoras_detalhes' => $administradorasComPlanos,
                'cidades_detalhes' => $cidadesComPlanos
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro no debug de planosData',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function gerarTabelaData()
{
    try {
        // Buscar todos os dados da tabela 'tabelas' com JOIN para pegar o nome da cidade
        $tabelas = DB::select("
            SELECT 
                t.id,
                t.administradora_id,
                t.tabela_origens_id,
                t.plano_id,
                t.acomodacao_id,
                t.faixa_etaria_id,
                t.coparticipacao,
                t.odonto,
                t.valor,
                tor.nome as cidade_nome
            FROM tabelas t
            INNER JOIN tabela_origens tor ON t.tabela_origens_id = tor.id
            WHERE tor.nome IS NOT NULL
            ORDER BY t.id
        ");

        // Converter para array com apelidos das cidades
        $tabelaDataArray = [];
        $cidadesEncontradas = [];
        $estatisticas = [
            'total_registros' => 0,
            'administradoras_usadas' => [],
            'planos_usados' => [],
            'cidades_usadas' => []
        ];
        
        foreach ($tabelas as $tabela) {
            // Converter nome da cidade para apelido
            $tabelaOrigem = $this->converterNomeParaApelido($tabela->cidade_nome);
            
            // Registrar estatísticas
            $estatisticas['total_registros']++;
            
            if (!in_array($tabela->cidade_nome, $cidadesEncontradas)) {
                $cidadesEncontradas[] = $tabela->cidade_nome;
                $estatisticas['cidades_usadas'][] = $tabela->cidade_nome;
            }
            
            if (!in_array($tabela->administradora_id, $estatisticas['administradoras_usadas'])) {
                $estatisticas['administradoras_usadas'][] = $tabela->administradora_id;
            }
            
            if (!in_array($tabela->plano_id, $estatisticas['planos_usados'])) {
                $estatisticas['planos_usados'][] = $tabela->plano_id;
            }
            
            $tabelaDataArray[] = [
                'id' => (int) $tabela->id,
                'administradora_id' => (int) $tabela->administradora_id,
                'tabela_origem' => $tabelaOrigem,
                'plano_id' => (int) $tabela->plano_id,
                'acomodacao_id' => (int) $tabela->acomodacao_id,
                'faixa_etaria_id' => (int) $tabela->faixa_etaria_id,
                'coparticipacao' => (int) $tabela->coparticipacao,
                'odonto' => (int) $tabela->odonto,
                'valor' => (float) $tabela->valor
            ];
        }

        // Salvar arquivo JSON
        $jsonContent = json_encode($tabelaDataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filePath = public_path('data/tabelaData.json');
        
        // Criar diretório se não existir
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        
        File::put($filePath, $jsonContent);

        return response()->json([
            'success' => true,
            'message' => 'Arquivo tabelaData.json gerado com sucesso!',
            'total_registros' => $estatisticas['total_registros'],
            'total_cidades_encontradas' => count($cidadesEncontradas),
            'total_administradoras' => count($estatisticas['administradoras_usadas']),
            'total_planos' => count($estatisticas['planos_usados']),
            'cidades_encontradas' => $cidadesEncontradas,
            'administradoras_usadas' => $estatisticas['administradoras_usadas'],
            'planos_usados' => $estatisticas['planos_usados'],
            'arquivo' => '/data/tabelaData.json'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao gerar arquivo tabelaData.json',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ VISUALIZAR PREVIEW DA TABELA DATA
 */
public function visualizarTabelaData()
{
    try {
        $tabelas = DB::select("
            SELECT 
                t.id,
                t.administradora_id,
                t.tabela_origens_id,
                t.plano_id,
                t.acomodacao_id,
                t.faixa_etaria_id,
                t.coparticipacao,
                t.odonto,
                t.valor,
                tor.nome as cidade_nome
            FROM tabelas t
            INNER JOIN tabela_origens tor ON t.tabela_origens_id = tor.id
            WHERE tor.nome IS NOT NULL
            ORDER BY t.id
            LIMIT 20
        ");

        $preview = [];
        foreach ($tabelas as $tabela) {
            $tabelaOrigem = $this->converterNomeParaApelido($tabela->cidade_nome);
            
            $preview[] = [
                'id' => (int) $tabela->id,
                'administradora_id' => (int) $tabela->administradora_id,
                'tabela_origens_id' => $tabela->tabela_origens_id,
                'cidade_original' => $tabela->cidade_nome,
                'tabela_origem' => $tabelaOrigem,
                'plano_id' => (int) $tabela->plano_id,
                'acomodacao_id' => (int) $tabela->acomodacao_id,
                'faixa_etaria_id' => (int) $tabela->faixa_etaria_id,
                'coparticipacao' => (int) $tabela->coparticipacao,
                'odonto' => (int) $tabela->odonto,
                'valor' => (float) $tabela->valor
            ];
        }

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'total_preview' => count($preview),
            'message' => 'Preview dos primeiros 20 registros de tabelaData'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao visualizar tabelaData',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ DEBUG TABELA DATA
 */
public function debugTabelaData()
{
    try {
        // Verificar total de registros
        $totalRegistros = DB::select("SELECT COUNT(*) as total FROM tabelas")[0]->total;
        
        // Verificar quantos registros por administradora
        $registrosPorAdministradora = DB::select("
            SELECT 
                administradora_id,
                COUNT(*) as total_registros
            FROM tabelas
            GROUP BY administradora_id
            ORDER BY COUNT(*) DESC
        ");
        
        // Verificar quantos registros por cidade
        $registrosPorCidade = DB::select("
            SELECT 
                tor.nome as cidade_nome,
                COUNT(t.id) as total_registros
            FROM tabela_origens tor
            LEFT JOIN tabelas t ON tor.id = t.tabela_origens_id
            GROUP BY tor.id, tor.nome
            HAVING COUNT(t.id) > 0
            ORDER BY COUNT(t.id) DESC
            LIMIT 10
        ");
        
        // Verificar quantos registros por plano
        $registrosPorPlano = DB::select("
            SELECT 
                plano_id,
                COUNT(*) as total_registros
            FROM tabelas
            GROUP BY plano_id
            ORDER BY COUNT(*) DESC
        ");
        
        // Verificar registros órfãos (sem cidade)
        $registrosOrfaos = DB::select("
            SELECT COUNT(*) as total
            FROM tabelas t
            LEFT JOIN tabela_origens tor ON t.tabela_origens_id = tor.id
            WHERE tor.id IS NULL
        ");
        
        return response()->json([
            'success' => true,
            'debug' => [
                'total_registros_tabelas' => $totalRegistros,
                'registros_orfaos' => $registrosOrfaos[0]->total,
                'registros_por_administradora' => $registrosPorAdministradora,
                'top_10_cidades_com_mais_registros' => $registrosPorCidade,
                'registros_por_plano' => $registrosPorPlano
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro no debug de tabelaData',
            'error' => $e->getMessage()
        ], 500);
    }
}




public function gerarTodos()
{
    try {
        $resultados = [];
        
        // Gerar carências
        $carenciasResponse = $this->gerarCarenciasComFiltro();
        $resultados['carencias'] = json_decode($carenciasResponse->getContent(), true);
        
        // Gerar cidades
        $cidadesResponse = $this->gerarCidades();
        $resultados['cidades'] = json_decode($cidadesResponse->getContent(), true);
        
        // Gerar descontos
        $descontosResponse = $this->gerarDescontos();
        $resultados['descontos'] = json_decode($descontosResponse->getContent(), true);
        
        // Gerar PDFs
        $pdfsResponse = $this->gerarPdfs();
        $resultados['pdfs'] = json_decode($pdfsResponse->getContent(), true);
        
        // Gerar planos data
        $planosDataResponse = $this->gerarPlanosData();
        $resultados['planosData'] = json_decode($planosDataResponse->getContent(), true);
        
        // ✅ GERAR TABELA DATA
        $tabelaDataResponse = $this->gerarTabelaData();
        $resultados['tabelaData'] = json_decode($tabelaDataResponse->getContent(), true);
        
        return response()->json([
            'success' => true,
            'message' => 'Todos os arquivos JSON foram gerados com sucesso!',
            'resultados' => $resultados
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao gerar arquivos JSON',
            'error' => $e->getMessage()
        ], 500);
    }
}





	
	
	
	
	
	
	
    
   public function debugCarencias()
{
    try {
        // 1. Verificar todas as cidades na tabela_origens
        $todasCidades = DB::select("SELECT id, nome FROM tabela_origens ORDER BY id");
        
        // 2. Verificar quantas carências existem por cidade
        $carenciasPorCidade = DB::select("
            SELECT 
                t.id,
                t.nome,
                COUNT(c.id) as total_carencias
            FROM tabela_origens t
            LEFT JOIN carencias c ON t.id = c.tabela_origens_id
            GROUP BY t.id, t.nome
            ORDER BY t.id
        ");
        
        // 3. Verificar se existem carências órfãs (sem cidade)
        $carenciasOrfas = DB::select("
            SELECT COUNT(*) as total
            FROM carencias c
            LEFT JOIN tabela_origens t ON c.tabela_origens_id = t.id
            WHERE t.id IS NULL
        ");
        
        // 4. Verificar IDs únicos de tabela_origens_id nas carências
        $idsUsados = DB::select("
            SELECT DISTINCT tabela_origens_id, COUNT(*) as total
            FROM carencias 
            GROUP BY tabela_origens_id
            ORDER BY tabela_origens_id
        ");
        
        // 5. Verificar se há diferença entre IDs
        $cidadesComCarencias = array_column($idsUsados, 'tabela_origens_id');
        $todasCidadesIds = array_column($todasCidades, 'id');
        $cidadesSemCarencias = array_diff($todasCidadesIds, $cidadesComCarencias);
        
        return response()->json([
            'success' => true,
            'debug' => [
                'total_cidades_cadastradas' => count($todasCidades),
                'total_cidades_com_carencias' => count($idsUsados),
                'carencias_orfas' => $carenciasOrfas[0]->total,
                'cidades_sem_carencias' => $cidadesSemCarencias,
                'detalhes_por_cidade' => $carenciasPorCidade,
                'ids_usados_nas_carencias' => $idsUsados,
                'todas_cidades' => $todasCidades
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro no debug',
            'error' => $e->getMessage()
        ], 500);
    }
}  

	private function converterNomeParaApelido($nome)
{
    // Remover acentos e caracteres especiais
    $apelido = $nome;
    
    // Mapeamento de caracteres acentuados
    $acentos = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
        'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N'
    ];
    
    // Aplicar substituições
    foreach ($acentos as $com_acento => $sem_acento) {
        $apelido = str_replace($com_acento, $sem_acento, $apelido);
    }
    
    // Converter para minúsculo
    $apelido = strtolower($apelido);
    
    // Remover espaços e caracteres especiais
    $apelido = preg_replace('/[^a-z0-9]/', '', $apelido);
    
    return $apelido;
}


public function visualizarMapeamentoCidades()
{
    try {
        $cidades = DB::select("SELECT id, nome FROM tabela_origens ORDER BY id");
        
        $mapeamento = [];
        foreach ($cidades as $cidade) {
            $apelido = $this->converterNomeParaApelido($cidade->nome);
            $mapeamento[] = [
                'id' => $cidade->id,
                'nome_original' => $cidade->nome,
                'apelido_gerado' => $apelido
            ];
        }

        return response()->json([
            'success' => true,
            'mapeamento' => $mapeamento,
            'total' => count($mapeamento)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao visualizar mapeamento',
            'error' => $e->getMessage()
        ], 500);
    }
}











    
    

    /**
     * Listar arquivos JSON disponíveis
     */
    public function listarArquivos()
    {
        try {
            $dataPath = public_path('data');
            $arquivos = [];
            
            if (File::exists($dataPath)) {
                $files = File::files($dataPath);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'json') {
                        $arquivos[] = [
                            'nome' => $file->getFilename(),
                            'tamanho' => File::size($file->getPathname()),
                            'modificado' => date('Y-m-d H:i:s', File::lastModified($file->getPathname())),
                            'url' => '/data/' . $file->getFilename()
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'arquivos' => $arquivos,
                'total' => count($arquivos)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar arquivos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Visualizar dados antes de gerar o arquivo
     */
    public function visualizarCarencias()
    {
        try {
            $carencias = DB::select("
                SELECT 
                    c.id,
                    c.plano_id,
                    t.nome as cidade_original,
                    CASE 
                        WHEN t.nome = 'Anápolis' THEN 'anapolis'
                        WHEN t.nome = 'Goiânia' THEN 'goiania'
                        WHEN t.nome = 'Rondonópolis' THEN 'rondonopolis'
                        WHEN t.nome = 'Cuiabá' THEN 'cuiaba'
                        WHEN t.nome = 'Três Lagoas' THEN 'treslagoas'
                        WHEN t.nome = 'Dourados' THEN 'dourados'
                        WHEN t.nome = 'Campo Grande' THEN 'campogrande'
                        WHEN t.nome = 'Brasília' THEN 'brasilia'
                        WHEN t.nome = 'Rio Verde' THEN 'rioverde'
                        WHEN t.nome = 'Bahia' THEN 'bahia'
                        WHEN t.nome = 'Uberlândia' THEN 'uberlandia'
                        WHEN t.nome = 'Belo Horizonte' THEN 'belohorizonte'
                        WHEN t.nome = 'Uberaba' THEN 'uberaba'
                        WHEN t.nome = 'Fortaleza' THEN 'fortaleza'
                        WHEN t.nome = 'Juazeiro do Norte' THEN 'juazeirodonorte'
                        WHEN t.nome = 'Belém' THEN 'belem'
                        WHEN t.nome = 'Parauapebas' THEN 'parauapebas'
                        WHEN t.nome = 'Maceió' THEN 'maceio'
                        WHEN t.nome = 'São Luís' THEN 'saoluis'
                        WHEN t.nome = 'Aracaju' THEN 'aracaju'
                        WHEN t.nome = 'João Pessoa' THEN 'joaopessoa'
                        WHEN t.nome = 'Manaus' THEN 'manaus'
                        WHEN t.nome = 'Teresina' THEN 'teresina'
                        WHEN t.nome = 'Natal' THEN 'natal'
                        WHEN t.nome = 'Mossoró' THEN 'mossoro'
                        WHEN t.nome = 'Recife' THEN 'recife'
                        WHEN t.nome = 'Camaçari' THEN 'camacari'
                        WHEN t.nome = 'Feira de Santana' THEN 'feiradesantana'
                        WHEN t.nome = 'Salvador' THEN 'salvador'
                        ELSE LOWER(t.nome)
                    END as tabela_origem,
                    c.tempo,
                    c.detalhe,
                    c.frase
                FROM carencias c
                INNER JOIN tabela_origens t ON c.tabela_origens_id = t.id
                ORDER BY c.id
                LIMIT 20
            ");

            return response()->json([
                'success' => true,
                'preview' => $carencias,
                'total_preview' => count($carencias),
                'message' => 'Preview dos primeiros 20 registros'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao visualizar dados',
                'error' => $e->getMessage()
            ], 500);
        }
    }
	
	
	public function gerarDescontos()
{
    try {
        // Buscar todos os descontos com JOIN para pegar o nome da cidade
        $descontos = DB::select("
            SELECT 
                d.id,
                d.tabela_origens_id,
                d.plano_id,
                d.administradora_id,
                d.valor,
                t.nome as cidade_nome
            FROM desconto d
            INNER JOIN tabela_origens t ON d.tabela_origens_id = t.id
            WHERE t.nome IS NOT NULL
            ORDER BY d.id
        ");

        // Converter para array com apelidos das cidades
        $descontosArray = [];
        $cidadesEncontradas = [];
        
        foreach ($descontos as $desconto) {
            $tabelaOrigem = $this->converterNomeParaApelido($desconto->cidade_nome);
            
            // Registrar cidade encontrada
            if (!in_array($desconto->cidade_nome, $cidadesEncontradas)) {
                $cidadesEncontradas[] = $desconto->cidade_nome;
            }
            
            $descontosArray[] = [
                'id' => (int) $desconto->id,
                'tabela_origem' => $tabelaOrigem,
                'plano_id' => (int) $desconto->plano_id,
                'administradora_id' => (int) $desconto->administradora_id,
                'valor' => (float) $desconto->valor
            ];
        }

        // Salvar arquivo JSON
        $jsonContent = json_encode($descontosArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filePath = public_path('data/descontos.json');
        
        // Criar diretório se não existir
        if (!File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }
        
        File::put($filePath, $jsonContent);

        return response()->json([
            'success' => true,
            'message' => 'Arquivo descontos.json gerado com sucesso!',
            'total_registros' => count($descontosArray),
            'total_cidades_com_desconto' => count($cidadesEncontradas),
            'cidades_encontradas' => $cidadesEncontradas,
            'arquivo' => '/data/descontos.json'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao gerar arquivo descontos.json',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ VISUALIZAR PREVIEW DOS DESCONTOS
 */
public function visualizarDescontos()
{
    try {
        $descontos = DB::select("
            SELECT 
                d.id,
                d.tabela_origens_id,
                d.plano_id,
                d.administradora_id,
                d.valor,
                t.nome as cidade_nome
            FROM desconto d
            INNER JOIN tabela_origens t ON d.tabela_origens_id = t.id
            WHERE t.nome IS NOT NULL
            ORDER BY d.id
            LIMIT 20
        ");

        $preview = [];
        foreach ($descontos as $desconto) {
            $tabelaOrigem = $this->converterNomeParaApelido($desconto->cidade_nome);
            
            $preview[] = [
                'id' => (int) $desconto->id,
                'cidade_original' => $desconto->cidade_nome,
                'tabela_origem' => $tabelaOrigem,
                'plano_id' => (int) $desconto->plano_id,
                'administradora_id' => (int) $desconto->administradora_id,
                'valor' => (float) $desconto->valor
            ];
        }

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'total_preview' => count($preview),
            'message' => 'Preview dos primeiros 20 descontos'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro ao visualizar descontos',
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * ✅ DEBUG DESCONTOS
 */
public function debugDescontos()
{
    try {
        // Verificar quantos descontos existem por cidade
        $descontosPorCidade = DB::select("
            SELECT 
                t.id,
                t.nome,
                COUNT(d.id) as total_descontos
            FROM tabela_origens t
            LEFT JOIN desconto d ON t.id = d.tabela_origens_id
            GROUP BY t.id, t.nome
            ORDER BY t.id
        ");
        
        // Verificar se existem descontos órfãos (sem cidade)
        $descontosOrfaos = DB::select("
            SELECT COUNT(*) as total
            FROM desconto d
            LEFT JOIN tabela_origens t ON d.tabela_origens_id = t.id
            WHERE t.id IS NULL
        ");
        
        // Verificar IDs únicos de tabela_origens_id nos descontos
        $idsUsados = DB::select("
            SELECT DISTINCT tabela_origens_id, COUNT(*) as total
            FROM desconto 
            GROUP BY tabela_origens_id
            ORDER BY tabela_origens_id
        ");
        
        return response()->json([
            'success' => true,
            'debug' => [
                'total_descontos' => DB::select("SELECT COUNT(*) as total FROM desconto")[0]->total,
                'total_cidades_com_descontos' => count($idsUsados),
                'descontos_orfaos' => $descontosOrfaos[0]->total,
                'detalhes_por_cidade' => $descontosPorCidade,
                'ids_usados_nos_descontos' => $idsUsados
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro no debug de descontos',
            'error' => $e->getMessage()
        ], 500);
    }
}
	
	
	
	
	
	
	
	
	
	
	
	
}
