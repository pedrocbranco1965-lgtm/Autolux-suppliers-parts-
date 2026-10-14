<?php

// AutoLux - modelo de configuracao PHP para desenvolvimento local.
// Numa nova instalacao, copiar para config.local.php e ajustar os valores.
// Nao substituir um config.local.php que ja esteja configurado.
//
// Gerar as chaves da propria instalacao com tools/generate_jwt_keys.php.
// No ficheiro local, substituir SEU_UTILIZADOR pelos caminhos corretos.
// Nao colocar palavras-passe reais ou chaves privadas neste modelo.

return [
    // Primeira base: vendas, pecas, contas e controlo da autenticacao.
    // root/root sao valores de exemplo para MAMP local.
    'db' => [
        'host' => '127.0.0.1',
        'port' => '8889',
        'name' => 'autolux_sales_db',
        'user' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4',
    ],

    // Endereco da API Node.
    'supplier_api_url' => 'http://127.0.0.1:3000/api',

    // Endereco do site de viaturas React.
    'frontend_url' => 'http://localhost:5173/',

    // Autenticacao JWT: caminhos, nunca o conteudo das chaves.
    'jwt' => [
        'private_key_path' =>
            '/Users/SEU_UTILIZADOR/autolux-secrets/jwt/jwt-private.pem',

        'public_key_path' =>
            '/Users/SEU_UTILIZADOR/autolux-secrets/jwt/jwt-public.pem',

        'algorithm' => 'RS256',
        'issuer' => 'autolux-php',
        'audience' => 'autolux-pecas',

        // 900 segundos = 15 minutos.
        // Manter coerente com a validacao do JWT no Node.
        'ttl_seconds' => 900,
    ],
];