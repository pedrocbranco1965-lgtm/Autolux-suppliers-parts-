<?php

declare(strict_types=1);

require_once __DIR__ . '/DatabaseSource.php';
require_once __DIR__ . '/Input.php';

class UserRepository extends DatabaseSource
{
    // Listar contas sem devolver hashes de palavras-passe.
    public function getAll(): array
    {
        return $this->query(
            'SELECT id, name, email, active, created_at
             FROM users
             ORDER BY name, id'
        )->fetchAll();
    }

    // Procurar uma conta pelo ID, sem devolver o hash.
    public function findById(int $id): ?array
    {
        $id = Input::positiveInt($id, 'Utilizador');

        $user = $this->query(
            'SELECT id, name, email, active, created_at
             FROM users
             WHERE id = :id',
            ['id' => $id]
        )->fetch();

        return $user === false ? null : $user;
    }

    // Uso interno no login. Nunca enviar este resultado para HTML ou JSON.
    public function findForAuthenticationByEmail(string $email): ?array
    {
        $email = $this->normalizeEmail($email);

        $user = $this->query(
            'SELECT id, name, email, password_hash, active
             FROM users
             WHERE email = :email',
            ['email' => $email]
        )->fetch();

        return $user === false ? null : $user;
    }

    // Recebe um hash ja gerado pelo PHP, nunca a palavra-passe original.
    public function createWithPasswordHash(
        string $name,
        string $email,
        string $passwordHash
    ): int {
        $name = Input::text($name, 'Nome', 120);
        $email = $this->normalizeEmail($email);

        $hashInfo = password_get_info($passwordHash);

        if (
            strlen($passwordHash) > 255
            || $hashInfo['algoName'] === 'unknown'
        ) {
            throw new InvalidArgumentException(
                'E necessario fornecer um hash gerado por password_hash().'
            );
        }

        try {
            $this->query(
                'INSERT INTO users (name, email, password_hash, active)
                 VALUES (:name, :email, :password_hash, 1)',
                [
                    'name' => $name,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                ]
            );
        } catch (PDOException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                throw new InvalidArgumentException(
                    'Ja existe uma conta com este email.',
                    0,
                    $error
                );
            }

            throw $error;
        }

        return (int) $this->pdo->lastInsertId();
    }

    // Convencao desta aplicacao: emails sem espacos nas pontas e em minusculas.
    private function normalizeEmail(string $email): string
    {
        $email = strtolower(Input::text($email, 'Email', 150));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Introduza um email valido.');
        }

        return $email;
    }
}