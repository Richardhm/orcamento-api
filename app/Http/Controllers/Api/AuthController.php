<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
      // Função de Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas'
            ], 401);
        }

        // Remove tokens antigos (opcional)
        $user->tokens()->delete();

        // Cria token
        $token = $user->createToken('ionic-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
    
     // Função de Logout
    public function logout(Request $request)
    {

          \Log::info('Logout recebido', [
            'Usuário' => $request->user(),                     // Verificar se o usuário autenticado é identificado
            'CSRF Header' => $request->header('X-XSRF-TOKEN'), // Token CSRF recebido no header
            'Cookies' => $request->cookies->all(),             // Cookies enviados pelo cliente
            ]);


        //return response()->json($request->all());
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout realizado com sucesso']);
    }    
}
