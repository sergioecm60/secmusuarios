<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use App\Utils\JwtHandler;

class AuthMiddleware
{
    private $jwt;

    public function __construct(JwtHandler $jwt)
    {
        $this->jwt = $jwt;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token no proporcionado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $token = $matches[1];
        $userData = $this->jwt->decode($token);

        if (!$userData) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Token inválido o expirado']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $request = $request->withAttribute('user_id', $userData['user_id']);
        $request = $request->withAttribute('user_rol', $userData['rol'] ?? 'user');
        $request = $request->withAttribute('user_apps', $userData['apps'] ?? []);

        return $handler->handle($request);
    }

    public static function validarPara(string $sistema, string $secret): ?array
    {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $token);

        $data = JwtHandler::validar($secret, $token);
        if (!$data) return null;

        $apps = $data->apps ?? [];
        if (!in_array($sistema, $apps)) return null;

        return [
            'user_id' => $data->user_id,
            'username' => $data->username,
            'rol' => $data->rol ?? 'user',
            'apps' => (array)$apps
        ];
    }
}