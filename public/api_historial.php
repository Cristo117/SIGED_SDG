<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/historial_model.php';

requireAuth();

if (!esAdministrador()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Solo el administrador puede ver el historial']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $soloColab = !empty($_GET['solo_colaboradores']);
    $historial = listarHistorial($conn, 500, $soloColab);
    // Excluir datos sensibles: id, usuario_id, detalle
    $historial = array_map(function ($h) {
        unset($h['id'], $h['usuario_id'], $h['detalle']);
        return $h;
    }, $historial);
    echo json_encode(['ok' => true, 'historial' => $historial]);
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

if ($action === 'limpiar') {
    try {
        limpiarHistorial($conn);
        echo json_encode(['ok' => true]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Error al limpiar el historial']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
