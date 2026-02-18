<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/reportes.php';

requireAuth();
asegurarColumnaEstadoPagoProceso();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $clienteId = (int) ($_GET['cliente_id'] ?? 0);
    if ($clienteId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'cliente_id requerido']);
        exit;
    }
    echo json_encode([
        'procesos' => obtenerProcesosPorCliente($clienteId),
        'estado_pago' => obtenerEstadoPagoCliente($clienteId),
        'nombre_cliente' => obtenerNombreCliente($clienteId)
    ]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

if ($action === 'toggle_estado_pago') {
    $procesoClienteId = (int) ($input['proceso_cliente_id'] ?? 0);
    $res = toggleEstadoPagoProcesoCliente($procesoClienteId);
    if (!$res['ok']) {
        http_response_code(400);
    }
    echo json_encode($res);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}
