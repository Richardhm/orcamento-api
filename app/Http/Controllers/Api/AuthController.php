<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
	public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ], 401);
        }

        $user = Auth::user();
        
        // Pegar duração configurada pelo desenvolvedor
        $horasConfiguracao = config('auth.token_lifetime_hours', 2); // Padrão: 2h
        $minutos = $horasConfiguracao * 60;
        
        // Revogar tokens existentes (opcional - remova se quiser múltiplas sessões)
        $user->tokens()->delete();
        
        // Criar token com duração configurada
        $token = $user->createToken(
            'auth_token',
            ['*'],
            Carbon::now()->addMinutes($minutos)
        )->plainTextToken;

        $expiresAt = Carbon::now()->addMinutes($minutos);

        return response()->json([
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString(),
            'expires_in' => $minutos * 60, // em segundos
            'configured_hours' => $horasConfiguracao, // Para debug
        ]);
    }
	
	  public function me(Request $request)
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();

        // Verificar se o token expirou
        if ($token && $token->expires_at && Carbon::now()->isAfter($token->expires_at)) {
            $token->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token expirado'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
            'token_info' => [
                'expires_at' => $token->expires_at?->toISOString(),
                'created_at' => $token->created_at->toISOString(),
                'last_used_at' => $token->last_used_at?->toISOString(),
            ]
        ]);
    }
	
	public function refreshToken(Request $request)
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();
        
        // Obter nova duração (mesma configuração)
        $horasConfiguracao = config('auth.token_lifetime_hours', 2);
        $minutos = $horasConfiguracao * 60;

        // Revogar token atual
        $currentToken->delete();

        // Criar novo token
        $token = $user->createToken(
            'auth_token',
            ['*'],
            Carbon::now()->addMinutes($minutos)
        )->plainTextToken;

        $expiresAt = Carbon::now()->addMinutes($minutos);

        return response()->json([
            'success' => true,
            'message' => 'Token renovado com sucesso',
            'token' => $token,
            'expires_at' => $expiresAt->toISOString(),
            'expires_in' => $minutos * 60,
            'configured_hours' => $horasConfiguracao,
        ]);
    }
	
	
	
	
	
	
      // Função de Login
    public function loginolllllllllllllllll(Request $request)
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
	
	 /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso'
        ]);
    }

    /**
     * Logout de todos os dispositivos
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado em todos os dispositivos'
        ]);
    }
	
	
    
     // Função de Logout
    public function logoutooooooooooooooooooooooooo(Request $request)
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
