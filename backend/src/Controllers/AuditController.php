<?php

namespace App\Controllers;

use App\Services\AuditService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuditController
{
    private $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Obtiene los registros de auditoría con filtros y paginación
     * Solo accesible por admin y superadmin
     */
    public function getAuditLogs(Request $request, Response $response): Response
    {
        $userRol = $request->getAttribute('user_rol');

        // Solo admin y superadmin pueden ver auditoría
        if (!in_array($userRol, ['superadmin', 'admin'])) {
            $response->getBody()->write(json_encode(['error' => 'No autorizado']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        $params = $request->getQueryParams();

        // Paginación
        $limit = min((int)($params['limit'] ?? 50), 500); // máximo 500
        $offset = (int)($params['offset'] ?? 0);

        // Filtros
        $filters = [];
        if (!empty($params['user_id'])) {
            $filters['user_id'] = (int)$params['user_id'];
        }
        if (!empty($params['username'])) {
            $filters['username'] = $params['username'];
        }
        if (!empty($params['action'])) {
            $filters['action'] = $params['action'];
        }
        if (!empty($params['target_type'])) {
            $filters['target_type'] = $params['target_type'];
        }
        if (!empty($params['from_date'])) {
            $filters['from_date'] = $params['from_date'];
        }
        if (!empty($params['to_date'])) {
            $filters['to_date'] = $params['to_date'];
        }
        if (!empty($params['ip_address'])) {
            $filters['ip_address'] = $params['ip_address'];
        }

        $logs = $this->auditService->getAuditLogs($limit, $offset, $filters);
        $total = $this->auditService->countAuditLogs($filters);
        $actions = $this->auditService->getAvailableActions();

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $logs,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'available_actions' => $actions
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
