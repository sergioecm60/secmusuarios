<?php

header('Content-Type: application/json');

$host = 'localhost';
$port = '3306';
$user = 'root';
$pass = '';

$tests = [];

try {
    $tests['usuarios'] = new PDO("mysql:host=$host;port=$port;dbname=usuarios;charset=utf8mb4", $user, $pass);
    $tests['gestion_alquileres'] = new PDO("mysql:host=$host;port=$port;dbname=gestion_alquileres;charset=utf8mb4", $user, $pass);
    $tests['portal_db'] = new PDO("mysql:host=$host;port=$port;dbname=portal_db;charset=utf8mb4", $user, $pass);
    $tests['secmautos_db'] = new PDO("mysql:host=$host;port=$port;dbname=secmautos_db;charset=utf8mb4", $user, $pass);
    $tests['secmrrhh'] = new PDO("mysql:host=$host;port=$port;dbname=secmrrhh;charset=utf8mb4", $user, $pass);
    $tests['secure_panel_db'] = new PDO("mysql:host=$host;port=$port;dbname=secure_panel_db;charset=utf8mb4", $user, $pass);
    $tests['sistema_transportes'] = new PDO("mysql:host=$host;port=$port;dbname=sistema_transportes;charset=utf8mb4", $user, $pass);
} catch (\Exception $e) {
    $tests['error'] = $e->getMessage();
}

echo json_encode([
    'success' => true,
    'tests' => $tests,
    'error' => $tests['error'] ?? null
], JSON_PRETTY_PRINT);