<?php
// O PHP nao le o .env da API Node. Para configuracao local, copiar
// config.local.example.php para config.local.php (ignorado pelo Git).
$env = static function (string $key, string $default): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
};
$config = [
    'db' => [
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => $env('DB_PORT', '8889'),
        'name' => $env('DB_NAME', 'autolux_sales_db'),
        'user' => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', 'root'),
        'charset' => 'utf8mb4',
    ],
    'supplier_api_url' => $env('SUPPLIER_API_URL', 'http://localhost:3000/api'),
    'frontend_url' => $env('FRONTEND_URL', 'http://localhost:5173/'),
];
$localPath = __DIR__ . '/config.local.php';
if (is_file($localPath)) {
    $local = require $localPath;
    if (!is_array($local)) {
        throw new RuntimeException('config.local.php deve devolver um array.');
    }
    $config = array_replace_recursive($config, $local);
}
return $config;
