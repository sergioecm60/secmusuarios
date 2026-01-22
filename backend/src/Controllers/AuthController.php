<?php

namespace App\Controllers;

use App\Models\User;
use App\Utils\JwtHandler;
use App\Services\AuditService;
use App\Services\RefreshTokenService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private $userModel;
    private $jwt;
    private $auditService;
    private $refreshTokenService;

    public function __construct(
        User $userModel,
        JwtHandler $jwt,
        AuditService $auditService,
        RefreshTokenService $refreshTokenService
    ) {
        $this->userModel = $userModel;
        $this->jwt = $jwt;
        $this->auditService = $auditService;
        $this->refreshTokenService = $refreshTokenService;
    }

    public function login(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['username']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Usuario y contraseña son requeridos']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Verificar si la cuenta está bloqueada
        $lockStatus = $this->userModel->isAccountLocked($data['username']);

        if ($lockStatus === null) {
            // Usuario no existe
            $this->auditService->logLoginFailed($data['username'], 'user_not_found');
            $response->getBody()->write(json_encode(['error' => 'Credenciales inválidas']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if ($lockStatus['locked']) {
            $this->auditService->logLoginFailed($data['username'], 'account_locked');
            $response->getBody()->write(json_encode([
                'error' => 'Cuenta bloqueada temporalmente',
                'locked_until' => $lockStatus['until'],
                'remaining_minutes' => $lockStatus['remaining_minutes']
            ]));
            return $response->withStatus(423)->withHeader('Content-Type', 'application/json');
        }

        $user = $this->userModel->findByUsername($data['username']);

        if (!$user || !$this->userModel->verifyPassword($data['password'], $user['password_hash'])) {
            // Incrementar intentos fallidos
            $result = $this->userModel->incrementFailedAttempts($data['username']);

            if ($result['locked']) {
                $this->auditService->logAccountLocked($data['username'], $result['user_id'], $result['attempts']);
                $response->getBody()->write(json_encode([
                    'error' => 'Demasiados intentos fallidos. Cuenta bloqueada temporalmente.',
                    'locked' => true
                ]));
                return $response->withStatus(423)->withHeader('Content-Type', 'application/json');
            }

            $this->auditService->logLoginFailed($data['username'], 'invalid_password');
            $response->getBody()->write(json_encode([
                'error' => 'Credenciales inválidas',
                'attempts_remaining' => $result['remaining']
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Login exitoso - resetear contador de intentos
        $this->userModel->resetFailedAttempts($user['id']);
        $this->userModel->updateLastLogin($user['id']);
        $this->auditService->logLoginSuccess($user['id'], $user['username']);

        $apps = $this->getAppsPermitidas($user['id']);

        // Generar access token
        $accessToken = $this->jwt->encode([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'rol' => $user['rol'],
            'apps' => $apps
        ]);

        // Generar refresh token
        $refreshToken = $this->refreshTokenService->generateToken($user['id']);

        $response->getBody()->write(json_encode([
            'success' => true,
            'token' => $accessToken,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->jwt->getExpiresIn(),
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'nombre_completo' => $user['nombre_completo'],
                'email' => $user['email'],
                'rol' => $user['rol'],
                'apps' => $apps,
                'primer_login' => $user['primer_login']
            ]
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function validate(Request $request, Response $response): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $response->getBody()->write(json_encode(['error' => 'Token no proporcionado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = $matches[1];
        $userData = $this->jwt->decode($token);

        if (!$userData) {
            $response->getBody()->write(json_encode(['error' => 'Token inválido o expirado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'user_id' => $userData['user_id'],
            'username' => $userData['username'],
            'rol' => $userData['rol'],
            'apps' => $userData['apps'] ?? []
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function getAppsPermitidas(int $userId): array
    {
        return [
            'secmalquileres',
            'secmti',
            'secmautos',
            'secmrrhh',
            'Psitios',
            'secmagencias'
        ];
    }

    public function register(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $required = ['username', 'email', 'password', 'nombre_completo'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode(['error' => "Campo '$field' es requerido"]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        try {
            $userId = $this->userModel->create($data);
            $user = $this->userModel->findById($userId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'user' => $user
            ]));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Error al crear usuario: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function me(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = $this->userModel->findById($userId);

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(['success' => true, 'user' => $user]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getAllUsers(Request $request, Response $response): Response
    {
        $userRol = $request->getAttribute('user_rol');

        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'No autorizado']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $users = $this->userModel->getAll();
        $total = $this->userModel->count();

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $users,
            'total' => $total
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Renueva el access token usando un refresh token valido
     */
    public function refresh(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $refreshToken = $data['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            $response->getBody()->write(json_encode(['error' => 'Refresh token requerido']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $tokenData = $this->refreshTokenService->validateToken($refreshToken);

        if (!$tokenData) {
            $response->getBody()->write(json_encode(['error' => 'Refresh token invalido o expirado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Obtener usuario actualizado
        $user = $this->userModel->findById($tokenData['user_id']);
        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Usuario no encontrado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Rotar refresh token (seguridad)
        $newRefreshToken = $this->refreshTokenService->rotateToken($refreshToken);

        // Generar nuevo access token
        $apps = $this->getAppsPermitidas($user['id']);
        $newAccessToken = $this->jwt->encode([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'rol' => $user['rol'],
            'apps' => $apps
        ]);

        $this->auditService->logTokenRefresh($user['id'], $user['username']);

        $response->getBody()->write(json_encode([
            'success' => true,
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => $this->jwt->getExpiresIn()
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Cierra la sesion actual revocando el refresh token
     */
    public function logout(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $data = json_decode($request->getBody()->getContents(), true);
        $refreshToken = $data['refresh_token'] ?? '';

        if (!empty($refreshToken)) {
            $this->refreshTokenService->revokeToken($refreshToken);
        }

        $user = $this->userModel->findById($userId);
        if ($user) {
            $this->auditService->logLogout($userId, $user['username']);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Sesion cerrada exitosamente'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Cierra todas las sesiones del usuario (logout de todos los dispositivos)
     */
    public function logoutAll(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');

        $this->refreshTokenService->revokeAllUserTokens($userId);

        $user = $this->userModel->findById($userId);
        if ($user) {
            $this->auditService->logLogoutAll($userId, $user['username']);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Todas las sesiones han sido cerradas'
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Obtiene las sesiones activas del usuario
     */
    public function getSessions(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');

        $sessions = $this->refreshTokenService->getActiveTokens($userId);

        $response->getBody()->write(json_encode([
            'success' => true,
            'sessions' => $sessions
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}