<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/empleados.php';
require_once __DIR__ . '/../models/historial_model.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

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
    case 'reactivar':
        $id = (int) ($input['cliente_id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID no válido']);
            exit;
        }
        $cliente = obtenerClientePorId($id);
        if (!$cliente) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Cliente no encontrado']);
            exit;
        }
        if (!empty($cliente['activo']) && $cliente['activo'] == 1) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El cliente ya está activo']);
            exit;
        }
        try {
            reactivarCliente($id);
            $nombreCli = trim(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellidos'] ?? ''));
            registrarActividad($conn, 'REACTIVAR_CLIENTE', 'Reactivó al cliente "' . $nombreCli . '"');
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Error al reactivar']);
        }
        break;

    case 'reasignar_empleado':
        $empleadoId = (int) ($input['empleado_id'] ?? 0);
        $nuevoClienteId = (int) ($input['nuevo_cliente_id'] ?? 0);
        $clienteInactivoId = (int) ($input['cliente_inactivo_id'] ?? 0);

        if ($empleadoId <= 0 || $nuevoClienteId <= 0 || $clienteInactivoId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        $clienteDestino = obtenerClientePorId($nuevoClienteId);
        if (!$clienteDestino) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Empleador destino no encontrado']);
            exit;
        }
        if (isset($clienteDestino['activo']) && $clienteDestino['activo'] == 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El empleador destino está inactivo']);
            exit;
        }
        if (($clienteDestino['tipo_cliente'] ?? '') !== 'EMPLEADOR') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'El cliente destino debe ser tipo Empleador']);
            exit;
        }

        $empleados = obtenerEmpleadosPorCliente($clienteInactivoId);
        $encontrado = false;
        foreach ($empleados as $e) {
            if ((int) $e['empleado_id'] === $empleadoId) {
                $encontrado = true;
                break;
            }
        }
        if (!$encontrado) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Empleado no pertenece a este cliente']);
            exit;
        }

        try {
            reasignarEmpleado($empleadoId, $nuevoClienteId);
            $clienteDestino = obtenerClientePorId($nuevoClienteId);
            $nombreDestino = trim(($clienteDestino['nombre'] ?? '') . ' ' . ($clienteDestino['apellidos'] ?? ''));
            registrarActividad($conn, 'REASIGNAR_EMPLEADO', 'Reasignó un empleado al cliente "' . $nombreDestino . '"');
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Error al reasignar']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}
