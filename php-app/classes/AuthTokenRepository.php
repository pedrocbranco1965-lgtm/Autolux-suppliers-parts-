<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseSource.php';
require_once __DIR__ . '/Input.php';

final class AuthTokenRepository extends DatabaseSource
{
    // Apenas metadados. Nao devolve o JWT, chaves ou hashes de palavras-passe.
    public function getAll(): array
    {
        return $this->query(
            'SELECT id, user_id, issued_at, expires_at, revoked_at
             FROM auth_tokens
             ORDER BY id DESC'
        )->fetchAll();
    }

    // Chamar com os dados devolvidos por JwtService::issue(), apos o login.
    // Nao enviar o token ao navegador antes de este registo ter sucesso.
    public function register(
        int $userId,
        string $jti,
        int $issuedAt,
        int $expiresAt
    ): int {
        $this->validateIdentity($userId, $jti);

        $now = time();

        if (
            $issuedAt < 1
            || $issuedAt > $now
            || $expiresAt <= $now
            || $expiresAt <= $issuedAt
            || $expiresAt - $issuedAt > 3600
        ) {
            throw new InvalidArgumentException('Datas do registo JWT invalidas.');
        }

        // INSERT ... SELECT so insere se a conta existir e estiver ativa.
        $statement = $this->query(
            'INSERT INTO auth_tokens (user_id, jti, issued_at, expires_at)
             SELECT id, :jti, :issued_at, :expires_at
             FROM users
             WHERE id = :user_id AND active = 1',
            [
                'user_id' => $userId,
                'jti' => $jti,
                'issued_at' => gmdate('Y-m-d H:i:s', $issuedAt),
                'expires_at' => gmdate('Y-m-d H:i:s', $expiresAt),
            ]
        );

        if ($statement->rowCount() !== 1) {
            throw new RuntimeException('Nao foi possivel registar o token para uma conta ativa.');
        }

        return (int) $this->pdo->lastInsertId();
    }

    // Usar apenas DEPOIS de JwtService::verify().
    // Este metodo consulta o estado na BD; nao verifica a assinatura.
    public function isActive(int $userId, string $jti): bool
    {
        $this->validateIdentity($userId, $jti);

        $now = gmdate('Y-m-d H:i:s');

        $statement = $this->query(
            'SELECT t.id
             FROM auth_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.user_id = :user_id
               AND t.jti = :jti
               AND t.revoked_at IS NULL
               AND t.issued_at <= :now_start
               AND t.expires_at > :now_end
               AND u.active = 1
             LIMIT 1',
            [
                'user_id' => $userId,
                'jti' => $jti,
                'now_start' => $now,
                'now_end' => $now,
            ]
        );

        return $statement->fetchColumn() !== false;
    }

    // Revoga apenas o token indicado; nao apaga a conta ou os outros tokens.
    public function revoke(int $userId, string $jti): void
    {
        $this->validateIdentity($userId, $jti);

        $this->query(
            'UPDATE auth_tokens
             SET revoked_at = :revoked_at
             WHERE user_id = :user_id
               AND jti = :jti
               AND revoked_at IS NULL',
            [
                'user_id' => $userId,
                'jti' => $jti,
                'revoked_at' => gmdate('Y-m-d H:i:s'),
            ]
        );
    }

    private function validateIdentity(int $userId, string $jti): void
    {
        Input::positiveInt($userId, 'Utilizador');

        if (preg_match('/\A[a-f0-9]{64}\z/', $jti) !== 1) {
            throw new InvalidArgumentException('Identificador do token invalido.');
        }
    }
}
