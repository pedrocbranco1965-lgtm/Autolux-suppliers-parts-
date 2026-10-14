<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function checkLimitTest(bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException('Resultado diferente do esperado.');
    }
}

$pdo = null;
$testKeys = [];
$cleanupReady = false;
$exitCode = 0;
$stage = 'preparar a ligacao';

try {
    require_once __DIR__ . '/../classes/LoginRateLimitRepository.php';

    $config = require __DIR__ . '/../config/config.php';
    $db = $config['db'];

    if ($db['name'] !== 'autolux_sales_db') {
        throw new RuntimeException('Base de dados inesperada.');
    }

    $limiter = new LoginRateLimitRepository($db);

    // Ligacao auxiliar apenas para inspecionar e limpar os dados do teste.
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'], $db['port'], $db['name'], $db['charset']
        ),
        $db['user'],
        $db['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Identificadores ficticios e diferentes em cada execucao.
    $tag = bin2hex(random_bytes(8));
    $suffix = implode(':', str_split($tag, 4));

    $ipA = '2001:db8:1:1:' . $suffix;
    $ipB = '2001:db8:1:2:' . $suffix;
    $ipC = '2001:db8:1:3:' . $suffix;

    $emails = [];

    for ($i = 0; $i <= 31; $i++) {
        $emails[$i] = 'limite-' . $tag . '-' . $i . '@example.test';

        $testKeys[] = [
            'email',
            hash('sha256', $emails[$i]),
        ];
    }

    foreach ([$ipA, $ipB, $ipC] as $ip) {
        $testKeys[] = [
            'ip',
            hash('sha256', inet_pton($ip)),
        ];
    }

    $read = $pdo->prepare(
        'SELECT attempts, window_ends_at
         FROM login_rate_limits
         WHERE scope = :scope AND identifier_hash = :hash'
    );

    $readCounter = static function (
        string $scope,
        string $hash
    ) use ($read): array|false {
        $read->execute([
            'scope' => $scope,
            'hash' => $hash,
        ]);

        return $read->fetch();
    };

    // Nao tocar num contador que ja existia antes deste teste.
    $stage = 'confirmar que os identificadores de teste sao novos';

    foreach ($testKeys as [$scope, $hash]) {
        checkLimitTest($readCounter($scope, $hash) === false);
    }

    $cleanupReady = true;

    $stage = 'permitir as primeiras cinco tentativas do email';

    for ($i = 0; $i < 5; $i++) {
        checkLimitTest(
            $limiter->consumeAttempt($emails[0], $ipA) === 0
        );
    }

    echo "[OK] Cinco tentativas do email permitidas.\n";

    $emailHash = hash('sha256', $emails[0]);

    $beforeEmail = $readCounter('email', $emailHash);

    $beforeIp = $readCounter(
        'ip',
        hash('sha256', inet_pton($ipA))
    );

    $stage = 'recusar a sexta tentativa sem alterar os contadores';

    $wait = $limiter->consumeAttempt($emails[0], $ipA);

    checkLimitTest($wait > 0 && $wait <= 900);

    checkLimitTest(
        $readCounter('email', $emailHash) === $beforeEmail
    );

    checkLimitTest(
        $readCounter('ip', hash('sha256', inet_pton($ipA)))
        === $beforeIp
    );

    echo "[OK] Sexta tentativa recusada, sem prolongar o periodo.\n";

    $stage = 'manter o limite do email quando muda o IP';

    checkLimitTest(
        $limiter->consumeAttempt($emails[0], $ipB) > 0
    );

    checkLimitTest(
        $readCounter('ip', hash('sha256', inet_pton($ipB)))
        === false
    );

    echo "[OK] Mudar de IP nao contornou o limite do email.\n";

    // Simular o fim do periodo APENAS no contador ficticio deste email.
    $stage = 'iniciar um novo periodo depois da expiracao';

    $expire = $pdo->prepare(
        "UPDATE login_rate_limits
         SET window_ends_at = :past
         WHERE scope = 'email' AND identifier_hash = :hash"
    );

    $expire->execute([
        'past' => gmdate('Y-m-d H:i:s', time() - 1),
        'hash' => $emailHash,
    ]);

    checkLimitTest(
        $limiter->consumeAttempt($emails[0], $ipA) === 0
    );

    $restarted = $readCounter('email', $emailHash);

    checkLimitTest(
        $restarted !== false
        && (int) $restarted['attempts'] === 1
    );

    echo "[OK] Periodo terminado: nova tentativa permitida.\n";

    $stage = 'testar o limite do IP com emails diferentes';

    for ($i = 1; $i <= 30; $i++) {
        checkLimitTest(
            $limiter->consumeAttempt($emails[$i], $ipC) === 0
        );
    }

    echo "[OK] Trinta tentativas do IP permitidas.\n";

    $beforeIpLimit = $readCounter(
        'ip',
        hash('sha256', inet_pton($ipC))
    );

    checkLimitTest(
        $limiter->consumeAttempt($emails[31], $ipC) > 0
    );

    checkLimitTest(
        $readCounter('ip', hash('sha256', inet_pton($ipC)))
        === $beforeIpLimit
    );

    checkLimitTest(
        $readCounter('email', hash('sha256', $emails[31]))
        === false
    );

    echo "[OK] Tentativa 31 do IP recusada, mesmo com outro email.\n";

} catch (Throwable $error) {
    fwrite(STDERR, '[FALHOU] Etapa: ' . $stage . PHP_EOL);
    fwrite(STDERR, 'Tipo de erro: ' . get_class($error) . PHP_EOL);

    $exitCode = 1;

} finally {
    // Fechar a ligacao do repository antes da limpeza auxiliar.
    unset($limiter);

    if ($pdo !== null && $cleanupReady) {
        try {
            $delete = $pdo->prepare(
                'DELETE FROM login_rate_limits
                 WHERE scope = :scope AND identifier_hash = :hash'
            );

            foreach ($testKeys as [$scope, $hash]) {
                $delete->execute([
                    'scope' => $scope,
                    'hash' => $hash,
                ]);

                checkLimitTest(
                    $readCounter($scope, $hash) === false
                );
            }

            echo "[OK] Contadores temporarios deste teste removidos.\n";

        } catch (Throwable $cleanupError) {
            fwrite(
                STDERR,
                "[ATENCAO] Nao foi possivel concluir a limpeza do teste.\n"
            );

            $exitCode = 1;
        }
    }
}

if ($exitCode === 0) {
    echo "Teste concluido. O login no navegador ainda nao foi alterado.\n";
}

exit($exitCode);

