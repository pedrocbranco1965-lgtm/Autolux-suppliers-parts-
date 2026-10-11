<?php

declare(strict_types=1);

// Ferramenta local: nao permitir execucao pelo navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Le os dados pelo terminal, sem mostrar a palavra-passe.
function askLoginInput(string $label, bool $hidden = false): string
{
    fwrite(STDOUT, $label);

    $reader = $hidden
        ? 'IFS= read -r -s value || exit 1; printf "%s" "$value"'
        : 'IFS= read -r value || exit 1; printf "%s" "$value"';

    $process = proc_open(
        ['/bin/bash', '--noprofile', '--norc', '-c', $reader],
        [0 => STDIN, 1 => ['pipe', 'w'], 2 => STDERR],
        $pipes
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Nao foi possivel ler os dados.');
    }

    try {
        $value = stream_get_contents($pipes[1]);
    } finally {
        fclose($pipes[1]);
        $status = proc_close($process);

        if ($hidden) {
            fwrite(STDOUT, PHP_EOL);
        }
    }

    if ($status !== 0 || $value === false) {
        throw new RuntimeException('Leitura interrompida.');
    }

    return $value;
}

$auth = null;
$token = null;
$exitCode = 0;
$stage = 'preparar o teste';

try {
    if (
        $argc !== 1
        || !function_exists('proc_open')
        || !is_executable('/bin/bash')
        || !stream_isatty(STDIN)
    ) {
        throw new RuntimeException(
            'Execute sem argumentos num terminal interativo do Mac.'
        );
    }

    require_once __DIR__ . '/../services/AuthService.php';

    $config = require __DIR__ . '/../config/config.php';

    $users = new UserRepository($config['db']);
    $tokens = new AuthTokenRepository($config['db']);
    $jwt = new JwtService($config['jwt']);
    $auth = new AuthService($users, $tokens, $jwt);

    echo "AutoLux - Teste local de login e logout\n";
    echo "Use a conta que ja criou. Este teste nao cria contas novas.\n";

    $stage = 'ler as credenciais';
    $email = askLoginInput('Email: ');
    $password = askLoginInput('Palavra-passe (oculta): ', true);

    $stage = 'verificar as credenciais e registar o token';
    $result = $auth->login($email, $password);
    $token = $result['token'];
    unset($password);

    $userId = (int) $result['user']['id'];
    echo '[OK] Login aceite para o utilizador ' . $userId . ".\n";

    $stage = 'validar o acesso com authenticate';
    $user = $auth->authenticate($token);

    if ((int) $user['id'] !== $userId) {
        throw new RuntimeException('A conta recuperada nao corresponde.');
    }

    echo "[OK] Token aceite por authenticate().\n";

    $stage = 'terminar a sessao de teste';
    $auth->logout($token);
    echo "[OK] Logout executado.\n";

    // Confirmar que a recusa nao resulta simplesmente de o JWT ter expirado.
    $stage = 'confirmar a recusa depois do logout';
    $jwt->verify($token);

    $rejected = false;

    try {
        $auth->authenticate($token);
    } catch (UnexpectedValueException $error) {
        $rejected = true;
    }

    if (!$rejected) {
        throw new RuntimeException('O acesso deveria ter sido recusado.');
    }

    echo "[OK] Token rejeitado depois do logout.\n";

} catch (Throwable $error) {
    fwrite(STDERR, '[FALHOU] Etapa: ' . $stage . PHP_EOL);
    fwrite(STDERR, 'Tipo de erro: ' . get_class($error) . PHP_EOL);

    if ($error instanceof InvalidArgumentException) {
        fwrite(STDERR, "Confirme os dados introduzidos, sem os partilhar aqui.\n");
    }

    $exitCode = 1;

} finally {
    unset($password);

    // Tentar revogar o token mesmo se uma verificacao posterior falhar.
    if ($auth !== null && $token !== null) {
        try {
            $auth->logout($token);
        } catch (Throwable $cleanupError) {
            fwrite(STDERR, "[ATENCAO] Nao foi possivel confirmar a revogacao final.\n");
            $exitCode = 1;
        }
    }
}

if ($exitCode === 0) {
    echo "Teste concluido. O token deste teste ficou revogado.\n";
}

exit($exitCode);
