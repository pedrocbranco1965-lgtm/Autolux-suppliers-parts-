<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once __DIR__ . '/../vendor/autoload.php';

final class JwtService
{
    private const ALGORITHM = 'RS256';

    private OpenSSLAsymmetricKey $privateKey;
    private OpenSSLAsymmetricKey $publicKey;
    private string $issuer;
    private string $audience;
    private int $ttlSeconds;

    public function __construct(array $jwtConfig)
    {
        if (($jwtConfig['algorithm'] ?? null) !== self::ALGORITHM) {
            throw new RuntimeException('A configuracao JWT deve usar RS256.');
        }

        foreach (['issuer', 'audience'] as $field) {
            $value = $jwtConfig[$field] ?? null;

            if (!is_string($value) || trim($value) === '') {
                throw new RuntimeException('Configuracao JWT incompleta: ' . $field);
            }
        }

        $ttl = $jwtConfig['ttl_seconds'] ?? null;

        // Limite escolhido para esta aplicacao, em segundos.
        if (!is_int($ttl) || $ttl < 60 || $ttl > 3600) {
            throw new RuntimeException('ttl_seconds deve ser um inteiro entre 60 e 3600.');
        }

        $privatePem = $this->readKeyFile($jwtConfig['private_key_path'] ?? null);
        $publicPem = $this->readKeyFile($jwtConfig['public_key_path'] ?? null);

        $privateKey = @openssl_pkey_get_private($privatePem);
        $publicKey = @openssl_pkey_get_public($publicPem);

        if ($privateKey === false || $publicKey === false) {
            throw new RuntimeException('Nao foi possivel interpretar as chaves JWT.');
        }

        $privateDetails = openssl_pkey_get_details($privateKey);
        $publicDetails = openssl_pkey_get_details($publicKey);

        if (
            $privateDetails === false
            || $publicDetails === false
            || $privateDetails['type'] !== OPENSSL_KEYTYPE_RSA
            || $publicDetails['type'] !== OPENSSL_KEYTYPE_RSA
            || $privateDetails['bits'] < 2048
            || $publicDetails['bits'] < 2048
            || $privateDetails['key'] !== $publicDetails['key']
        ) {
            throw new RuntimeException('As chaves devem formar um par RSA de pelo menos 2048 bits.');
        }

        $this->privateKey = $privateKey;
        $this->publicKey = $publicKey;
        $this->issuer = $jwtConfig['issuer'];
        $this->audience = $jwtConfig['audience'];
        $this->ttlSeconds = $ttl;
    }

    // Chamar apenas DEPOIS de validar a palavra-passe e a conta ativa.
    // Este metodo nao consulta utilizadores nem grava em auth_tokens.
    public function issue(int $userId): array
    {
        if ($userId < 1 || $userId > 2147483647) {
            throw new InvalidArgumentException('Identificador de utilizador invalido.');
        }

        $now = time();

        $claims = [
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'sub' => (string) $userId,
            'jti' => bin2hex(random_bytes(32)),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttlSeconds,
        ];

        $token = JWT::encode($claims, $this->privateKey, self::ALGORITHM);

        return [
            'token' => $token,
            'claims' => $claims,
        ];
    }

    // Verifica assinatura e campos. Nao verifica revogacao nem conta ativa.
    public function verify(string $token): array
    {
        if ($token === '' || strlen($token) > 4096) {
            throw new UnexpectedValueException('Token ausente ou demasiado longo.');
        }

        $headers = new stdClass();

        // O algoritmo e fixado pelo nosso codigo, nao pelo token recebido.
        // A biblioteca rejeita assinaturas invalidas e tokens expirados.
        $claims = (array) JWT::decode(
            $token,
            new Key($this->publicKey, self::ALGORITHM),
            $headers
        );

        // Neste projeto usamos o tipo JWT e nao suportamos cabecalhos criticos.
        if (($headers->typ ?? null) !== 'JWT' || isset($headers->crit)) {
            throw new UnexpectedValueException('Cabecalho JWT nao permitido.');
        }

        if (
            ($claims['iss'] ?? null) !== $this->issuer
            || ($claims['aud'] ?? null) !== $this->audience
        ) {
            throw new UnexpectedValueException('Emissor ou destinatario JWT invalido.');
        }

        $subject = $claims['sub'] ?? null;
        $jti = $claims['jti'] ?? null;

        if (
            !is_string($subject)
            || preg_match('/\A[1-9][0-9]{0,9}\z/', $subject) !== 1
            || (int) $subject > 2147483647
            || !is_string($jti)
            || preg_match('/\A[a-f0-9]{64}\z/', $jti) !== 1
        ) {
            throw new UnexpectedValueException('Identificadores JWT invalidos.');
        }

        // Tornamos estes campos obrigatorios no nosso formato de token.
        foreach (['iat', 'nbf', 'exp'] as $field) {
            if (!is_int($claims[$field] ?? null)) {
                throw new UnexpectedValueException('Datas JWT ausentes ou invalidas.');
            }
        }

        $now = time();

        if (
            $claims['iat'] < 0
            || $claims['iat'] > $now
            || $claims['nbf'] !== $claims['iat']
            || $claims['exp'] <= $now
            || $claims['exp'] <= $claims['iat']
            || $claims['exp'] - $claims['iat'] > $this->ttlSeconds
        ) {
            throw new UnexpectedValueException('Token expirado ou com datas incoerentes.');
        }

        return $claims;
    }

    private function readKeyFile(mixed $path): string
    {
        if (!is_string($path) || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Verifique os caminhos e as permissoes das chaves JWT.');
        }

        $contents = @file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new RuntimeException('Nao foi possivel ler uma das chaves JWT.');
        }

        return $contents;
    }
}
