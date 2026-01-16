<?php

namespace App\Controllers;

use App\Models\User;
use App\Utils\JwtHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private $userModel;
    private $jwt;

    public function __construct(User $userModel, JwtHandler $jwt)
    {
        $this->userModel = $userModel;
        $this->jwt = $jwt;
    }

    public function login(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        if (empty($data['username']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Usuario y contraseña son requeridos']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $user = $this->userModel->findByUsername($data['username']);

        if (!$user || !$this->userModel->verifyPassword($data['password'], $user['password_hash'])) {
            $response->getBody()->write(json_encode(['error' => 'Credenciales inválidas']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $this->userModel->updateLastLogin($user['id']);

        $apps = $this->getAppsPermitidas($user['id']);

        $token = $this->jwt->encode([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'rol' => $user['rol'],
            'apps' => $apps
        ]);

        $response->getBody()->write(json_encode([
            'success' => true,
            'token' => $token,
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
}