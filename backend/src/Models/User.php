<?php

namespace App\Models;

class User
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users_master WHERE username = ? AND activo = 1 AND (bloqueado_hasta IS NULL OR bloqueado_hasta < NOW())");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, nombre, apellido, nombre_completo, rol, activo, empresa_id, sucursal_id, departamento_id, telefono, created_at FROM users_master WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function create(array $data): int
    {
        $nombreCompleto = trim(($data['nombre'] ?? '') . ' ' . ($data['apellido'] ?? ''));
        $stmt = $this->pdo->prepare(
            "INSERT INTO users_master (username, email, password_hash, nombre, apellido, nombre_completo, rol, sistema_origen, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([
            $data['username'],
            $data['email'],
            password_hash($data['password'], PASSWORD_ARGON2ID),
            $data['nombre'] ?? '',
            $data['apellido'] ?? '',
            $nombreCompleto,
            $data['rol'] ?? 'user',
            $data['sistema_origen'] ?? 'secmusuarios'
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if ($key === 'password') {
                $fields[] = "password_hash = ?";
                $values[] = password_hash($value, PASSWORD_ARGON2ID);
            } elseif ($key === 'nombre' || $key === 'apellido') {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (!empty($fields)) {
            $values[] = $id;
            $sql = "UPDATE users_master SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        }

        return false;
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE users_master SET ultimo_login = CURRENT_TIMESTAMP, primer_login = 0 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, nombre, apellido, nombre_completo, rol, activo, ultimo_login, created_at FROM users_master ORDER BY id LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM users_master");
        return (int)$stmt->fetch()['total'];
    }

    /**
     * Verifica si la cuenta está bloqueada
     * @return array|null null si usuario no existe, array con estado de bloqueo si existe
     */
    public function isAccountLocked(string $username): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, intentos_fallidos, bloqueado_hasta
            FROM users_master
            WHERE username = ? AND activo = 1
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
            return [
                'locked' => true,
                'until' => $user['bloqueado_hasta'],
                'remaining_minutes' => ceil((strtotime($user['bloqueado_hasta']) - time()) / 60)
            ];
        }

        return ['locked' => false, 'attempts' => (int)$user['intentos_fallidos']];
    }

    /**
     * Incrementa contador de intentos fallidos y bloquea si excede el máximo
     */
    public function incrementFailedAttempts(string $username): array
    {
        $maxAttempts = (int)($_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5);
        $lockoutMinutes = (int)($_ENV['LOCKOUT_DURATION_MINUTES'] ?? 30);

        // Obtener usuario
        $stmt = $this->pdo->prepare("SELECT id, intentos_fallidos FROM users_master WHERE username = ? AND activo = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['locked' => false, 'attempts' => 0, 'remaining' => $maxAttempts];
        }

        $newAttempts = (int)$user['intentos_fallidos'] + 1;

        // Incrementar intentos
        $stmt = $this->pdo->prepare("UPDATE users_master SET intentos_fallidos = ? WHERE id = ?");
        $stmt->execute([$newAttempts, $user['id']]);

        // Verificar si debe bloquear
        if ($newAttempts >= $maxAttempts) {
            $lockUntil = date('Y-m-d H:i:s', strtotime("+{$lockoutMinutes} minutes"));
            $stmt = $this->pdo->prepare("UPDATE users_master SET bloqueado_hasta = ? WHERE id = ?");
            $stmt->execute([$lockUntil, $user['id']]);

            return [
                'locked' => true,
                'user_id' => (int)$user['id'],
                'until' => $lockUntil,
                'attempts' => $newAttempts
            ];
        }

        return [
            'locked' => false,
            'attempts' => $newAttempts,
            'remaining' => $maxAttempts - $newAttempts
        ];
    }

    /**
     * Resetea contador de intentos fallidos (login exitoso)
     */
    public function resetFailedAttempts(int $userId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users_master
            SET intentos_fallidos = 0, bloqueado_hasta = NULL
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Desbloquea cuenta manualmente (admin)
     */
    public function unlockAccount(int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE users_master
            SET intentos_fallidos = 0, bloqueado_hasta = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }

    /**
     * Obtiene datos básicos de un usuario por ID (para auditoría)
     */
    public function getBasicInfo(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, rol FROM users_master WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}