<?php

namespace App\Controllers;

use App\Services\AuditService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UsersController
{
    private $pdo;
    private $pdoSecmalquileres;
    private $pdoSecmti;
    private $pdoSecmautos;
    private $pdoSecmrrhh;
    private $pdoPsitios;
    private $pdoSecmagencias;
    private $auditService;

    public function __construct($pdo, AuditService $auditService)
    {
        global $pdoSecmalquileres, $pdoSecmti, $pdoSecmautos, $pdoSecmrrhh, $pdoPsitios, $pdoSecmagencias;

        $this->pdo = $pdo;
        $this->pdoSecmalquileres = $pdoSecmalquileres;
        $this->pdoSecmti = $pdoSecmti;
        $this->pdoSecmautos = $pdoSecmautos;
        $this->pdoSecmrrhh = $pdoSecmrrhh;
        $this->pdoPsitios = $pdoPsitios;
        $this->pdoSecmagencias = $pdoSecmagencias;
        $this->auditService = $auditService;
    }

    public function getAllSystemsUsers(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $userRol = $request->getAttribute('user_rol');

        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'No autorizado']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $data = [
            'secmusuarios' => $this->getFromMaster(),
            'secmalquileres' => $this->getFromSecmalquileres(),
            'secmti' => $this->getFromSecmti(),
            'secmautos' => $this->getFromSecmautos(),
            'secmrrhh' => $this->getFromSecmrrhh(),
            'Psitios' => $this->getFromPsitios(),
            'secmagencias' => $this->getFromSecmagencias()
        ];

        $totals = [
            'secmusuarios' => count($data['secmusuarios']),
            'secmalquileres' => count($data['secmalquileres']),
            'secmti' => count($data['secmti']),
            'secmautos' => count($data['secmautos']),
            'secmrrhh' => count($data['secmrrhh']),
            'Psitios' => count($data['Psitios']),
            'secmagencias' => count($data['secmagencias']),
            'total' => array_sum([
                count($data['secmusuarios']),
                count($data['secmalquileres']),
                count($data['secmti']),
                count($data['secmautos']),
                count($data['secmrrhh']),
                count($data['Psitios']),
                count($data['secmagencias'])
            ])
        ];

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $data,
            'totals' => $totals
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getFromMaster(): array
    {
        if (!$this->pdo) return [];
        try {
            $stmt = $this->pdo->query("SELECT 'secmusuarios' as sistema, id, username, email, nombre_completo, rol, activo, created_at FROM users_master ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmalquileres(): array
    {
        if (!$this->pdoSecmalquileres) return [];
        try {
            $stmt = $this->pdoSecmalquileres->query("SELECT 'secmalquileres' as sistema, id, username, email, nombre_completo, rol, activo, fecha_registro as created_at FROM users ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmti(): array
    {
        if (!$this->pdoSecmti) return [];
        try {
            $stmt = $this->pdoSecmti->query("SELECT 'secmti' as sistema, id, username, email, full_name as nombre_completo, role as rol, 1 as activo, created_at FROM users ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmautos(): array
    {
        if (!$this->pdoSecmautos) return [];
        try {
            $stmt = $this->pdoSecmautos->query("SELECT 'secmautos' as sistema, id, username, email, CONCAT(nombre, ' ', apellido) as nombre_completo, rol, activo, created_at FROM usuarios ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmrrhh(): array
    {
        if (!$this->pdoSecmrrhh) return [];
        try {
            $stmt = $this->pdoSecmrrhh->query("SELECT 'secmrrhh' as sistema, id_usuario as id, username, email, nombre_completo, rol, CASE WHEN estado='activo' THEN 1 ELSE 0 END as activo, NOW() as created_at FROM usuarios ORDER BY id_usuario");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromPsitios(): array
    {
        if (!$this->pdoPsitios) return [];
        try {
            $stmt = $this->pdoPsitios->query("SELECT 'Psitios' as sistema, id, username, '' as email, username as nombre_completo, role as rol, is_active as activo, created_at FROM users ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmagencias(): array
    {
        if (!$this->pdoSecmagencias) return [];
        try {
            $stmt = $this->pdoSecmagencias->query("SELECT 'secmagencias' as sistema, u.id, u.username, u.email, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo, r.nombre as rol, u.activo, NOW() as created_at FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id ORDER BY u.id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function createUser(Request $request, Response $response): Response
    {
        $userRol = $request->getAttribute('user_rol');

        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'No autorizado']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $sistema = $data['sistema'] ?? 'secmusuarios';
        $sistemas = $data['sistemas'] ?? null; // Array de sistemas seleccionados
        $multitenant = $data['multitenant'] ?? false; // Mantener compatibilidad

        if (empty($data['username']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Username y password son requeridos']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $actorId = $request->getAttribute('user_id');
            $actorUsername = $this->getActorUsername($actorId);

            // Si se envio un array de sistemas especificos
            if (!empty($sistemas) && is_array($sistemas)) {
                $results = [];
                $successCount = 0;
                $totalCount = count($sistemas);

                foreach ($sistemas as $sys) {
                    $result = $this->createInSystem($sys, $data);
                    $results[$sys] = $result;
                    if ($result['success']) {
                        $successCount++;
                        // Auditar creacion exitosa
                        $this->auditService->logUserCreate($actorId, $actorUsername, $result['id'] ?? 0, $data, $sys);
                    }
                }

                $allSuccess = ($successCount === $totalCount);
                $message = $allSuccess
                    ? "Usuario creado en {$successCount} sistema(s)"
                    : "Usuario creado en {$successCount} de {$totalCount} sistemas";

                $response->getBody()->write(json_encode([
                    'success' => $successCount > 0,
                    'message' => $message,
                    'results' => $results
                ]));

                return $response->withHeader('Content-Type', 'application/json');

            } elseif ($multitenant) {
                // Mantener compatibilidad con el checkbox antiguo (todos los sistemas)
                $systems = ['secmusuarios', 'secmalquileres', 'secmti', 'secmautos', 'secmrrhh', 'Psitios', 'secmagencias'];
                $results = [];
                $all_success = true;

                foreach ($systems as $sys) {
                    $result = $this->createInSystem($sys, $data);
                    $results[$sys] = $result;
                    if ($result['success']) {
                        // Auditar creacion exitosa
                        $this->auditService->logUserCreate($actorId, $actorUsername, $result['id'] ?? 0, $data, $sys);
                    } else {
                        $all_success = false;
                    }
                }

                $response->getBody()->write(json_encode([
                    'success' => $all_success,
                    'message' => $all_success ? 'Usuario creado en todos los sistemas' : 'Error en algunos sistemas',
                    'results' => $results
                ]));

                return $response->withHeader('Content-Type', 'application/json');

            } else {
                // Crear en un solo sistema
                $result = $this->createInSystem($sistema, $data);

                if (!$result['success']) {
                    $response->getBody()->write(json_encode(['error' => $result['error']]));
                    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
                }

                // Auditar creacion exitosa
                $this->auditService->logUserCreate($actorId, $actorUsername, $result['id'], $data, $sistema);

                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Usuario creado exitosamente',
                    'sistema' => $sistema,
                    'user_id' => $result['id']
                ]));

                return $response->withHeader('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Error al crear usuario: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function updateUser(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $userRol = $request->getAttribute('user_rol');

        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'No autorizado']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $sistema = $data['sistema'] ?? 'secmusuarios';
        $id = $data['id'] ?? 0;

        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'ID de usuario es requerido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $actorId = $request->getAttribute('user_id');
            $actorUsername = $this->getActorUsername($actorId);

            $result = $this->updateInSystem($sistema, $data['username'], $data);

            if (!$result['success']) {
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            // Auditar actualización
            $this->auditService->logUserUpdate($actorId, $actorUsername, $id, [], $data, $sistema);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'sistema' => $sistema
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Error al actualizar usuario: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function deleteUser(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $userRol = $request->getAttribute('user_rol');

        if ($userRol !== 'superadmin') {
            $response->getBody()->write(json_encode(['error' => 'Solo superadmin puede eliminar usuarios']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $data = json_decode($request->getBody()->getContents(), true);
        $sistema = $data['sistema'] ?? 'secmusuarios';
        $id = $data['id'] ?? 0;
        $username = $data['username'] ?? '';

        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'ID de usuario es requerido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $actorId = $request->getAttribute('user_id');
            $actorUsername = $this->getActorUsername($actorId);

            $result = $this->deleteFromSystem($sistema, $id, $username);

            if (!$result['success']) {
                $response->getBody()->write(json_encode(['error' => $result['error']]));
                return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
            }

            // Auditar eliminación
            $this->auditService->logUserDelete($actorId, $actorUsername, $id, ['username' => $username], $sistema);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente',
                'sistema' => $sistema
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Error al eliminar usuario: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    private function createInSystem(string $sistema, array $data): array
    {
        switch ($sistema) {
            case 'secmusuarios':
                return $this->createInMaster($data);
            case 'secmalquileres':
                return $this->createInSecmalquileres($data);
            case 'secmti':
                return $this->createInSecmti($data);
            case 'secmautos':
                return $this->createInSecmautos($data);
            case 'secmrrhh':
                return $this->createInSecmrrhh($data);
            case 'Psitios':
                return $this->createInPsitios($data);
            case 'secmagencias':
                return $this->createInSecmagencias($data);
            default:
                return ['success' => false, 'error' => 'Sistema no válido'];
        }
    }

    private function updateInSystem(string $sistema, string $username, array $data): array
    {
        switch ($sistema) {
            case 'secmusuarios':
                return $this->updateInMaster($data['id'], $data);
            case 'secmalquileres':
                return $this->updateInSecmalquileres($username, $data);
            case 'secmti':
                return $this->updateInSecmti($username, $data);
            case 'secmautos':
                return $this->updateInSecmautos($username, $data);
            case 'secmrrhh':
                return $this->updateInSecmrrhh($username, $data);
            case 'Psitios':
                return $this->updateInPsitios($username, $data);
            case 'secmagencias':
                return $this->updateInSecmagencias($username, $data);
            default:
                return ['success' => false, 'error' => 'Sistema no válido'];
        }
    }

    private function deleteFromSystem(string $sistema, int $id, string $username): array
    {
        switch ($sistema) {
            case 'secmusuarios':
                return $this->deleteFromMaster($id);
            case 'secmalquileres':
                return $this->deleteFromSecmalquileres($username);
            case 'secmti':
                return $this->deleteFromSecmti($username);
            case 'secmautos':
                return $this->deleteFromSecmautos($username);
            case 'secmrrhh':
                return $this->deleteFromSecmrrhh($username);
            case 'Psitios':
                return $this->deleteFromPsitios($username);
            case 'secmagencias':
                return $this->deleteFromSecmagencias($username);
            default:
                return ['success' => false, 'error' => 'Sistema no válido'];
        }
    }

    private function createInMaster(array $data): array
    {
        if (!$this->pdo) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO users_master (username, email, password_hash, nombre, apellido, rol, sistema_origen, activo) VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([
                $data['username'],
                $data['email'] ?? null,
                password_hash($data['password'], PASSWORD_ARGON2ID),
                $data['nombre'] ?? '',
                $data['apellido'] ?? '',
                $data['rol'] ?? 'user',
                'secmusuarios'
            ]);

            return ['success' => true, 'id' => (int)$this->pdo->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createInSecmalquileres(array $data): array
    {
        if (!$this->pdoSecmalquileres) return ['success' => false, 'error' => 'Conexión no disponible'];

        $rolMapping = [
            'user' => 'usuario',
            'admin' => 'admin',
            'superadmin' => 'admin'
        ];

        try {
            $stmt = $this->pdoSecmalquileres->prepare(
                "INSERT INTO users (username, email, password, nombre_completo, rol, activo, fecha_registro) VALUES (?, ?, ?, ?, ?, 1, NOW())"
            );
            $stmt->execute([
                $data['username'],
                $data['email'] ?? '',
                password_hash($data['password'], PASSWORD_ARGON2ID),
                $data['nombre_completo'] ?? $data['nombre'] . ' ' . $data['apellido'] ?? $data['username'],
                $rolMapping[$data['rol'] ?? 'user'] ?? 'usuario'
            ]);

            return ['success' => true, 'id' => (int)$this->pdoSecmalquileres->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createInSecmti(array $data): array
    {
        if (!$this->pdoSecmti) return ['success' => false, 'error' => 'Conexión no disponible'];

        $roleMapping = [
            'user' => 'user',
            'admin' => 'admin',
            'superadmin' => 'admin'
        ];

        $fullName = trim(($data['nombre'] ?? '') . ' ' . ($data['apellido'] ?? ''));

        try {
            $stmt = $this->pdoSecmti->prepare(
                "INSERT INTO users (username, email, pass_hash, full_name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $data['username'],
                $data['email'] ?? '',
                password_hash($data['password'], PASSWORD_ARGON2ID),
                $fullName ?: $data['nombre_completo'] ?? $data['username'],
                $roleMapping[$data['rol'] ?? 'user'] ?? 'user'
            ]);

            return ['success' => true, 'id' => (int)$this->pdoSecmti->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createInSecmautos(array $data): array
    {
        if (!$this->pdoSecmautos) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdoSecmautos->prepare(
                "INSERT INTO usuarios (username, email, password_hash, nombre, apellido, rol, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
            );
            $stmt->execute([
                $data['username'],
                $data['email'] ?? '',
                password_hash($data['password'], PASSWORD_ARGON2ID),
                $data['nombre'] ?? '',
                $data['apellido'] ?? '',
                $data['rol'] ?? 'user'
            ]);

            return ['success' => true, 'id' => (int)$this->pdoSecmautos->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createInSecmrrhh(array $data): array
    {
        if (!$this->pdoSecmrrhh) return ['success' => false, 'error' => 'Conexión no disponible'];

        // Mapeo de roles: usamos slug para buscar en la BD
        $slugMapping = [
            'user' => 'usuario',
            'admin' => 'admin',
            'superadmin' => 'superadmin'
        ];

        $slug = $slugMapping[$data['rol'] ?? 'user'] ?? 'usuario';

        try {
            // Buscar rol por slug (más confiable que por nombre)
            $stmt = $this->pdoSecmrrhh->prepare("SELECT id_rol, nombre FROM roles WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $rolRow = $stmt->fetch();

            if (!$rolRow) {
                // Fallback: buscar rol por defecto (Usuario)
                $stmt = $this->pdoSecmrrhh->prepare("SELECT id_rol, nombre FROM roles WHERE slug = 'usuario' LIMIT 1");
                $stmt->execute();
                $rolRow = $stmt->fetch();
            }

            if (!$rolRow) {
                return ['success' => false, 'error' => 'No se encontró rol válido en secmrrhh'];
            }

            $idRol = $rolRow['id_rol'];
            $rolNombre = $rolRow['nombre'];

            $nombreCompleto = trim(($data['nombre'] ?? '') . ' ' . ($data['apellido'] ?? ''));

            $stmt = $this->pdoSecmrrhh->prepare(
                "INSERT INTO usuarios (username, email, password, nombre_completo, rol, id_rol, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, 'activo', NOW())"
            );
            $stmt->execute([
                $data['username'],
                $data['email'] ?? '',
                password_hash($data['password'], PASSWORD_ARGON2ID),
                $nombreCompleto ?: $data['username'],
                $slug,
                $idRol
            ]);

            return ['success' => true, 'id' => (int)$this->pdoSecmrrhh->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createInPsitios(array $data): array
    {
        if (!$this->pdoPsitios) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdoPsitios->prepare(
                "INSERT INTO users (username, password_hash, role, is_active, created_at) VALUES (?, ?, ?, 1, NOW())"
            );
            $stmt->execute([
                $data['username'],
                password_hash($data['password'], PASSWORD_ARGON2ID),
                $data['rol'] ?? 'user'
            ]);

            return ['success' => true, 'id' => (int)$this->pdoPsitios->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function createInSecmagencias(array $data): array
    {
        if (!$this->pdoSecmagencias) return ['success' => false, 'error' => 'Conexion no disponible'];

        $rolMapping = [
            'user' => 4,
            'admin' => 1,
            'superadmin' => 1
        ];

        try {
            $rolId = $rolMapping[$data['rol'] ?? 'user'] ?? 4;

            $stmt = $this->pdoSecmagencias->prepare(
                "INSERT INTO usuarios (username, email, password, nombre, apellido, rol_id, activo, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
            );
            $stmt->execute([
                $data['username'],
                $data['email'] ?? '',
                password_hash($data['password'], PASSWORD_ARGON2ID),
                $data['nombre'] ?? '',
                $data['apellido'] ?? '',
                $rolId
            ]);

            return ['success' => true, 'id' => (int)$this->pdoSecmagencias->lastInsertId()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function updateInMaster(int $id, array $data): array
    {
        if (!$this->pdo) return ['success' => false, 'error' => 'Conexión no disponible'];

        $fields = [];
        $values = [];

        foreach (['email', 'nombre', 'apellido', 'rol', 'activo'] as $field) {
            if (isset($data[$field])) {
                if ($field === 'nombre' || $field === 'apellido') {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                } else {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password_hash = ?";
            $values[] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (!empty($fields)) {
            $fields[] = "updated_at = CURRENT_TIMESTAMP";
            $values[] = $id;
            $sql = "UPDATE users_master SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'No hay campos para actualizar'];
    }

    private function updateInSecmalquileres(string $username, array $data): array
    {
        if (!$this->pdoSecmalquileres) return ['success' => false, 'error' => 'Conexión no disponible'];

        $fields = [];
        $values = [];

        foreach (['email', 'nombre_completo', 'rol', 'activo'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (!empty($fields)) {
            $values[] = $username;
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE username = ?";
            $stmt = $this->pdoSecmalquileres->prepare($sql);
            $stmt->execute($values);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'No hay campos para actualizar'];
    }

    private function updateInSecmti(string $username, array $data): array
    {
        if (!$this->pdoSecmti) return ['success' => false, 'error' => 'Conexión no disponible'];

        $fields = [];
        $values = [];

        foreach (['email', 'full_name', 'role'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (!empty($fields)) {
            $values[] = $username;
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE username = ?";
            $stmt = $this->pdoSecmti->prepare($sql);
            $stmt->execute($values);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'No hay campos para actualizar'];
    }

    private function updateInSecmautos(string $username, array $data): array
    {
        if (!$this->pdoSecmautos) return ['success' => false, 'error' => 'Conexión no disponible'];

        $fields = [];
        $values = [];

        foreach (['email', 'nombre', 'apellido', 'rol', 'activo'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (!empty($fields)) {
            $values[] = $username;
            $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE username = ?";
            $stmt = $this->pdoSecmautos->prepare($sql);
            $stmt->execute($values);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'No hay campos para actualizar'];
    }

    private function updateInSecmrrhh(string $username, array $data): array
    {
        if (!$this->pdoSecmrrhh) return ['success' => false, 'error' => 'Conexión no disponible'];

        $fields = [];
        $values = [];

        foreach (['email', 'nombre_completo', 'rol', 'estado'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (!empty($fields)) {
            $values[] = $username;
            $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE username = ?";
            $stmt = $this->pdoSecmrrhh->prepare($sql);
            $stmt->execute($values);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'No hay campos para actualizar'];
    }

    private function updateInPsitios(string $username, array $data): array
    {
        if (!$this->pdoPsitios) return ['success' => false, 'error' => 'Conexión no disponible'];

        $fields = [];
        $values = [];

        foreach (['role', 'is_active'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (!empty($fields)) {
            $values[] = $username;
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE username = ?";
            $stmt = $this->pdoPsitios->prepare($sql);
            $stmt->execute($values);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'No hay campos para actualizar'];
    }

    private function updateInSecmagencias(string $username, array $data): array
    {
        if (!$this->pdoSecmagencias) return ['success' => false, 'error' => 'Conexión no disponible'];

        $fields = [];
        $values = [];

        foreach (['email', 'nombre', 'apellido', 'activo'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (isset($data['rol'])) {
            $stmt = $this->pdoSecmagencias->prepare("SELECT id FROM roles WHERE nombre = ? LIMIT 1");
            $stmt->execute([$data['rol']]);
            $rol = $stmt->fetch();
            if ($rol) {
                $fields[] = "rol_id = ?";
                $values[] = $rol['id'];
            }
        }

        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_ARGON2ID);
        }

        if (!empty($fields)) {
            $values[] = $username;
            $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE username = ?";
            $stmt = $this->pdoSecmagencias->prepare($sql);
            $stmt->execute($values);
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'No hay campos para actualizar'];
    }

    private function deleteFromMaster(int $id): array
    {
        if (!$this->pdo) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdo->prepare("DELETE FROM users_master WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function deleteFromSecmalquileres(string $username): array
    {
        if (!$this->pdoSecmalquileres) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdoSecmalquileres->prepare("DELETE FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function deleteFromSecmti(string $username): array
    {
        if (!$this->pdoSecmti) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdoSecmti->prepare("DELETE FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function deleteFromSecmautos(string $username): array
    {
        if (!$this->pdoSecmautos) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdoSecmautos->prepare("DELETE FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function deleteFromSecmrrhh(string $username): array
    {
        if (!$this->pdoSecmrrhh) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdoSecmrrhh->prepare("DELETE FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function deleteFromPsitios(string $username): array
    {
        if (!$this->pdoPsitios) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdoPsitios->prepare("DELETE FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function deleteFromSecmagencias(string $username): array
    {
        if (!$this->pdoSecmagencias) return ['success' => false, 'error' => 'Conexión no disponible'];

        try {
            $stmt = $this->pdoSecmagencias->prepare("DELETE FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Desbloquea una cuenta de usuario (solo admin/superadmin)
     */
    public function unlockUser(Request $request, Response $response, array $args): Response
    {
        $userRol = $request->getAttribute('user_rol');
        $actorId = $request->getAttribute('user_id');

        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'No autorizado']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $targetUserId = (int)$args['id'];

        // Obtener datos del usuario antes de desbloquear
        $stmt = $this->pdo->prepare("SELECT id, username FROM users_master WHERE id = ?");
        $stmt->execute([$targetUserId]);
        $targetUser = $stmt->fetch();

        if (!$targetUser) {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Desbloquear cuenta
        $stmt = $this->pdo->prepare("UPDATE users_master SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?");
        $stmt->execute([$targetUserId]);

        // Auditar desbloqueo
        $actorUsername = $this->getActorUsername($actorId);
        $this->auditService->logAccountUnlocked($actorId, $actorUsername, $targetUserId, $targetUser['username']);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Cuenta desbloqueada exitosamente'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Obtiene el username del actor para auditoría
     */
    private function getActorUsername(int $actorId): string
    {
        $stmt = $this->pdo->prepare("SELECT username FROM users_master WHERE id = ?");
        $stmt->execute([$actorId]);
        $user = $stmt->fetch();
        return $user['username'] ?? 'unknown';
    }
}