<?php

namespace App\Services;

class AuditService
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra una acción en la bitácora de auditoría
     */
    public function log(
        string $action,
        ?int $userId = null,
        ?string $username = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $additionalData = null
    ): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_log
                (user_id, username, action, target_type, target_id, old_values, new_values, ip_address, user_agent, additional_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $userId,
                $username,
                $action,
                $targetType,
                $targetId,
                $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                $this->getClientIp(),
                $this->getUserAgent(),
                $additionalData ? json_encode($additionalData, JSON_UNESCAPED_UNICODE) : null
            ]);
        } catch (\Exception $e) {
            error_log("AuditService error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra login exitoso
     */
    public function logLoginSuccess(int $userId, string $username): bool
    {
        return $this->log('login_success', $userId, $username, 'session', null);
    }

    /**
     * Registra intento de login fallido
     */
    public function logLoginFailed(string $username, string $reason = 'invalid_credentials'): bool
    {
        return $this->log('login_failed', null, $username, null, null, null, null, ['reason' => $reason]);
    }

    /**
     * Registra cierre de sesión
     */
    public function logLogout(int $userId, string $username): bool
    {
        return $this->log('logout', $userId, $username, 'session', null);
    }

    /**
     * Registra creación de usuario
     */
    public function logUserCreate(int $actorId, string $actorUsername, int $newUserId, array $userData, string $sistema = 'secmusuarios'): bool
    {
        // Nunca guardar password en audit
        unset($userData['password'], $userData['password_hash']);
        $userData['sistema'] = $sistema;
        return $this->log('user_create', $actorId, $actorUsername, 'user', $newUserId, null, $userData);
    }

    /**
     * Registra actualización de usuario
     */
    public function logUserUpdate(int $actorId, string $actorUsername, int $targetUserId, array $oldData, array $newData, string $sistema = 'secmusuarios'): bool
    {
        // Nunca guardar password en audit
        unset($oldData['password'], $oldData['password_hash']);
        unset($newData['password'], $newData['password_hash']);
        $newData['sistema'] = $sistema;
        return $this->log('user_update', $actorId, $actorUsername, 'user', $targetUserId, $oldData, $newData);
    }

    /**
     * Registra eliminación de usuario
     */
    public function logUserDelete(int $actorId, string $actorUsername, int $targetUserId, array $deletedData, string $sistema = 'secmusuarios'): bool
    {
        // Nunca guardar password en audit
        unset($deletedData['password'], $deletedData['password_hash']);
        $deletedData['sistema'] = $sistema;
        return $this->log('user_delete', $actorId, $actorUsername, 'user', $targetUserId, $deletedData, null);
    }

    /**
     * Registra bloqueo de cuenta
     */
    public function logAccountLocked(string $username, int $userId, int $attemptCount = 0): bool
    {
        return $this->log('account_locked', null, $username, 'user', $userId, null, null, [
            'reason' => 'max_attempts_exceeded',
            'attempts' => $attemptCount
        ]);
    }

    /**
     * Registra desbloqueo de cuenta
     */
    public function logAccountUnlocked(int $actorId, string $actorUsername, int $targetUserId, string $targetUsername): bool
    {
        return $this->log('account_unlocked', $actorId, $actorUsername, 'user', $targetUserId, null, null, [
            'unlocked_user' => $targetUsername
        ]);
    }

    /**
     * Registra renovación de token
     */
    public function logTokenRefresh(int $userId, string $username): bool
    {
        return $this->log('token_refresh', $userId, $username, 'token', null);
    }

    /**
     * Registra logout de todos los dispositivos
     */
    public function logLogoutAll(int $userId, string $username): bool
    {
        return $this->log('logout_all_devices', $userId, $username, 'session', null);
    }

    /**
     * Consulta registros de auditoría con filtros y paginación
     */
    public function getAuditLogs(int $limit = 100, int $offset = 0, ?array $filters = null): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['username'])) {
            $where[] = "username LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
        }
        if (!empty($filters['action'])) {
            $where[] = "action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['target_type'])) {
            $where[] = "target_type = ?";
            $params[] = $filters['target_type'];
        }
        if (!empty($filters['from_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['to_date'] . ' 23:59:59';
        }
        if (!empty($filters['ip_address'])) {
            $where[] = "ip_address = ?";
            $params[] = $filters['ip_address'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM audit_log $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Cuenta total de registros con filtros
     */
    public function countAuditLogs(?array $filters = null): int
    {
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
        }
        if (!empty($filters['username'])) {
            $where[] = "username LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
        }
        if (!empty($filters['action'])) {
            $where[] = "action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['target_type'])) {
            $where[] = "target_type = ?";
            $params[] = $filters['target_type'];
        }
        if (!empty($filters['from_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['to_date'] . ' 23:59:59';
        }
        if (!empty($filters['ip_address'])) {
            $where[] = "ip_address = ?";
            $params[] = $filters['ip_address'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM audit_log $whereClause");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene las acciones disponibles para filtrado
     */
    public function getAvailableActions(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT action FROM audit_log ORDER BY action");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
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
