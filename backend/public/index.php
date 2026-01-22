<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\AuthController;
use App\Controllers\UsersController;
use App\Controllers\AuditController;
use App\Middleware\AuthMiddleware;
use App\Models\User;
use App\Utils\JwtHandler;
use App\Services\AuditService;
use App\Services\RefreshTokenService;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

require __DIR__ . '/../config/db.php';

$container = new \DI\Container();
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$container->set(User::class, function () use ($pdo) {
    return new User($pdo);
});

$container->set(JwtHandler::class, function () {
    return new JwtHandler();
});

$container->set(AuditService::class, function () use ($pdo) {
    return new AuditService($pdo);
});

$container->set(RefreshTokenService::class, function () use ($pdo) {
    return new RefreshTokenService($pdo);
});

$container->set(AuthController::class, function ($c) {
    return new AuthController(
        $c->get(User::class),
        $c->get(JwtHandler::class),
        $c->get(AuditService::class),
        $c->get(RefreshTokenService::class)
    );
});

$container->set(UsersController::class, function ($c) use ($pdo) {
    return new UsersController($pdo, $c->get(AuditService::class));
});

$container->set(AuditController::class, function ($c) {
    return new AuditController($c->get(AuditService::class));
});

$container->set(AuthMiddleware::class, function ($c) {
    return new AuthMiddleware($c->get(JwtHandler::class));
});

$app->group('/api', function (RouteCollectorProxy $group) {
    $group->post('/login', [AuthController::class, 'login']);
    $group->post('/register', [AuthController::class, 'register']);
    $group->post('/validate', [AuthController::class, 'validate']);
    $group->post('/refresh', [AuthController::class, 'refresh']);
});

$app->group('/api', function (RouteCollectorProxy $group) {
    $group->get('/me', [AuthController::class, 'me']);
    $group->get('/users', [AuthController::class, 'getAllUsers']);
    $group->get('/users/all', [UsersController::class, 'getAllSystemsUsers']);
    $group->post('/users', [UsersController::class, 'createUser']);
    $group->put('/users', [UsersController::class, 'updateUser']);
    $group->delete('/users', [UsersController::class, 'deleteUser']);
    $group->post('/users/{id}/unlock', [UsersController::class, 'unlockUser']);
    $group->get('/audit', [AuditController::class, 'getAuditLogs']);
    $group->post('/logout', [AuthController::class, 'logout']);
    $group->post('/logout-all', [AuthController::class, 'logoutAll']);
    $group->get('/sessions', [AuthController::class, 'getSessions']);
})->add(new AuthMiddleware($container->get(JwtHandler::class)));

$app->get('/', function ($request, $response) {
    $params = $request->getQueryParams();
    $redirect = $params['redirect'] ?? 'http://secmusuarios.test:8081/dashboard.html';
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Login...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .loading {
            text-align: center;
            color: white;
        }
        .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 4px solid white;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading">
        <div class="spinner"></div>
        <h2>Redirigiendo al login...</h2>
    </div>
    <script>
        const redirectUrl = '$redirect';
        const loginUrl = 'http://localhost/secmusuarios/frontend/login.html?redirect=' + encodeURIComponent(redirectUrl);
        setTimeout(function() {
            window.location.href = loginUrl;
        }, 1000);
    </script>
</body>
</html>
HTML;
    
    $response->getBody()->write($html);
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/api', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'name' => 'SECM Usuarios API',
        'version' => '1.0.0',
        'description' => 'Sistema Centralizado de Gestión de Usuarios para proyectos SECM',
        'endpoints' => [
            'POST /api/login' => 'Autenticación y generación de token JWT',
            'POST /api/validate' => 'Validación de token JWT',
            'POST /api/register' => 'Registro de nuevos usuarios',
            'GET /api/me' => 'Obtener datos del usuario actual (requiere token)',
            'GET /api/users' => 'Listar todos los usuarios (requiere token y rol admin/superadmin)'
        ],
        'documentation' => 'Consulte README.md para más detalles',
        'shared_library' => 'backend/shared/SecmAuth.php para integración en otros sistemas'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();