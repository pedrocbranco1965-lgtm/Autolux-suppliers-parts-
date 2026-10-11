<?php

declare(strict_types=1);

require_once __DIR__ . '/../classes/UserRepository.php';
require_once __DIR__ . '/../classes/AuthTokenRepository.php';
require_once __DIR__ . '/JwtService.php';

final class AuthService
{
    // Hashes ficticios para verificar uma password mesmo sem encontrar conta.
    // Nao pertencem a nenhum utilizador e nao permitem entrar na aplicacao.
    private const DUMMY_ARGON2ID = '$argon2id$v=19$m=19456,t=2,p=1$OFQvUEwxQ2lvRDhpSkhCWA$zdsB8eTf76aCYnAuFPNNBs9Ef9NCxPg9HH7tqSoRMgM';
    private const DUMMY_BCRYPT = '$2y$12$8.PfORx6eI7FLgxttSDQMuFd8ZeTyVgA2z2mqZy8O1qTv8PyV/6Ey';

    private UserRepository $users;
    private AuthTokenRepository $tokens;
    private JwtService $jwt;

    public function __construct(
        UserRepository $users,
        AuthTokenRepository $tokens,
        JwtService $jwt
    ) {
        $this->users = $users;
        $this->tokens = $tokens;
        $this->jwt = $jwt;
    }

    // Devolve o token apenas depois de o registar com sucesso na base.
    public function login(mixed $email, #[SensitiveParameter] mixed $password): array
    {
        $failure = 'Email ou palavra-passe incorretos.';

        if (!is_string($email) || !is_string($password)) {
            throw new InvalidArgumentException($failure);
        }

        $email = strtolower(trim($email));

        if (
            strlen($email) > 150
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || $password === ''
            || strlen($password) > 512
            || str_contains($password, "\0")
        ) {
            throw new InvalidArgumentException($failure);
        }

        $length = preg_match_all('/./us', $password);

        if ($length === false || $length > 128) {
            throw new InvalidArgumentException($failure);
        }

        // Nao aplicar trim() na palavra-passe.
        $user = $this->users->findForAuthenticationByEmail($email);
        $hash = $user['password_hash'] ?? $this->dummyHash();
        $passwordIsValid = password_verify($password, $hash);

        // Nao aceitar silenciosamente passwords truncadas pelo bcrypt.
        if (
            password_get_info($hash)['algoName'] === 'bcrypt'
            && strlen($password) > 72
        ) {
            $passwordIsValid = false;
        }

        if ($user === null || !$passwordIsValid || (int) $user['active'] !== 1) {
            throw new InvalidArgumentException($failure);
        }

        $userId = (int) $user['id'];
        $issued = $this->jwt->issue($userId);
        $claims = $issued['claims'];

        $this->tokens->register(
            $userId,
            $claims['jti'],
            $claims['iat'],
            $claims['exp']
        );

        // Resultado interno: nao imprimir, registar em logs ou enviar como JSON.
        // A futura pagina colocara o token num cookie com as opcoes adequadas.
        return [
            'user' => $this->safeUser($user),
            'token' => $issued['token'],
            'expires_at' => $claims['exp'],
        ];
    }

    // Verifica o JWT e tambem o estado atual do acesso na base de dados.
    public function authenticate(#[SensitiveParameter] string $token): array
    {
        $claims = $this->jwt->verify($token);
        $userId = (int) $claims['sub'];

        if (!$this->tokens->isActive($userId, $claims['jti'])) {
            throw new UnexpectedValueException('Sessao invalida ou expirada.');
        }

        $user = $this->users->findById($userId);

        if ($user === null || (int) $user['active'] !== 1) {
            throw new UnexpectedValueException('Sessao invalida ou expirada.');
        }

        return $this->safeUser($user);
    }

    // Revoga este token. A futura pagina de logout tratara tambem do cookie.
    public function logout(#[SensitiveParameter] string $token): void
    {
        try {
            $claims = $this->jwt->verify($token);
        } catch (UnexpectedValueException $error) {
            // Um JWT invalido ou expirado ja nao concede acesso.
            return;
        }

        // Falhas na BD nao sao ignoradas: nao afirmar sucesso se a revogacao falhar.
        $this->tokens->revoke((int) $claims['sub'], $claims['jti']);
    }

    private function safeUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];
    }

    private function dummyHash(): string
    {
        return in_array('argon2id', password_algos(), true)
            ? self::DUMMY_ARGON2ID
            : self::DUMMY_BCRYPT;
    }
}
