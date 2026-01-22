<?php

namespace App\Services;

class RefreshTokenService
{
    private $pdo;
    private $expiresDays;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->expiresDays = (int)($_ENV['REFRESH_TOKEN_EXPIRES_DAYS'] ?? 7);
    }

    /**
     * Genera un nuevo refresh token para un usuario
     */
    public function generateToken(int $userId): string
    {
        // Generar token criptograficamente seguro (32 bytes = 64 caracteres hex)
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->expiresDays} days"));

        $stmt = $this->pdo->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at, user_agent, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $tokenHash,
            $expiresAt,
            $this->getUserAgent(),
            $this->getClientIp()
        ]);

        return $token;
    }

    /**
     * Valida un refresh token y retorna datos del usuario si es valido
     */
    public function validateToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare("
            SELECT rt.*, u.id as user_id, u.username, u.rol, u.activo, u.bloqueado_hasta
            FROM refresh_tokens rt
            JOIN users_master u ON rt.user_id = u.id
            WHERE rt.token_hash = ?
              AND rt.expires_at > NOW()
              AND rt.revoked_at IS NULL
              AND u.activo = 1
              AND (u.bloqueado_hasta IS NULL OR u.bloqueado_hasta < NOW())
        ");
        $stmt->execute([$tokenHash]);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Revoca un refresh token (logout)
     */
    public function revokeToken(string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->pdo->prepare("
            UPDATE refresh_tokens
            SET revoked_at = NOW()
            WHERE token_hash = ? AND revoked_at IS NULL
        ");
        return $stmt->execute([$tokenHash]);
    }

    /**
     * Revoca todos los tokens de un usuario (logout de todos los dispositivos)
     */
    public function revokeAllUserTokens(int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE refresh_tokens
            SET revoked_at = NOW()
            WHERE user_id = ? AND revoked_at IS NULL
        ");
        return $stmt->execute([$userId]);
    }

    /**
     * Rota un token (revoca el anterior y genera uno nuevo)
     */
    public function rotateToken(string $oldToken): ?string
    {
        $tokenData = $this->validateToken($oldToken);
        if (!$tokenData) {
            return null;
        }

        // Revocar el token anterior
        $this->revokeToken($oldToken);

        // Generar nuevo token
        return $this->generateToken($tokenData['user_id']);
    }

    /**
     * Limpia tokens expirados o revocados (para cron job)
     */
    public function cleanupExpiredTokens(): int
    {
        $stmt = $this->pdo->query("
            DELETE FROM refresh_tokens
            WHERE expires_at < NOW() OR revoked_at IS NOT NULL
        ");
        return $stmt->rowCount();
    }

    /**
     * Obtiene todos los tokens activos de un usuario (para mostrar sesiones)
     */
    public function getActiveTokens(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, created_at, expires_at, user_agent, ip_address
            FROM refresh_tokens
            WHERE user_id = ?
              AND expires_at > NOW()
              AND revoked_at IS NULL
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Revoca un token especifico por ID (para cerrar sesion en un dispositivo)
     */
    public function revokeTokenById(int $tokenId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE refresh_tokens
            SET revoked_at = NOW()
            WHERE id = ? AND user_id = ? AND revoked_at IS NULL
        ");
        return $stmt->execute([$tokenId, $userId]);
    }

    /**
     * Obtiene la IP del cliente
     */
    private function getClientIp(): string
    {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                return trim($ip);
            }
        }
        return '0.0.0.0';
    }

    /**
     * Obtiene el User Agent del navegador
     */
    private function getUserAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    }
}
