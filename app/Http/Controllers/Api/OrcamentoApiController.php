<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TabelaOrigens;
use App\Models\Plano;
use App\Models\Carencia;
use App\Models\Tabela;
use App\Models\Administradora;
use App\Models\PdfExcecao;
use App\Models\Desconto;
use App\Models\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf as PDFFile;

class OrcamentoApiController extends Controller
{

    public function update(Request $request)
    {
        
        // $data = $request->validate([
        //     'uf_preferencia' => 'string|size:2|nullable', // Valida o estado como sigla
        //     'layout_id' => 'integer|nullable', // Valida o layout_id como inteiro
        // ]);

        // Atualiza os campos do usuário autenticado
        $user = $request->user();
        $user->fill($request->all()); // Preenche apenas os campos fornecidos
        $user->save(); // Salva as alterações no banco de dados

        // Retorna o usuário atualizado
        return response()->json($user, 200);
    }

    public function completa(Request $request)
    {
        $cidade = $request->input('tabela_origem');
        $plano = $request->input('plano');
        $operadora = $request->input('operadora');

        $faixas = $request->input('faixas') ?? [];
        $sql = '';
        $chaves = [];
        foreach ($faixas as $faixaId => $f) {
            if ($f['quantidade'] > 0) {
                $sql .= " WHEN tabelas.faixa_etaria_id = {$faixaId} THEN {$f['quantidade']}";
                $chaves[] = $faixaId;
            }
        }


        if (empty($chaves)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nenhuma faixa etária válida fornecida.',
            ], 422);
        }

        $keys = implode(',', $chaves);

        // Realizando a consulta
        $dados = Tabela::select('tabelas.*')
            ->selectRaw("CASE {$sql} END AS quantidade")
            ->where('tabelas.tabela_origens_id', $cidade)
            ->where('tabelas.plano_id', $plano)
            ->where('tabelas.administradora_id', $operadora)
            ->where('tabelas.acomodacao_id',"!=",3)
            ->whereIn('tabelas.faixa_etaria_id', explode(',', $keys))
            ->orderBy('tabelas.faixa_etaria_id')

            ->get();



        return response()->json([
            'status' => 'success',
            'data' => [
                'detalhes' => $dados

            ],
        ], 200);
    }


    public function criarTabelaCompleta(Request $request)
    {
         try {
        // Lê os parâmetros recebidos pela requisição
            $com_coparticipacao = 1;
            $sem_coparticipacao = 1;
            $desconto           = 0;
            $status_carencia    = 0;
            $apenasvalores      = 0;
            $tipo_documento     = 'png';
            $ambulatorial       = 0;
            $cidade             = 2;
            $plano              = 1;
            $operadora          = 4;
            $odonto             = 1;


            $faixas = [ 
                1 => 1 , 
                2 => 1, 
                3 => 1, 
                4 => 1, 
                5 => 1, 
                6 => 1, 
                7 => 1, 
                8 => 1, 
                9 => 1, 
                10 => 1 
            ];

                


            // Montando lógica SQL baseado em faixas etárias
            $sql = "";
            $chaves = [];
            $somar_linhas = 0;

            foreach ($faixas as $k => $v) {
                if ($v != null && $v != 0) {
                    $sql .= " WHEN tabelas.faixa_etaria_id = {$k} THEN ${v} ";
                    $chaves[] = $k;
                    $somar_linhas += (int) $v;
                }
            }

            $linhas = count($chaves);

            // Informações básicas relacionadas às tabelas/origens/plano
            $cidade_nome = TabelaOrigens::find($cidade)->nome ?? 'N/A';
            $plano_nome  = Plano::find($plano)->nome ?? 'N/A';
            $cidade_uf   = TabelaOrigens::find($cidade)->uf ?? '';
            $admin_nome  = Administradora::find($operadora)->nome ?? 'N/A';

            // Configuração do layout
            //$layout = auth()->user()->layout_id ?? 1; // Default para layout 1
            $layout = 1;
            $layout_user = in_array($layout, [1, 2, 3, 4]) ? $layout : 1;

            $frase = $plano_nome . ($odonto ? " c/ Odonto" : " s/ Odonto");

            // Dados para a tabela principal
            $dados = Tabela::select('tabelas.*')
                ->selectRaw("CASE $sql END AS quantidade")
                ->join('faixa_etarias', 'faixa_etarias.id', '=', 'tabelas.faixa_etaria_id')
                ->where('tabelas.tabela_origens_id', $cidade)
                ->where('tabelas.plano_id', $plano)
                ->where('tabelas.administradora_id', $operadora)
                ->where("tabelas.odonto", $odonto)
                ->where("acomodacao_id", "!=", 3)
                ->whereIn('tabelas.faixa_etaria_id', explode(',', implode(',', $chaves)))
                ->orderBy("tabelas.faixa_etaria_id")
                ->get();


            $carencia = Carencia::where("plano_id",$plano)->where("tabela_origens_id",$cidade)->get();
            $quantidade_carencia = Carencia::where("plano_id",$plano)->where("tabela_origens_id",$cidade)->count();    
            $status_desconto = request()->status_desconto == "true" ? 1 : 0;
            // Preparar view (Blade template a ser renderizado)
            $viewName = "cotacao.modelotabela";


             $quantidade_cop = 0;
              if($apenasvalores == 0) {
                $pdf_excecao = PdfExcecao::where("plano_id",$plano)->where("tabela_origens_id",$cidade)->count();
                if($pdf_excecao == 1) {
                    $status_excecao = true;
                    $pdf_copar = PdfExcecao::where("plano_id",$plano)->where("tabela_origens_id",$cidade)->first();
                    $quantidade_cop = 1;
                } else {
                    $hasTabelaOrigens = Pdf::where('plano_id', $plano)
                        ->where('tabela_origens_id',$cidade)
                        ->exists();
                    if ($hasTabelaOrigens) {
                        $quantidade_cop = 1;
                        $pdf_copar = Pdf::where('plano_id', $plano)
                            ->where('tabela_origens_id',$cidade)
                            ->first();

                        if($pdf_copar->linha02) {
                            $itens = explode('|', $pdf_copar->linha02);
                            $itensFormatados = array_map(function($item) {
                                return trim($item); // Remove espaços extras
                            }, $itens);
                            $linha_01 = $itensFormatados[0];
                            $linha_02 = $itensFormatados[1];
                        }


                    } else {
                        $pdf_copar = Pdf::where('plano_id', $plano)->first();
                        if(isset($pdf_copar->linha02) && $pdf_copar->linha02) {
                            $quantidade_cop = 1;
                            $itens = explode('|', $pdf_copar->linha02);
                            $itensFormatados = array_map(function($item) {
                                return trim($item); // Remove espaços extras
                            }, $itens);
                            $linha_01 = $itensFormatados[0];
                            $linha_02 = $itensFormatados[1];
                        }

                    }
                }
            }    


            $valor_desconto = 0;
            if($status_desconto) {
                $desconto = Desconto::where('plano_id', $plano)->where('tabela_origens_id', $cidade)->where('administradora_id',$operadora)->first();
                if($desconto) {
                    $valor_desconto = $desconto->valor;
                }

            }    

            $view = \Illuminate\Support\Facades\View::make($viewName, [
                'dados' => $dados,
                'frase' => $frase,
                'cidade' => $cidade_nome,
                'plano_nome' => $plano_nome,
                'linha_01' => $linha_01,
                'linha_02' => $linha_02,
                'pdf' => $pdf_copar,
                'administradora' => $admin_nome,
                'com_coparticipacao' => $com_coparticipacao,
                'sem_coparticipacao' => $sem_coparticipacao,
                'apenasvalores' => $apenasvalores,
                'odonto' => $odonto,
                'linhas' => $linhas,
                'valor_desconto' => $valor_desconto,
                'desconto' => $status_desconto,
                'quantidade_carencia' => $quantidade_carencia,
                'carencia_texto' => $carencia,
                'carencia' => $status_carencia,
                'quantidade_copar' => $quantidade_cop,
                'nome' => "Richard",
                'celular' => "62 9 9358-1475",
                'image' => "users/1740672587_Foto0079.jpg",
                'status_excecao' => false,
            ])->render();

            // Prepara nome único para o arquivo
            $nome_arquivo = "orcamento_" . now()->format('d_m_Y_H_i_s') . "_" . uniqid();

            // Geração do Documento (PDF ou PNG)
            if ($tipo_documento === 'pdf') {
                $pdf = PDFFile::loadHTML($view)->setPaper('A3', 'portrait');
                return $pdf->download("{$nome_arquivo}.pdf");
            } elseif ($tipo_documento === 'png') {
                // Cria PDF temporário e converte para imagem PNG usando Ghostscript
                $pdfPath = storage_path("app/temp/{$nome_arquivo}.pdf");
                $pngPath = storage_path("app/temp/{$nome_arquivo}.png");

                // Salva o PDF temporariamente
                PDFFile::loadHTML($view)->setPaper('A3', 'portrait')->save($pdfPath);

                // Comando Ghostscript para converter o PDF em PNG
                $command = "gs -sDEVICE=pngalpha -r300 -o {$pngPath} {$pdfPath}";
                exec($command, $output, $return_var);

                if ($return_var !== 0 || !file_exists($pngPath)) {
                    return response()->json(['error' => 'Erro ao gerar a imagem.'], 500);
                }

                return response()->download($pngPath)->deleteFileAfterSend(true);
            } else {
                return response()->json(['error' => 'Tipo de documento inválido.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }




	public function criarDocumento(Request $request)
    {

        try {
            // Lê os parâmetros recebidos pela requisição
            $com_coparticipacao = $request->comcoparticipacao == "true" ? 1 : 0;
            $sem_coparticipacao = $request->semcoparticipacao == "true" ? 1 : 0;
            $desconto           = $request->status_desconto == "true" ? 1 : 0;
            $status_carencia           = $request->status_carencia == "true" ? 1 : 0;

            $apenasvalores      = request()->apenasvalores      == "true" ? 1 : 0;

            
            $tipo_documento     = $request->tipo_documento; // 'png' ou 'pdf'
            $ambulatorial       = $request->ambulatorial;
            $cidade             = $request->tabela_origem;
            $plano              = $request->plano;
            $operadora          = $request->operadora;
            $odonto             = $request->odonto;

            // Montando lógica SQL baseado em faixas etárias
            $sql = "";
            $chaves = [];
            $somar_linhas = 0;

            foreach ($request->faixas[0] as $k => $v) {
                if ($v != null && $v != 0) {
                    $sql .= " WHEN tabelas.faixa_etaria_id = {$k} THEN ${v} ";
                    $chaves[] = $k;
                    $somar_linhas += (int) $v;
                }
            }

            $linhas = count($chaves);

            // Informações básicas relacionadas às tabelas/origens/plano
            $cidade_nome = TabelaOrigens::find($cidade)->nome ?? 'N/A';
            $plano_nome  = Plano::find($plano)->nome ?? 'N/A';
            $cidade_uf   = TabelaOrigens::find($cidade)->uf ?? '';
            $admin_nome  = Administradora::find($operadora)->nome ?? 'N/A';

            // Configuração do layout
            //$layout = auth()->user()->layout_id ?? 1; // Default para layout 1
            $layout = 1;
	        $layout_user = in_array($layout, [1, 2, 3, 4]) ? $layout : 1;

            $frase = $plano_nome . ($odonto ? " c/ Odonto" : " s/ Odonto");

            // Dados para a tabela principal
            $dados = Tabela::select('tabelas.*')
                ->selectRaw("CASE $sql END AS quantidade")
                ->join('faixa_etarias', 'faixa_etarias.id', '=', 'tabelas.faixa_etaria_id')
                ->where('tabelas.tabela_origens_id', $cidade)
                ->where('tabelas.plano_id', $plano)
                ->where('tabelas.administradora_id', $operadora)
                ->where("tabelas.odonto", $odonto)
                ->where("acomodacao_id", "!=", 3)
                ->whereIn('tabelas.faixa_etaria_id', explode(',', implode(',', $chaves)))
                ->orderBy("tabelas.faixa_etaria_id")
                ->get();


            $carencia = Carencia::where("plano_id",$plano)->where("tabela_origens_id",$cidade)->get();
            $quantidade_carencia = Carencia::where("plano_id",$plano)->where("tabela_origens_id",$cidade)->count();    
            $status_desconto = request()->status_desconto == "true" ? 1 : 0;
            // Preparar view (Blade template a ser renderizado)
            $viewName = "cotacao.modelo1-mobile";


             $quantidade_cop = 0;
              if($apenasvalores == 0) {
                $pdf_excecao = PdfExcecao::where("plano_id",$plano)->where("tabela_origens_id",$cidade)->count();
                if($pdf_excecao == 1) {
                    $status_excecao = true;
                    $pdf_copar = PdfExcecao::where("plano_id",$plano)->where("tabela_origens_id",$cidade)->first();
                    $quantidade_cop = 1;
                } else {
                    $hasTabelaOrigens = Pdf::where('plano_id', $plano)
                        ->where('tabela_origens_id',$cidade)
                        ->exists();
                    if ($hasTabelaOrigens) {
                        $quantidade_cop = 1;
                        $pdf_copar = Pdf::where('plano_id', $plano)
                            ->where('tabela_origens_id',$cidade)
                            ->first();

                        if($pdf_copar->linha02) {
                            $itens = explode('|', $pdf_copar->linha02);
                            $itensFormatados = array_map(function($item) {
                                return trim($item); // Remove espaços extras
                            }, $itens);
                            $linha_01 = $itensFormatados[0];
                            $linha_02 = $itensFormatados[1];
                        }


                    } else {
                        $pdf_copar = Pdf::where('plano_id', $plano)->first();
                        if(isset($pdf_copar->linha02) && $pdf_copar->linha02) {
                            $quantidade_cop = 1;
                            $itens = explode('|', $pdf_copar->linha02);
                            $itensFormatados = array_map(function($item) {
                                return trim($item); // Remove espaços extras
                            }, $itens);
                            $linha_01 = $itensFormatados[0];
                            $linha_02 = $itensFormatados[1];
                        }

                    }
                }
            }    


            $valor_desconto = 0;
            if($status_desconto) {
                $desconto = Desconto::where('plano_id', $plano)->where('tabela_origens_id', $cidade)->where('administradora_id',$operadora)->first();
                if($desconto) {
                    $valor_desconto = $desconto->valor;
                }

            }    

            $view = \Illuminate\Support\Facades\View::make($viewName, [
                'dados' => $dados,
                'frase' => $frase,
                'cidade' => $cidade_nome,
                'plano_nome' => $plano_nome,
                'linha_01' => $linha_01,
                'linha_02' => $linha_02,
                'pdf' => $pdf_copar,
                'administradora' => $admin_nome,
                'com_coparticipacao' => $com_coparticipacao,
                'sem_coparticipacao' => $sem_coparticipacao,
                'apenasvalores' => $apenasvalores,
                'odonto' => $odonto,
                'linhas' => $linhas,
                'valor_desconto' => $valor_desconto,
                'desconto' => $status_desconto,
                'quantidade_carencia' => $quantidade_carencia,
                'carencia_texto' => $carencia,
                'carencia' => $status_carencia,
                'quantidade_copar' => $quantidade_cop,
                'nome' => "Richard",
                'celular' => "62 9 9358-1475",
                'image' => "users/1740672587_Foto0079.jpg",
                'status_excecao' => false,
            ])->render();

            // Prepara nome único para o arquivo
            $nome_arquivo = "orcamento_" . now()->format('d_m_Y_H_i_s') . "_" . uniqid();

            // Geração do Documento (PDF ou PNG)
            if ($tipo_documento === 'pdf') {
                $pdf = PDFFile::loadHTML($view)->setPaper('A3', 'portrait');
                return $pdf->download("{$nome_arquivo}.pdf");
            } elseif ($tipo_documento === 'png') {
                // Cria PDF temporário e converte para imagem PNG usando Ghostscript
                $pdfPath = storage_path("app/temp/{$nome_arquivo}.pdf");
                $pngPath = storage_path("app/temp/{$nome_arquivo}.png");

                // Salva o PDF temporariamente
                PDFFile::loadHTML($view)->setPaper('A3', 'portrait')->save($pdfPath);

                // Comando Ghostscript para converter o PDF em PNG
                $command = "gs -sDEVICE=pngalpha -r300 -o {$pngPath} {$pdfPath}";
                exec($command, $output, $return_var);

                if ($return_var !== 0 || !file_exists($pngPath)) {
                    return response()->json(['error' => 'Erro ao gerar a imagem.'], 500);
                }

                return response()->download($pngPath)->deleteFileAfterSend(true);
            } else {
                return response()->json(['error' => 'Tipo de documento inválido.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }



















    /**
     * Retorna todos os estados disponíveis.
     */
    public function getEstados()
    {
        $estados = TabelaOrigens::groupBy('uf')->select('uf')->get();

        return response()->json([
            'status' => 'success',
            'data' => $estados,
        ], 200);
    }

    /**
     * Retorna as cidades de um estado (consultando o UF).
     */
    public function getCidades(Request $request)
    {
        $uf = $request->input('uf');
        $cidades = TabelaOrigens::where('uf', $uf)->select('id', 'nome')->orderBy('nome')->get();

        return response()->json([
            'status' => 'success',
            'data' => $cidades,
        ], 200);
    }

    /**
     * Retorna os planos de saúde baseados em uma cidade e uma administradora.
     */
    public function getPlanos(Request $request)
    {

        $administradoraId = $request->input('operadora');
        $tabelaOrigemId = $request->input('tabela_origens_id');


	$plano_id = DB::table('administradora_planos')
            ->where('administradora_id', $administradoraId)
            ->where('tabela_origens_id', $tabelaOrigemId)
            ->pluck('plano_id');
	
	
	$planos = Plano::whereIn('id', $plano_id)->get();
	

        return response()->json([
            'status' => 'success',
            'data' => $planos,
        ], 200);
    }

    public function filtrarAdministradora(Request $request)
    {
        //return  response()->json($request->all());


        $cidade = $request->cidade_id;
	   //return "Olaaaaaaaaaaaaaaaaaaaaaaaa";

        $administradoraIds = DB::table('tabelas')
            ->select('administradora_id')
            ->where('tabela_origens_id', $cidade)
            ->where("administradora_id","!=",5)
            ->where("administradora_id","!=",3)
            ->groupBy('administradora_id')
            ->pluck('administradora_id');
        $operadoras = Administradora::whereIn('id', $administradoraIds)->get();
        
        return response()->json($operadoras);
    }



    /**
     * Realiza o cálculo da cotação com base nos dados fornecidos (cidade, plano, faixas etárias...).
     */
    public function calcularCotacao(Request $request)
    {



        $cidade = $request->input('tabela_origem');
        $plano = $request->input('plano');
        $operadora = $request->input('operadora');
        $faixas = $request->input('faixas') ?? [];




        $sql = '';
        $chaves = [];
        foreach ($faixas as $faixaId => $f) {
            if ($f['quantidade'] > 0) {
                $sql .= " WHEN tabelas.faixa_etaria_id = {$faixaId} THEN {$f['quantidade']}";
                $chaves[] = $faixaId;
            }
        }


        if (empty($chaves)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Nenhuma faixa etária válida fornecida.',
            ], 422);
        }

        $keys = implode(',', $chaves);

        // Realizando a consulta
        $dados = Tabela::select('tabelas.*')
            ->selectRaw("CASE {$sql} END AS quantidade")
            ->where('tabelas.tabela_origens_id', $cidade)
            ->where('tabelas.plano_id', $plano)
            ->where('tabelas.administradora_id', $operadora)
            ->where('tabelas.acomodacao_id',"!=",3)
            ->whereIn('tabelas.faixa_etaria_id', explode(',', $keys))
            ->orderBy('tabelas.faixa_etaria_id')

            ->get();



        return response()->json([
            'status' => 'success',
            'data' => [
                'detalhes' => $dados

            ],
        ], 200);
    }

}
