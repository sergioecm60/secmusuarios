<?php

namespace App\Controllers;

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

    public function __construct($pdo)
    {
        global $pdoSecmalquileres, $pdoSecmti, $pdoSecmautos, $pdoSecmrrhh, $pdoPsitios, $pdoSecmagencias;
        
        $this->pdo = $pdo;
        $this->pdoSecmalquileres = $pdoSecmalquileres;
        $this->pdoSecmti = $pdoSecmti;
        $this->pdoSecmautos = $pdoSecmautos;
        $this->pdoSecmrrhh = $pdoSecmrrhh;
        $this->pdoPsitios = $pdoPsitios;
        $this->pdoSecmagencias = $pdoSecmagencias;
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
            $stmt = $this->pdoSecmalquileres->query("SELECT 'secmalquileres' as sistema, id, usuario as username, email, nombre as nombre_completo, rol as rol, activo, created_at FROM usuarios ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmti(): array
    {
        if (!$this->pdoSecmti) return [];
        try {
            $stmt = $this->pdoSecmti->query("SELECT 'secmti' as sistema, id, username, email, nombre_completo, rol as rol, activo, created_at FROM usuarios ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmautos(): array
    {
        if (!$this->pdoSecmautos) return [];
        try {
            $stmt = $this->pdoSecmautos->query("SELECT 'secmautos' as sistema, id, usuario as username, email, nombre as nombre_completo, rol as rol, activo, created_at FROM usuarios ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmrrhh(): array
    {
        if (!$this->pdoSecmrrhh) return [];
        try {
            $stmt = $this->pdoSecmrrhh->query("SELECT 'secmrrhh' as sistema, id, username, email, nombre as nombre_completo, rol as rol, activo, created_at FROM usuarios ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromPsitios(): array
    {
        if (!$this->pdoPsitios) return [];
        try {
            $stmt = $this->pdoPsitios->query("SELECT 'Psitios' as sistema, id, username, email, nombre_completo, rol as rol, activo, created_at FROM usuarios ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getFromSecmagencias(): array
    {
        if (!$this->pdoSecmagencias) return [];
        try {
            $stmt = $this->pdoSecmagencias->query("SELECT 'secmagencias' as sistema, id, usuario as username, email, nombre as nombre_completo, rol as rol, activo, created_at FROM usuarios ORDER BY id");
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }
}