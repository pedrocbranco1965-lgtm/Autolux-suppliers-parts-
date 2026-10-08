<?php

declare(strict_types=1);

// Apenas para testes locais no terminal.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$userId = 1;
$tokens = null;
$jti = null;
$recordId = null;
$exitCode = 0;
$stage = 'carregar os ficheiros e a configuracao';

try {
    require_once __DIR__ . '/../classes/UserRepository.php';
    require_once __DIR__ . '/../classes/AuthTokenRepository.php';
    require_once __DIR__ . '/../services/JwtService.php';

    $config = require __DIR__ . '/../config/config.php';

    $stage = 'confirmar a conta numero 1';

    $users = new UserRepository($config['db']);
    $user = $users->findById($userId);

    if ($user === null || (int) $user['active'] !== 1) {
        throw new RuntimeException('A conta de teste nao existe ou esta inativa.');
    }

    echo "[OK] Conta numero 1 encontrada e ativa.\n";

    $stage = 'emitir e verificar o JWT de teste';

    $jwt = new JwtService($config['jwt']);
    $issued = $jwt->issue($userId);
    $claims = $jwt->verify($issued['token']);

    if ($claims['sub'] !== (string) $userId) {
        throw new RuntimeException('O identificador do utilizador nao corresponde.');
    }

    $jti = $claims['jti'];

    echo "[OK] JWT emitido e verificado.\n";

    $stage = 'consultar o token antes do registo';

    $tokens = new AuthTokenRepository($config['db']);

    if ($tokens->isActive($userId, $jti)) {
        throw new RuntimeException('O token novo nao deveria estar registado.');
    }

    echo "[OK] Token ainda nao registado: inativo na base.\n";

    $stage = 'registar o token na base de dados';

    $recordId = $tokens->register(
        $userId,
        $jti,
        $claims['iat'],
        $claims['exp']
    );

    if (!$tokens->isActive($userId, $jti)) {
        throw new RuntimeException('O token registado deveria estar ativo.');
    }

    echo "[OK] Token registado e ativo na base.\n";

    $stage = 'revogar o token e confirmar o resultado';

    $tokens->revoke($userId, $jti);

    if ($tokens->isActive($userId, $jti)) {
        throw new RuntimeException('O token revogado nao pode continuar ativo.');
    }

    echo "[OK] Token revogado: deixou de estar ativo.\n";

    // A revogacao na BD nao altera a assinatura criptografica do JWT.
    $stage = 'verificar a assinatura depois da revogacao';

    $jwt->verify($issued['token']);

    echo "[OK] A assinatura continua valida apos a revogacao.\n";

} catch (Throwable $error) {
    fwrite(STDERR, '[FALHOU] Etapa: ' . $stage . PHP_EOL);
    fwrite(STDERR, 'Tipo de erro: ' . get_class($error) . PHP_EOL);

    $exitCode = 1;

} finally {
    // Repetir a revogacao e seguro: atuamos apenas no registo deste teste.
    // Tambem tentamos revogar se um teste falhou depois da insercao.
    if ($tokens !== null && $recordId !== null && $jti !== null) {
        try {
            $tokens->revoke($userId, $jti);
        } catch (Throwable $cleanupError) {
            fwrite(STDERR, "[ATENCAO] Nao foi possivel confirmar a revogacao final.\n");
            fwrite(STDERR, 'Registo de teste: ' . $recordId . PHP_EOL);

            $exitCode = 1;
        }
    }
}

if ($exitCode === 0) {
    echo 'Teste concluido. Registo de teste: ' . $recordId . " (revogado).\n";
}

exit($exitCode);
