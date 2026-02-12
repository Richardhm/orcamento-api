<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function atualizarImagem(Request $request)
    {
        // Validação do arquivo
        $validator = Validator::make($request->all(), [
            'foto' => 'required|file|mimes:jpeg,png,jpg|max:5120', // Máx. 5MB
        ]);

        if ($validator->fails()) {
            return response()->json(['erro' => $validator->errors()->first()], 422);
        }
        \Log::info('Arquivo recebido:', [$request->file('foto')]);


        // Recuperar o usuário autenticado
        $user = $request->user();
        $path = $request->file('foto')->store('fotos_perfil', 'public');
        // Remover imagem antiga (se existir)
        // if ($user->imagem) {
        //     Storage::delete($user->imagem);
        // }

        // Salvar nova imagem
        ///$path = $request->file('foto')->store('fotos_perfil');

        if (!$path) {
            \Log::error('Erro ao salvar o arquivo.');
            return response()->json(['erro' => 'Não foi possível realizar o upload da imagem'], 500);
        }


        // Atualizar o campo no banco de dados
        $user->imagem = $path;
        $user->save();

        // Retornar a URL da imagem
        $url = Storage::url($path);

        return response()->json(['foto_url' => $url], 200);
    }
}
