<?php

declare(strict_types=1);

// Desenvolvimento local: nao retirar esta restricao nesta etapa.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(404);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    http_response_code(405);
    exit;
}

ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/../services/formHelpers.php';

$cookieName = 'autolux_access';
$cookieOptions = [
    'expires' => 0,
    'path' => '/AutoLux/php-app/public/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
];

$error = '';
$email = is_string($_POST['email'] ?? null)
    ? trim($_POST['email'])
    : '';

$user = null;
$auth = null;
$formReady = false;
$pendingToken = null;

try {
    startFormSession();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Nao foi possivel preparar o formulario.');
    }

    $config = require __DIR__ . '/../config/config.php';

    require_once __DIR__ . '/../services/AuthService.php';
    require_once __DIR__ . '/../classes/LoginRateLimitRepository.php';

    $auth = new AuthService(
        new UserRepository($config['db']),
        new AuthTokenRepository($config['db']),
        new JwtService($config['jwt'])
    );

    // Um cookie existente continua a precisar de verificacao.
    if (isset($_COOKIE[$cookieName])) {
        try {
            if (!is_string($_COOKIE[$cookieName])) {
                throw new UnexpectedValueException('Cookie invalido.');
            }

            $user = $auth->authenticate($_COOKIE[$cookieName]);

        } catch (UnexpectedValueException $exception) {
            $expiredCookie = $cookieOptions;
            $expiredCookie['expires'] = time() - 3600;

            if (!setcookie($cookieName, '', $expiredCookie)) {
                throw new RuntimeException('Nao foi possivel limpar o cookie.');
            }
        }
    }

    $formReady = true;

    if ($method === 'POST' && $user === null) {
        $csrfValid = true;

        try {
            checkCsrf();

        } catch (InvalidArgumentException $exception) {
            $csrfValid = false;
            http_response_code(403);
            $error = 'Formulario expirado ou invalido. Atualize a pagina e tente novamente.';
        }

        if ($csrfValid) {
            $result = null;

            try {
                // O endereco vem da ligacao recebida pelo servidor.
                // Nao aceitar um IP fornecido no formulario ou na URL.
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';

                $limiter = new LoginRateLimitRepository($config['db']);

                // Chamar uma unica vez por submissao valida do formulario.
                $retryAfter = $limiter->consumeAttempt($email, $ip);

                if ($retryAfter > 0) {
                    // Nao chamar AuthService::login() enquanto ha um limite ativo.
                    http_response_code(429);
                    header('Retry-After: ' . $retryAfter);

                    $minutes = (int) ceil($retryAfter / 60);
                    $unit = $minutes === 1 ? ' minuto' : ' minutos';

                    $error = 'Demasiadas tentativas de login. Aguarde cerca de '
                        . $minutes . $unit . ' antes de tentar novamente.';

                } else {
                    $result = $auth->login(
                        $email,
                        $_POST['password'] ?? null
                    );
                }

            } catch (InvalidArgumentException $exception) {
                http_response_code(401);
                $error = 'Email ou palavra-passe incorretos.';

            } finally {
                unset($_POST['password']);
            }

            if ($result !== null) {
                $pendingToken = $result['token'];

                // Renovar a sessao auxiliar; o JWT continua no seu proprio cookie.
                if (!session_regenerate_id(true)) {
                    throw new RuntimeException('Nao foi possivel renovar a sessao.');
                }

                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                $cookieOptions['expires'] = $result['expires_at'];

                if (!setcookie($cookieName, $pendingToken, $cookieOptions)) {
                    throw new RuntimeException('Nao foi possivel enviar o cookie.');
                }

                // Um novo GET confirma que o navegador devolve o cookie.
                header('Location: login.php', true, 303);
                exit;
            }
        }
    }

} catch (Throwable $exception) {
    if ($auth !== null && $pendingToken !== null) {
        try {
            $auth->logout($pendingToken);

        } catch (Throwable $cleanupError) {
            error_log('AutoLux login: falhou a revogacao de recuperacao.');
        }
    }

    // Falhas tecnicas, incluindo no limitador, nao permitem avancar no login.
    http_response_code(503);
    $formReady = false;
    $user = null;
    $error = 'Nao foi possivel iniciar sessao. Confirme os servicos e a configuracao.';

    // Nunca registar passwords, tokens ou o rastreio completo da excecao.
    error_log('AutoLux login: ' . get_class($exception));
}

unset($_POST['password'], $pendingToken, $result);

$pageTitle = 'Login - AutoLux Pecas';
require __DIR__ . '/../partials/header.php';
?>

<main class="container">
    <section class="page-heading">
        <p class="eyebrow">AutoLux Pe&ccedil;as</p>
        <h1>Entrar no m&oacute;dulo de pe&ccedil;as</h1>
    </section>

    <?php if ($error !== ''): ?>
        <p class="notice error" role="alert">
            <?= h($error) ?>
        </p>
    <?php endif; ?>

    <?php if ($user !== null): ?>
        <section class="panel">
            <p class="notice success" role="status">
                Login confirmado. Bem-vindo, <?= h($user['name']) ?>!
            </p>

            <a class="button primary" href="index.php">
                Continuar para Pe&ccedil;as
            </a>
        </section>

    <?php elseif ($formReady): ?>
        <form class="panel form-grid" method="POST" action="login.php">
            <?= csrfField() ?>

            <label for="login-email">
                Email
                <input
                    id="login-email"
                    type="email"
                    name="email"
                    autocomplete="username"
                    maxlength="150"
                    value="<?= h($email) ?>"
                    required
                >
            </label>

            <label for="login-password">
                Palavra-passe
                <input
                    id="login-password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    maxlength="128"
                    required
                >
            </label>

            <button class="button primary" type="submit">Entrar</button>
        </form>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../partials/footer.php'; ?>