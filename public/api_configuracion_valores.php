<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/reportes.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    asegurarConfiguracionInicial();
    echo json_encode(obtenerConfiguracionValores());
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

switch ($action) {
    case 'guardar_afiliacion':
        $valor = $input['valor_afiliacion'] ?? 0;
        $valor = (float) preg_replace('/[^\d]/', '', (string) $valor);
        guardarValorAfiliacion($valor);
        echo json_encode(['ok' => true]);
        break;

    case 'agregar_proceso':
        $nombre = trim($input['nombre'] ?? '');
        $valor = $input['valor'] ?? 0;
        $res = agregarProceso($nombre, $valor);
        if (!$res['ok']) {
            http_response_code(400);
        }
        echo json_encode($res);
        break;

    case 'editar_proceso':
        $id = (int) ($input['proceso_id'] ?? 0);
        $nombre = trim($input['nombre'] ?? '');
        $valor = $input['valor'] ?? 0;
        $res = actualizarProceso($id, $nombre, $valor);
        if (!$res['ok']) {
            http_response_code(400);
        }
        echo json_encode($res);
        break;

    case 'eliminar_proceso':
        $id = (int) ($input['proceso_id'] ?? 0);
        $res = eliminarProceso($id);
        if (!$res['ok']) {
            http_response_code(400);
        }
        echo json_encode($res);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}
