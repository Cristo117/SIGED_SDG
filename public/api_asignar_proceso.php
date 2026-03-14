<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/reportes.php';
require_once __DIR__ . '/../models/historial_model.php';

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
if (!csrf_validate()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token de sesión inválido. Recargue la página.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$clienteId = (int) ($input['cliente_id'] ?? 0);
$procesoId = (int) ($input['proceso_id'] ?? 0);
$empleadoId = !empty($input['empleado_id']) ? (int) $input['empleado_id'] : null;

$res = asignarProcesoACliente($clienteId, $procesoId, $empleadoId);
if (!$res['ok']) {
    http_response_code(400);
} else {
    $nombreCliente = obtenerNombreCliente($clienteId);
    registrarActividad($conn, 'ASIGNAR_PROCESO', 'Asignó un proceso al cliente "' . ($nombreCliente ?: '') . '"');
}
echo json_encode($res);
