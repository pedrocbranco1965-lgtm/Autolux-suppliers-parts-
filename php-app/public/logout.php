<?php

declare(strict_types=1);

// Desenvolvimento local: manter a mesma restricao de login.php.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}

ini_set('display_errors', '0');
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

// Abrir o endereco no navegador nao deve terminar uma sessao.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Utilize o botao Terminar sessao.');
}

try {
    require_once __DIR__ . '/../services/formHelpers.php';
    startFormSession();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Nao foi possivel abrir a sessao auxiliar.');
    }

    try {
        checkCsrf();
    } catch (InvalidArgumentException $error) {
        http_response_code(403);
        exit('Formulario expirado ou invalido. Atualize a pagina e tente novamente.');
    }

    $token = $_COOKIE['autolux_access'] ?? null;

    if (is_string($token) && $token !== '') {
        require_once __DIR__ . '/../services/AuthService.php';
        $config = require __DIR__ . '/../config/config.php';

        $auth = new AuthService(
            new UserRepository($config['db']),
            new AuthTokenRepository($config['db']),
            new JwtService($config['jwt'])
        );

        // Primeiro revogar o acesso no servidor.
        // Se a BD falhar, nao apresentar a saida como concluida.
        $auth->logout($token);
    }

    // Depois pedir ao navegador para remover o cookie JWT.
    // O nome e o caminho devem coincidir com os usados no login.php.
    if (!setcookie('autolux_access', '', [
        'expires' => time() - 3600,
        'path' => '/AutoLux/php-app/public/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ])) {
        throw new RuntimeException('Nao foi possivel remover o cookie JWT.');
    }

    // Limpar tambem a sessao auxiliar de formularios e mensagens.
    $_SESSION = [];
    $params = session_get_cookie_params();

    if (!setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?: 'Lax',
    ])) {
        throw new RuntimeException('Nao foi possivel remover o cookie da sessao.');
    }

    if (!session_destroy()) {
        throw new RuntimeException('Nao foi possivel terminar a sessao auxiliar.');
    }

    header('Location: login.php', true, 303);
    exit;

} catch (Throwable $error) {
    // Nunca registar o token, passwords ou o rastreio completo do erro.
    error_log('AutoLux logout: ' . get_class($error));
    http_response_code(503);
    echo 'Nao foi possivel concluir a saida. Confirme os servicos e tente novamente.';
}

