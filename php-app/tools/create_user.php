<?php

declare(strict_types=1);

// Nunca permitir a criacao de contas atraves do navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Le uma resposta no terminal. No modo oculto nao mostra a palavra-passe.
function ask(string $label, bool $hidden = false): string
{
    fwrite(STDOUT, $label);

    // Comandos fixos: nunca colocamos os dados do utilizador neste texto.
    $reader = $hidden
        ? 'IFS= read -r -s value || exit 1; printf "%s" "$value"'
        : 'IFS= read -r value || exit 1; printf "%s" "$value"';

    $process = proc_open(
        ['/bin/bash', '--noprofile', '--norc', '-c', $reader],
        [0 => STDIN, 1 => ['pipe', 'w'], 2 => STDERR],
        $pipes
    );

    if (!is_resource($process)) {
        throw new RuntimeException(
            'Nao foi possivel ler os dados no terminal.'
        );
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
        throw new RuntimeException(
            'Leitura interrompida. Nenhuma conta foi criada.'
        );
    }

    return $value;
}

try {
    if ($argc !== 1) {
        throw new InvalidArgumentException(
            'Execute sem argumentos. Os dados serao pedidos a seguir.'
        );
    }

    if (
        !function_exists('proc_open')
        || !is_executable('/bin/bash')
        || !stream_isatty(STDIN)
    ) {
        throw new RuntimeException(
            'Use um terminal interativo no Mac, com Bash e proc_open disponiveis.'
        );
    }

    require_once __DIR__ . '/../classes/UserRepository.php';

    $config = require __DIR__ . '/../config/config.php';

    $users = new UserRepository($config['db']);

    fwrite(STDOUT, "AutoLux - Criar conta local\n");

    fwrite(
        STDOUT,
        'Base de dados: ' . $config['db']['name'] . PHP_EOL
    );

    $name = Input::text(
        ask('Nome: '),
        'Nome',
        120
    );

    $email = strtolower(
        Input::text(
            ask('Email: '),
            'Email',
            150
        )
    );

    // Este script e administrativo e local; nao e o formulario de login.
    if ($users->findForAuthenticationByEmail($email) !== null) {
        throw new InvalidArgumentException(
            'Ja existe uma conta com este email.'
        );
    }

    $useArgon2 = in_array('argon2id', password_algos(), true);

    fwrite(
        STDOUT,
        "Escolha uma palavra-passe nova, com 15 a 128 caracteres.\n"
    );

    if (!$useArgon2) {
        fwrite(
            STDOUT,
            "Neste PHP, o bcrypt limita tambem o comprimento a 72 bytes.\n"
        );
    }

    fwrite(
        STDOUT,
        "A palavra-passe nao aparece enquanto escreve.\n"
    );

    $password = ask('Palavra-passe: ', true);

    $confirmation = ask('Repita a palavra-passe: ', true);

    if (!hash_equals($password, $confirmation)) {
        throw new InvalidArgumentException(
            'As palavras-passe nao coincidem.'
        );
    }

    // Nao usar trim() na palavra-passe: os espacos tambem fazem parte dela.
    $length = preg_match_all('/./us', $password);

    if (
        $length === false
        || $length < 15
        || $length > 128
        || str_contains($password, "\0")
    ) {
        throw new InvalidArgumentException(
            'A palavra-passe deve ter entre 15 e 128 caracteres validos.'
        );
    }

    if (!$useArgon2 && strlen($password) > 72) {
        throw new InvalidArgumentException(
            'Com bcrypt, use no maximo 72 bytes; nao vamos cortar a palavra-passe.'
        );
    }

    $passwordHash = $useArgon2
        ? password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 19456,
            'time_cost' => 2,
            'threads' => 1,
        ])
        : password_hash($password, PASSWORD_BCRYPT, [
            'cost' => 12,
        ]);

    unset($password, $confirmation);

    // Apenas o hash segue para o repository e para a base de dados.
    $userId = $users->createWithPasswordHash(
        $name,
        $email,
        $passwordHash
    );

    unset($passwordHash);

    fwrite(
        STDOUT,
        'Conta criada com sucesso. ID: ' . $userId . PHP_EOL
    );

    fwrite(
    STDOUT,
    "Para entrar, abra a pagina login.php no navegador.\n"
);

} catch (PDOException $error) {
    fwrite(
        STDERR,
        "Erro na base de dados. Confirme o MySQL do MAMP, a configuracao PHP e a tabela users.\n"
    );

    exit(1);

} catch (InvalidArgumentException | RuntimeException $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);

    exit(1);

} catch (Throwable $error) {
    fwrite(
        STDERR,
        "Nao foi possivel concluir. Confirme os ficheiros PHP antes de tentar novamente.\n"
    );

    exit(1);
}
