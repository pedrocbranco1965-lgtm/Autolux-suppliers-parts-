<?php
require_once __DIR__ . '/../classes/Input.php';
require_once __DIR__ . '/../classes/Money.php';

function h(mixed $value): string
{
    return htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function startFormSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name('autolux_forms');
        session_start([
            'use_strict_mode' => 1,
            'cookie_httponly' => true,
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'cookie_samesite' => 'Lax',
        ]);
    }
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function checkCsrf(): void
{
    $token = $_POST['csrf'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
        throw new InvalidArgumentException('Formulario expirado ou invalido. Atualize a pagina e tente novamente.');
    }
}

function flashRedirect(string $page, string $message): never
{
    // As chamadas usam apenas caminhos literais internos; nao recebem URLs do utilizador.
    $_SESSION['flash'] = $message;
    header('Location: ' . $page, true, 303);
    exit;
}

function takeFlash(): string
{
    $message = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $message;
}

function displayError(Throwable $error): string
{
    if ($error instanceof InvalidArgumentException) {
        return $error->getMessage();
    }
    if ($error instanceof RuntimeException && $error->getCode() === 503) {
        return $error->getMessage();
    }
    if ($error instanceof PDOException) {
        error_log('AutoLux: ' . $error->getMessage());
        if (($error->errorInfo[1] ?? null) === 1062) {
            return 'Ja existe um registo com esta referencia.';
        }
        return 'Nao foi possivel aceder aos dados. Confirme o MySQL do MAMP e a configuracao.';
    }
    error_log('AutoLux: ' . $error->getMessage());
    return 'Nao foi possivel concluir a operacao. Confirme os servicos e consulte os registos de erro.';
}
