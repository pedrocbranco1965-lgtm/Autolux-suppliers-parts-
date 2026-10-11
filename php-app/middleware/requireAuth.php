<?php
declare(strict_types=1);

// Desenvolvimento local: mesma restricao que aplicamos em login.php.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}

ini_set('display_errors', '0');

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

$authenticatedUser = null;
$accessToken = $_COOKIE['autolux_access'] ?? null;

// Sem token: nao mostrar a pagina protegida.
if (!is_string($accessToken) || $accessToken === '') {
    header('Location: /AutoLux/php-app/public/login.php', true, 303);
    exit;
}

try {
    require_once __DIR__ . '/../services/AuthService.php';

    $config = require __DIR__ . '/../config/config.php';

    $authService = new AuthService(
        new UserRepository($config['db']),
        new AuthTokenRepository($config['db']),
        new JwtService($config['jwt'])
    );

    // Verifica assinatura, validade, conta ativa e registo nao revogado.
    $authenticatedUser = $authService->authenticate($accessToken);

        
    // Preparar a sessao auxiliar e o CSRF antes de apresentar HTML.
    require_once __DIR__ . '/../services/formHelpers.php';

    startFormSession();

    if (
        session_status() !== PHP_SESSION_ACTIVE
        || !is_string($_SESSION['csrf'] ?? null)
        || $_SESSION['csrf'] === ''
    ) {
        throw new RuntimeException(
            'Nao foi possivel preparar a sessao dos formularios.'
        );
    }

} catch (UnexpectedValueException $error) {
    // Token invalido, expirado ou revogado: voltar ao login.
    // login.php ja trata de limpar um cookie JWT invalido.
    header('Location: /AutoLux/php-app/public/login.php', true, 303);
    exit;

} catch (Throwable $error) {
    // Uma falha tecnica nunca deve permitir o acesso por omissao.
    error_log('AutoLux acesso: ' . get_class($error));

    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');

    echo 'Nao foi possivel verificar o acesso. Confirme os servicos e a configuracao.';
    exit;
}

// Nao deixar o token disponivel desnecessariamente no resto da pagina.
unset($accessToken);
