<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/reportes.php';
require_once __DIR__ . '/../models/historial_model.php';

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
if (!csrf_validate()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token de sesión inválido. Recargue la página.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

if ($action === 'toggle_estado_pago') {
    $procesoClienteId = (int) ($input['proceso_cliente_id'] ?? 0);
    $res = toggleEstadoPagoProcesoCliente($procesoClienteId);
    if (!$res['ok']) {
        http_response_code(400);
    } else {
        $stmt = $conn->prepare("SELECT cliente_id FROM proceso_cliente WHERE proceso_cliente_id = ?");
        $stmt->execute([$procesoClienteId]);
        $clienteId = (int) ($stmt->fetchColumn() ?: 0);
        $nombreCliente = $clienteId ? obtenerNombreCliente($clienteId) : '';
        registrarActividad($conn, 'CAMBIO_ESTADO_PAGO', 'Cambió estado de pago para el cliente "' . ($nombreCliente ?: '') . '"');
    }
    echo json_encode($res);
} elseif ($action === 'eliminar_procesos_cliente') {
    $clienteId = (int) ($input['cliente_id'] ?? 0);
    $res = eliminarProcesosCliente($clienteId);
    if (!$res['ok']) {
        http_response_code(400);
    } else {
        $nombreCliente = $clienteId ? obtenerNombreCliente($clienteId) : '';
        registrarActividad($conn, 'ELIMINAR_PROCESOS_CLIENTE', 'Eliminó los procesos del cliente "' . ($nombreCliente ?: '') . '"');
    }
    echo json_encode($res);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}
