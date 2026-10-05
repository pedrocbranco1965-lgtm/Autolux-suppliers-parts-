<?php
// Apenas desenvolvimento local. Nao publicar credenciais no GitHub.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '8889',
        'name' => 'autolux_sales_db',
        'user' => 'root',
        'password' => 'root', // '' e permitido quando a instalacao local o exige.
    ],
    'supplier_api_url' => 'http://localhost:3000/api',
    'frontend_url' => 'http://localhost:5173/',
];
