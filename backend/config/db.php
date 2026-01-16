<?php

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'usuarios';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión a la base de datos"]);
    exit;
}

$pdoSecmalquileres = null;
if (!empty($_ENV['DB_SECMALQUILERES'])) {
    try {
        $pdoSecmalquileres = new PDO("mysql:host=$host;port=$port;dbname=" . $_ENV['DB_SECMALQUILERES'] . ";charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $pdoSecmalquileres = null;
    }
}

$pdoSecmti = null;
if (!empty($_ENV['DB_SECMTI'])) {
    try {
        $pdoSecmti = new PDO("mysql:host=$host;port=$port;dbname=" . $_ENV['DB_SECMTI'] . ";charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $pdoSecmti = null;
    }
}

$pdoSecmautos = null;
if (!empty($_ENV['DB_SECMAUTOS'])) {
    try {
        $pdoSecmautos = new PDO("mysql:host=$host;port=$port;dbname=" . $_ENV['DB_SECMAUTOS'] . ";charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $pdoSecmautos = null;
    }
}

$pdoSecmrrhh = null;
if (!empty($_ENV['DB_SECMRRHH'])) {
    try {
        $pdoSecmrrhh = new PDO("mysql:host=$host;port=$port;dbname=" . $_ENV['DB_SECMRRHH'] . ";charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $pdoSecmrrhh = null;
    }
}

$pdoPsitios = null;
if (!empty($_ENV['DB_PSITIOS'])) {
    try {
        $pdoPsitios = new PDO("mysql:host=$host;port=$port;dbname=" . $_ENV['DB_PSITIOS'] . ";charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $pdoPsitios = null;
    }
}

$pdoSecmagencias = null;
if (!empty($_ENV['DB_SECMAGENCIAS'])) {
    try {
        $pdoSecmagencias = new PDO("mysql:host=$host;port=$port;dbname=" . $_ENV['DB_SECMAGENCIAS'] . ";charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $pdoSecmagencias = null;
    }
}