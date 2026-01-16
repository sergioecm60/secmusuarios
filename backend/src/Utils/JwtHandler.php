<?php

namespace App\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler
{
    private $secret;
    private $algorithm;
    private $expiresIn;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'default_secret_key';
        $this->algorithm = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
        $this->expiresIn = (int)($_ENV['JWT_EXPIRES_IN'] ?? 3600);
    }

    public function encode(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->expiresIn;

        $token = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $payload
        ];

        return JWT::encode($token, $this->secret, $this->algorithm);
    }

    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return (array)$decoded->data;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function generar(string $secret, array $payload, int $expiresIn = 3600): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $expiresIn;
        $token = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire
        ]);
        return JWT::encode($token, $secret, 'HS256');
    }

    public static function validar(string $secret, string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($secret, 'HS256'));
        } catch (\Throwable $e) {
            return null;
        }
    }
}