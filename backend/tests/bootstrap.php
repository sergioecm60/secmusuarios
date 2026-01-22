<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables de entorno de test si existe
$envFile = __DIR__ . '/../.env.testing';
if (file_exists($envFile)) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..', '.env.testing');
    $dotenv->safeLoad();
}

// Configurar variables de entorno por defecto para tests
$_ENV['APP_ENV'] = 'testing';
$_ENV['JWT_SECRET'] = $_ENV['JWT_SECRET'] ?? 'test_secret_key_for_testing_only';
$_ENV['JWT_EXPIRES_IN'] = $_ENV['JWT_EXPIRES_IN'] ?? '3600';
$_ENV['MAX_LOGIN_ATTEMPTS'] = $_ENV['MAX_LOGIN_ATTEMPTS'] ?? '3';
$_ENV['LOCKOUT_DURATION_MINUTES'] = $_ENV['LOCKOUT_DURATION_MINUTES'] ?? '5';
$_ENV['REFRESH_TOKEN_EXPIRES_DAYS'] = $_ENV['REFRESH_TOKEN_EXPIRES_DAYS'] ?? '1';
