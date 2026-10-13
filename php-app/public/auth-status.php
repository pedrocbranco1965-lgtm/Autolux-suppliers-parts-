<?php

declare(strict_types=1);

ini_set('display_errors', '0');

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function sendAuthStatus(int $status, array $data): never
{
    http_response_code($status);

    if ($status === 401) {
        header('WWW-Authenticate: Bearer realm="autolux-pecas"');
    }

    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

// Endpoint interno para os testes locais no MAMP.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    sendAuthStatus(404, ['active' => false]);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');

    sendAuthStatus(405, [
        'active' => false,
        'message' => 'Utilize POST para verificar o token.',
    ]);
}

// Ler apenas o token do cabecalho.
// Nao usar cookies nem parametros da URL.
$authorization = $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? null;

if ($authorization === null && function_exists('getallheaders')) {
    foreach (getallheaders() ?: [] as $name => $value) {
        if (strcasecmp($name, 'Authorization') === 0) {
            $authorization = $value;
            break;
        }
    }
}

if (
    !is_string($authorization)
    || strlen($authorization) > 4103
    || preg_match(
        '/\ABearer ([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)\z/i',
        $authorization,
        $matches
    ) !== 1
) {
    sendAuthStatus(401, ['active' => false]);
}

$token = $matches[1];

unset($authorization, $matches);

try {
    require_once __DIR__ . '/../services/AuthService.php';

    $config = require __DIR__ . '/../config/config.php';

    $auth = new AuthService(
        new UserRepository($config['db']),
        new AuthTokenRepository($config['db']),
        new JwtService($config['jwt'])
    );

    try {
        // Verifica JWT, registo ativo, expiracao, revogacao e conta ativa.
        $user = $auth->authenticate($token);

    } catch (UnexpectedValueException $error) {
        // A mesma resposta para tokens invalidos, expirados ou revogados.
        sendAuthStatus(401, ['active' => false]);
    }

    sendAuthStatus(200, [
        'active' => true,
        'user_id' => (int) $user['id'],
    ]);

} catch (Throwable $error) {
    // Uma falha tecnica nao equivale a uma confirmacao de acesso.
    // Nunca registar o token nem o rastreio completo da excecao.
    error_log('AutoLux auth-status: ' . get_class($error));

    sendAuthStatus(503, [
        'active' => false,
        'message' => 'Nao foi possivel verificar o acesso.',
    ]);
}

