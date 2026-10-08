<?php

declare(strict_types=1);

// Ferramenta local: nao permitir execucao pelo navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$createdFiles = [];
$previousMask = umask(0077);
$exitCode = 0;

try {
    if (!extension_loaded('openssl')) {
        throw new RuntimeException(
            'O OpenSSL nao esta ativo neste PHP.'
        );
    }

    $home = getenv('HOME');

    if (!is_string($home) || $home === '' || $home[0] !== '/') {
        throw new RuntimeException(
            'Nao foi possivel localizar a pasta pessoal.'
        );
    }

    // As chaves ficam fora do MAMP, do AutoLux e do repositorio Git.
    $baseDirectory = rtrim($home, '/') . '/autolux-secrets';
    $directory = $baseDirectory . '/jwt';

    foreach ([$baseDirectory, $directory] as $folder) {
        if (is_link($folder)) {
            throw new RuntimeException(
                'A pasta das chaves nao pode ser um atalho simbolico.'
            );
        }

        if (!is_dir($folder) && !mkdir($folder, 0700)) {
            throw new RuntimeException(
                'Nao foi possivel criar a pasta das chaves.'
            );
        }

        if (!chmod($folder, 0700)) {
            throw new RuntimeException(
                'Nao foi possivel restringir o acesso a pasta.'
            );
        }
    }

    $privatePath = $directory . '/jwt-private.pem';
    $publicPath = $directory . '/jwt-public.pem';

    // Nunca substituir chaves existentes ao repetir este comando.
    foreach ([$privatePath, $publicPath] as $path) {
        if (file_exists($path) || is_link($path)) {
            throw new RuntimeException(
                'Ja existe um ficheiro de chaves. Nada foi substituido.'
            );
        }
    }

    $key = openssl_pkey_new([
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'private_key_bits' => 3072,
    ]);

    if ($key === false) {
        throw new RuntimeException(
            'Nao foi possivel gerar a chave RSA.'
        );
    }

    $privatePem = '';

    if (!openssl_pkey_export($key, $privatePem)) {
        throw new RuntimeException(
            'Nao foi possivel preparar a chave privada.'
        );
    }

    $details = openssl_pkey_get_details($key);

    if ($details === false || !is_string($details['key'] ?? null)) {
        throw new RuntimeException(
            'Nao foi possivel obter a chave publica.'
        );
    }

    $files = [
        $privatePath => $privatePem,
        $publicPath => $details['key'],
    ];

    foreach ($files as $path => $contents) {
        // O modo x so cria ficheiros novos; nao sobrescreve os existentes.
        $handle = @fopen($path, 'x');

        if ($handle === false) {
            throw new RuntimeException(
                'Nao foi possivel criar um dos ficheiros.'
            );
        }

        $createdFiles[] = $path;

        try {
            if (
                !chmod($path, 0600)
                || fwrite($handle, $contents) !== strlen($contents)
                || !fflush($handle)
            ) {
                throw new RuntimeException(
                    'Nao foi possivel guardar as chaves com seguranca.'
                );
            }
        } finally {
            fclose($handle);
        }
    }

    // Mostrar apenas nomes e localizacao, nunca o conteudo das chaves.
    fwrite(STDOUT, "Chaves RSA criadas com sucesso.\n");
    fwrite(STDOUT, 'Pasta: ' . $directory . PHP_EOL);
    fwrite(STDOUT, "Ficheiros: jwt-private.pem e jwt-public.pem\n");

} catch (Throwable $error) {
    // Em caso de falha, remover apenas ficheiros criados nesta execucao.
    foreach ($createdFiles as $path) {
        @unlink($path);
    }

    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    $exitCode = 1;

} finally {
    umask($previousMask);
}

exit($exitCode);