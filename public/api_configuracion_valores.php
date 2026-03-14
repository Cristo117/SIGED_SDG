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
    asegurarConfiguracionInicial();
    echo json_encode(obtenerConfiguracionValores());
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

switch ($action) {
    case 'agregar_proceso':
        $nombre = trim($input['nombre'] ?? '');
        $valor = $input['valor'] ?? 0;
        $res = agregarProceso($nombre, $valor);
        if (!$res['ok']) {
            http_response_code(400);
        } else {
            registrarActividad($conn, 'AGREGAR_PROCESO', 'Agregó el proceso "' . $nombre . '"');
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
        } else {
            registrarActividad($conn, 'EDITAR_PROCESO', 'Editó el proceso "' . $nombre . '"');
        }
        echo json_encode($res);
        break;

    case 'eliminar_proceso':
        if (!puedeEliminar()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tiene permiso para eliminar']);
            exit;
        }
        $id = (int) ($input['proceso_id'] ?? 0);
        $nombreProceso = '';
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT nombre FROM proceso WHERE proceso_id = ?");
            $stmt->execute([$id]);
            $nombreProceso = trim($stmt->fetchColumn() ?: '');
        }
        $res = eliminarProceso($id);
        if (!$res['ok']) {
            http_response_code(400);
        } else {
            $desc = $nombreProceso ? 'Eliminó el proceso "' . $nombreProceso . '"' : 'Eliminó un proceso de la configuración';
            registrarActividad($conn, 'ELIMINAR_PROCESO', $desc);
        }
        echo json_encode($res);
        break;

    case 'generar_cobros_mensuales':
        $res = generarCobrosMensualesSeguridadSocial();
        if ($res['ok']) {
            registrarActividad($conn, 'GENERAR_COBROS_SS', 'Generó cobros de Seguridad Social del mes: ' . $res['insertados'] . ' cliente(s)');
        }
        echo json_encode($res);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}
