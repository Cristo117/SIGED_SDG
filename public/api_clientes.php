<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/empleados.php';
require_once __DIR__ . '/../controllers/info_adicional.php';
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
    case 'crear':
        $nombre = trim($input['nombre'] ?? '');
        $apellidos = trim($input['apellidos'] ?? '') ?: null;
        $email = trim($input['email'] ?? '') ?: null;
        $tipoIdent = trim($input['tipo_identificacion'] ?? '') ?: null;
        $identificacion = trim($input['identificacion'] ?? '') ?: null;
        $tipoCliente = $input['tipo_cliente'] ?? 'INDEPENDIENTE';

        if ($nombre === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Los nombres del cliente son obligatorios.']);
            exit;
        }

        if (!preg_match('/^[\p{L}\s\.\-]+$/u', $nombre)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Los nombres solo pueden contener letras, espacios, puntos y guiones.'
            ]);
            exit;
        }

        $cobrarSS = !empty($input['cobrar_seguridad_social_mensual']);
        $datos = [
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'email' => $email,
            'tipo_identificacion' => $tipoIdent,
            'identificacion' => $identificacion,
            'tipo_cliente' => $tipoCliente,
            'estado_pago' => 'AL_DIA',
            'cobrar_seguridad_social_mensual' => $cobrarSS,
        ];

        // Notas del cliente (título, valor) opcionales
        $notasCliente = $input['notas_cliente'] ?? [];
        if (!is_array($notasCliente)) {
            $notasCliente = [];
        }

        try {
            $clienteId = guardarCliente($datos, null);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Error al guardar el cliente en la base de datos.'
            ]);
            exit;
        }

        if ($clienteId === false) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Ya existe un cliente registrado con ese número de documento.'
            ]);
            exit;
        }

        // Guardar notas del cliente si vienen
        if (!empty($notasCliente)) {
            $pares = [];
            foreach ($notasCliente as $par) {
                $titulo = trim($par['titulo'] ?? '');
                $valor = trim($par['valor'] ?? '');
                if ($titulo !== '' || $valor !== '') {
                    $pares[] = ['titulo' => $titulo, 'valor' => $valor];
                }
            }
            if (!empty($pares)) {
                try {
                    guardarNotasCliente((int)$clienteId, $pares);
                } catch (Throwable $e) {
                    http_response_code(500);
                    echo json_encode([
                        'ok' => false,
                        'error' => 'Error al guardar las notas del cliente.'
                    ]);
                    exit;
                }
            }
        }

        registrarActividad($conn, 'CREAR_CLIENTE', 'Creó el cliente ' . $nombre . ($apellidos ? ' ' . $apellidos : ''));
        echo json_encode(['ok' => true, 'cliente_id' => (int) $clienteId]);
        break;

    case 'actualizar':
        $clienteId = (int) ($input['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID de cliente no válido.']);
            exit;
        }

        $nombre = trim($input['nombre'] ?? '');
        $apellidos = trim($input['apellidos'] ?? '') ?: null;
        $email = trim($input['email'] ?? '') ?: null;
        $tipoIdent = trim($input['tipo_identificacion'] ?? '') ?: null;
        $identificacion = trim($input['identificacion'] ?? '') ?: null;
        $tipoCliente = $input['tipo_cliente'] ?? 'INDEPENDIENTE';
        $cobrarSS = !empty($input['cobrar_seguridad_social_mensual']);

        if ($nombre === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Los nombres del cliente son obligatorios.']);
            exit;
        }

        if (!preg_match('/^[\p{L}\s\.\-]+$/u', $nombre)) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Los nombres solo pueden contener letras, espacios, puntos y guiones.'
            ]);
            exit;
        }

        $clienteActual = obtenerClientePorId($clienteId);
        if (!$clienteActual) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Cliente no encontrado.']);
            exit;
        }

        $datos = [
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'email' => $email,
            'tipo_identificacion' => $tipoIdent,
            'identificacion' => $identificacion,
            'tipo_cliente' => $tipoCliente,
            'estado_pago' => $clienteActual['estado_pago'] ?? 'AL_DIA',
            'cobrar_seguridad_social_mensual' => $cobrarSS,
        ];

        $notasCliente = $input['notas_cliente'] ?? [];
        if (!is_array($notasCliente)) {
            $notasCliente = [];
        }

        try {
            $res = guardarCliente($datos, $clienteId);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Error al actualizar el cliente en la base de datos.'
            ]);
            exit;
        }

        if ($res === false) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'error' => 'Ya existe un cliente registrado con ese número de documento.'
            ]);
            exit;
        }

        $pares = [];
        foreach ($notasCliente as $par) {
            $titulo = trim($par['titulo'] ?? '');
            $valor = trim($par['valor'] ?? '');
            if ($titulo !== '' || $valor !== '') {
                $pares[] = ['titulo' => $titulo, 'valor' => $valor];
            }
        }
        try {
            guardarNotasCliente($clienteId, $pares);
        } catch (Throwable $e) {
            // no fallar si las notas fallan
        }

        registrarActividad($conn, 'EDITAR_CLIENTE', 'Editó el cliente "' . ($clienteActual['nombre'] ?? '') . '"');
        echo json_encode(['ok' => true, 'cliente_id' => (int) $clienteId]);
        break;

    case 'agregar_empleados':
        $clienteId = (int) ($input['cliente_id'] ?? 0);
        $empleados = $input['empleados'] ?? [];

        if ($clienteId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Cliente no válido.']);
            exit;
        }

        if (!is_array($empleados)) {
            $empleados = [];
        }

        $validados = [];
        $errores = [];

        foreach ($empleados as $idx => $emp) {
            $nombre = trim($emp['nombre'] ?? '');
            $apellidosEmp = trim($emp['apellidos'] ?? '') ?: null;
            $emailEmp = trim($emp['email'] ?? '') ?: null;
            $tipoDocEmp = trim($emp['tipo_documento'] ?? '') ?: null;
            $numDocEmp = trim($emp['numero_documento'] ?? '') ?: null;
            $cargoEmp = trim($emp['cargo'] ?? '') ?: null;

            if ($nombre === '') {
                // Filas totalmente vacías se ignoran
                continue;
            }

            if (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
                $errores[] = 'Empleado #' . ($idx + 1) . ': El nombre solo puede contener letras y espacios.';
            } elseif (mb_strlen($nombre) < 2) {
                $errores[] = 'Empleado #' . ($idx + 1) . ': El nombre debe tener al menos 2 caracteres.';
            } elseif (mb_strlen($nombre) > 100) {
                $errores[] = 'Empleado #' . ($idx + 1) . ': El nombre no puede exceder 100 caracteres.';
            } else {
                $validados[] = [
                    'cliente_id' => $clienteId,
                    'nombre' => $nombre,
                    'apellidos' => $apellidosEmp,
                    'email' => $emailEmp,
                    'tipo_documento' => $tipoDocEmp,
                    'numero_documento' => $numDocEmp,
                    'cargo' => $cargoEmp,
                    'notas' => $emp['notas'] ?? [],
                ];
            }
        }

        if (!empty($errores)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => implode(' ', $errores)]);
            exit;
        }

        try {
            foreach ($validados as $datosEmp) {
                $notas = $datosEmp['notas'] ?? [];
                unset($datosEmp['notas']);
                $empleadoId = guardarEmpleado($datosEmp, null);
                if ($empleadoId && !empty($notas)) {
                    $pares = [];
                    foreach ($notas as $p) {
                        $titulo = trim($p['titulo'] ?? '');
                        $valor = trim($p['valor'] ?? '');
                        if ($titulo !== '' || $valor !== '') {
                            $pares[] = ['titulo' => $titulo ?: 'Nota', 'valor' => $valor];
                        }
                    }
                    if (!empty($pares)) guardarNotasEmpleado($empleadoId, $pares);
                }
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => 'Error al guardar los empleados en la base de datos.'
            ]);
            exit;
        }

        $cliente = obtenerClientePorId($clienteId);
        $nombreCliente = $cliente['nombre'] ?? '';
        registrarActividad($conn, 'AGREGAR_EMPLEADOS', 'Agregó ' . count($validados) . ' empleado(s) al cliente "' . $nombreCliente . '"');
        echo json_encode(['ok' => true]);
        break;

    case 'actualizar_empleados':
        $clienteId = (int) ($input['cliente_id'] ?? 0);
        $empleados = $input['empleados'] ?? [];

        if ($clienteId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Cliente no válido.']);
            exit;
        }

        if (!is_array($empleados)) {
            $empleados = [];
        }

        $existentes = obtenerEmpleadosPorCliente($clienteId);
        $idsEnviados = [];

        foreach ($empleados as $emp) {
            $empleadoId = !empty($emp['empleado_id']) ? (int) $emp['empleado_id'] : null;
            $nombre = trim($emp['nombre'] ?? '');
            if ($nombre === '') continue;

            $datos = [
                'cliente_id' => $clienteId,
                'nombre' => $nombre,
                'apellidos' => trim($emp['apellidos'] ?? '') ?: null,
                'email' => trim($emp['email'] ?? '') ?: null,
                'tipo_documento' => trim($emp['tipo_documento'] ?? '') ?: null,
                'numero_documento' => trim($emp['numero_documento'] ?? '') ?: null,
                'cargo' => trim($emp['cargo'] ?? '') ?: null,
            ];
            $notas = $emp['notas'] ?? [];
            if (!is_array($notas)) $notas = [];

            if ($empleadoId) {
                $encontrado = false;
                foreach ($existentes as $e) {
                    if ((int) $e['empleado_id'] === $empleadoId) {
                        $encontrado = true;
                        break;
                    }
                }
                if ($encontrado) {
                    guardarEmpleado($datos, $empleadoId);
                    $idsEnviados[] = $empleadoId;
                    $pares = [];
                    foreach ($notas as $p) {
                        $titulo = trim($p['titulo'] ?? '');
                        $valor = trim($p['valor'] ?? '');
                        if ($titulo !== '' || $valor !== '') $pares[] = ['titulo' => $titulo ?: 'Nota', 'valor' => $valor];
                    }
                    guardarNotasEmpleado($empleadoId, $pares);
                }
            } else {
                $nuevoId = guardarEmpleado($datos, null);
                if ($nuevoId) {
                    $idsEnviados[] = $nuevoId;
                    $pares = [];
                    foreach ($notas as $p) {
                        $titulo = trim($p['titulo'] ?? '');
                        $valor = trim($p['valor'] ?? '');
                        if ($titulo !== '' || $valor !== '') $pares[] = ['titulo' => $titulo ?: 'Nota', 'valor' => $valor];
                    }
                    if (!empty($pares)) guardarNotasEmpleado($nuevoId, $pares);
                }
            }
        }

        if (puedeEliminar()) {
            foreach ($existentes as $e) {
                $eid = (int) $e['empleado_id'];
                if (!in_array($eid, $idsEnviados, true)) {
                    eliminarEmpleado($eid, $clienteId);
                }
            }
        }

        $cliente = obtenerClientePorId($clienteId);
        $nombreCliente = $cliente['nombre'] ?? '';
        registrarActividad($conn, 'EDITAR_EMPLEADOS', 'Actualizó empleados del cliente "' . $nombreCliente . '"');
        echo json_encode(['ok' => true]);
        break;

    case 'eliminar':
        $clienteId = (int) ($input['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID no válido']);
            exit;
        }
        if (!puedeEliminar()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sin permiso para eliminar']);
            exit;
        }
        $clienteAntes = obtenerClientePorId($clienteId);
        $nombreCliente = $clienteAntes ? trim(($clienteAntes['nombre'] ?? '') . ' ' . ($clienteAntes['apellidos'] ?? '')) : '';
        try {
            $eliminado = eliminarCliente($clienteId);
        } catch (Exception $e) {
            error_log("SIGED eliminar cliente: " . $e->getMessage());
            $eliminado = false;
        }
        if ($eliminado && $nombreCliente) {
            registrarActividad($conn, 'ELIMINAR_CLIENTE', 'Movió a inactivos al cliente "' . $nombreCliente . '"');
        }
        $_SESSION['cliente_msg'] = $eliminado ? 'Cliente movido a inactivos correctamente' : 'No se pudo inactivar.';
        $_SESSION['cliente_msg_type'] = $eliminado ? 'success' : 'error';
        echo json_encode(['ok' => $eliminado]);
        exit;

    case 'toggle_ss':
        $clienteId = (int) ($input['cliente_id'] ?? 0);
        if ($clienteId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'ID de cliente no válido']);
            exit;
        }
        $cliente = obtenerClientePorId($clienteId);
        if (!$cliente) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Cliente no encontrado']);
            exit;
        }
        $actual = (int) ($cliente['cobrar_seguridad_social_mensual'] ?? 1);
        $nuevo = $actual ? 0 : 1;
        $stmt = $conn->prepare("UPDATE cliente SET cobrar_seguridad_social_mensual = ? WHERE cliente_id = ?");
        $stmt->execute([$nuevo, $clienteId]);
        $accion = $nuevo ? 'activó' : 'desactivó';
        registrarActividad($conn, 'TOGGLE_SS', $accion . ' el cobro de seguridad social para "' . ($cliente['nombre'] ?? '') . '"');
        echo json_encode(['ok' => true, 'cobrar_seguridad_social_mensual' => $nuevo]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
}

