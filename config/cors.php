<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Caminhos Permitidos
    |--------------------------------------------------------------------------
    |
    | Especifica as rotas da aplicação que podem aceitar requisições CORS.
    | Geralmente, usamos 'api/*' para proteger todas as rotas do prefixo "api".
    |
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*','fotos_perfil/*','public/*','public/fotos_perfil/*'],

    /*
    |--------------------------------------------------------------------------
    | Métodos Permitidos
    |--------------------------------------------------------------------------
    |
    | Especifica os métodos HTTP permitidos (GET, POST, PUT, DELETE, etc.).
    | '*' ou uma lista explícita de métodos (como ['GET', 'POST']).
    |
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Origens Permitidas
    |--------------------------------------------------------------------------
    |
    | Domínios permitidos para fazer chamadas para o backend.
    | Substitua localhost:8100 pelo domínio do seu frontend em produção.
    |
    */
    'allowed_origins' => [
        'http://localhost:8100',
        'http://localhost:4173',
		'http://127.0.0.1:8100',
        'https://app.bmsys.com.br',
        'https://cotafacil.bmsys.com.br',
		'*'
    ],

    /*
    |--------------------------------------------------------------------------
    | Padrões de Origem Permitidos
    |--------------------------------------------------------------------------
    |
    | Você pode especificar padrões de origem permitidos (como wildcards).
    |
    */
    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Cabeçalhos Permitidos
    |--------------------------------------------------------------------------
    |
    | Especifique os headers HTTP permitidos nas requisições.
    | '*' permite que qualquer header seja enviado.
    |
    */
    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Cabeçalhos Visíveis para o Cliente
    |--------------------------------------------------------------------------
    |
    | Especifique quais headers podem ser acessados no lado do cliente.
    |
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Cacheamento do Preflight
    |--------------------------------------------------------------------------
    |
    | Tempo (em segundos) que o resultado da requisição preflight pode
    | ser armazenado no cache do navegador.
    |
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Permitir Credenciais (Cookies/Autenticação)
    |--------------------------------------------------------------------------
    |
    | Se você deseja permitir cookies ou tokens de autenticação em
    | requisições CORS, defina como `true`.
    |
    */
    'supports_credentials' => false,

];
