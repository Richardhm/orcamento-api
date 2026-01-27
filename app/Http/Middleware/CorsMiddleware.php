<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Responde imediatamente às requisições preflight (OPTIONS)
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204, $this->getCorsHeaders());
        }

        // Adicionar os cabeçalhos CORS à resposta das requisições principais
        $response = $next($request);

        foreach ($this->getCorsHeaders() as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }

    private function getCorsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin' => 'http://localhost:8100', // Domínio do front-end permitido
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS', // Métodos permitidos
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-XSRF-TOKEN', // Cabeçalhos permitidos
            'Access-Control-Allow-Credentials' => 'true', // Permitir envio de credenciais (cookies)
        ];
    }
}