<?php

declare(strict_types=1);

use Firebase\JWT\SignatureInvalidException;

// Executar apenas no terminal, nunca pelo navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$stage = 'carregar a configuracao';

try {
    require_once __DIR__ . '/../services/JwtService.php';

    $config = require __DIR__ . '/../config/config.php';

    if (!is_array($config['jwt'] ?? null)) {
        throw new RuntimeException('Configuracao JWT ausente.');
    }

    $jwt = new JwtService($config['jwt']);

    // Apenas um ID de teste. Nao consulta a tabela users.
    $stage = 'emitir o token de teste';
    $issued = $jwt->issue(1);

    $stage = 'verificar o token original';
    $claims = $jwt->verify($issued['token']);

    if (
        $claims['sub'] !== '1'
        || $claims['jti'] !== $issued['claims']['jti']
        || $claims['exp'] !== $issued['claims']['exp']
    ) {
        throw new RuntimeException(
            'Os dados verificados nao correspondem.'
        );
    }

    echo "[OK] Token emitido e verificado.\n";
    echo "[OK] Identificador e datas recuperados corretamente.\n";

    // Alterar uma COPIA da assinatura, sem mexer nas chaves.
    $stage = 'recusar o token com assinatura alterada';

    $segments = explode('.', $issued['token']);

    $segments[2][0] = $segments[2][0] === 'A' ? 'B' : 'A';

    $alteredToken = implode('.', $segments);

    $rejected = false;

    try {
        $jwt->verify($alteredToken);
    } catch (SignatureInvalidException $error) {
        $rejected = true;
    }

    if (!$rejected) {
        throw new RuntimeException(
            'O token alterado nao foi recusado.'
        );
    }

    echo "[OK] Token com assinatura alterada recusado.\n";
    echo "Teste concluido. Nenhum token foi gravado na base de dados ou mostrado.\n";

} catch (Throwable $error) {
    // Nao apresentar chaves, tokens ou o rastreio completo da excecao.
    fwrite(STDERR, '[FALHOU] Etapa: ' . $stage . PHP_EOL);
    fwrite(STDERR, 'Tipo de erro: ' . get_class($error) . PHP_EOL);

    exit(1);
}
