<?php

header('Content-Type: application/json');

function createPdo($database, $host = 'localhost', $port = '3306', $user = 'root', $pass = '') {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

$tests = [];

$tests['secmusuarios'] = createPdo('usuarios');
$tests['secmalquileres'] = createPdo('gestion_alquileres');
$tests['secmti'] = createPdo('portal_db');
$tests['secmautos'] = createPdo('secmautos_db');
$tests['secmrrhh'] = createPdo('secmrrhh');
$tests['Psitios'] = createPdo('secure_panel_db');
$tests['secmagencias'] = createPdo('sistema_transportes');

echo json_encode([
    'success' => true,
    'tests' => $tests
], JSON_PRETTY_PRINT);