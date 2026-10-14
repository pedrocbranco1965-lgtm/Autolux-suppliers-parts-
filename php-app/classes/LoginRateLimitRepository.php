<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseSource.php';

//private const WINDOW_SECONDS = 900;
//private const EMAIL_LIMIT = 5;
//private const IP_LIMIT = 30;


final class LoginRateLimitRepository extends DatabaseSource
{
    private const WINDOW_SECONDS = 900;
    private const EMAIL_LIMIT = 5;
    private const IP_LIMIT = 30;

    // Consulta interna dos contadores, nao uma pagina publica.
    public function getAll(): array
    {
        return $this->query(
            'SELECT scope, identifier_hash, attempts, window_ends_at
             FROM login_rate_limits
             ORDER BY window_ends_at DESC
             LIMIT 100'
        )->fetchAll();
    }

    //→ A tentativa foi contabilizada.
    // Podemos pedir ao AuthService que verifique as credenciais.
    //Um número positivo, por exemplo 120
    // O limite foi atingido.
    // Ainda faltam aproximadamente 120 segundos para tentar novamente.
    // 0 = tentativa reservada; valor positivo = segundos a aguardar.
    // Chamar uma vez por submissao, antes de verificar a palavra-passe.

    public function consumeAttempt(string $email, string $ip): int
    {
        $email = strtolower(trim($email));

        if (strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Email invalido.');
        }

        $packedIp = @inet_pton($ip);

        if ($packedIp === false) {
            throw new InvalidArgumentException('Endereco IP invalido.');
        }

        if ($this->pdo->inTransaction()) {
            throw new RuntimeException('O controlo de tentativas exige uma transacao propria.');
        }

        $this->pdo->beginTransaction();

        try {
            // Usar sempre esta ordem para adquirir os bloqueios.
            $ipWait = $this->consumeCounter(
                'ip', hash('sha256', $packedIp), self::IP_LIMIT
            );

            $emailWait = $this->consumeCounter(
                'email', hash('sha256', $email), self::EMAIL_LIMIT
            );

            $retryAfter = max($ipWait, $emailWait);

            if ($retryAfter > 0) {
                // Nao alterar contadores nem prolongar a espera quando recusamos.
                $this->pdo->rollBack();
                return $retryAfter;
            }

            $this->pdo->commit();
            return 0;

        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            // Uma falha na base nao equivale a permitir uma tentativa.
            throw $error;
        }
    }

    private function consumeCounter(string $scope, string $hash, int $limit): int
    {
        $identity = ['scope' => $scope, 'identifier_hash' => $hash];

        // Criar a linha se faltar; se existir, nao apagar o contador.
        $this->query(
            'INSERT INTO login_rate_limits
                (scope, identifier_hash, attempts, window_ends_at)
             VALUES (:scope, :identifier_hash, 0, :window_end)
             ON DUPLICATE KEY UPDATE attempts = attempts',
            $identity + ['window_end' => gmdate('Y-m-d H:i:s')]
        );

        // Bloquear o registo enquanto lemos e atualizamos o contador.
        $row = $this->query(
            'SELECT attempts, window_ends_at
             FROM login_rate_limits
             WHERE scope = :scope AND identifier_hash = :identifier_hash
             FOR UPDATE',
            $identity
        )->fetch();

        if ($row === false) {
            throw new RuntimeException('Nao foi possivel consultar o contador.');
        }

        $end = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s', $row['window_ends_at'], new DateTimeZone('UTC')
        );

        if ($end === false || $end->format('Y-m-d H:i:s') !== $row['window_ends_at']) {
            throw new RuntimeException('Data do contador invalida.');
        }

        $now = time();
        $endsAt = $end->getTimestamp();
        $attempts = (int) $row['attempts'];

        if ($endsAt <= $now) {
            $attempts = 0;
            $endsAt = $now + self::WINDOW_SECONDS;
        }

        if ($attempts >= $limit) {
            return max(1, $endsAt - $now);
        }

        $this->query(
            'UPDATE login_rate_limits
             SET attempts = :attempts, window_ends_at = :window_end
             WHERE scope = :scope AND identifier_hash = :identifier_hash',
            $identity + [
                'attempts' => $attempts + 1,
                'window_end' => gmdate('Y-m-d H:i:s', $endsAt),
            ]
        );

        return 0;
    }
}

