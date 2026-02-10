<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/reportes.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode([
        'clientes' => obtenerClientesParaAsignar(),
        'procesos' => obtenerProcesosActivos()
    ]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$clienteId = (int) ($input['cliente_id'] ?? 0);
$procesoId = (int) ($input['proceso_id'] ?? 0);

$res = asignarProcesoACliente($clienteId, $procesoId);
if (!$res['ok']) {
    http_response_code(400);
}
echo json_encode($res);
