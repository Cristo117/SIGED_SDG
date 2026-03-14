<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/info_adicional.php';
require_once __DIR__ . '/../controllers/empleados.php';
require_once __DIR__ . '/../controllers/documento.php';

requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID de cliente no válido.']);
    exit;
}

$cliente = obtenerClientePorId($id);
if (!$cliente) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Cliente no encontrado.']);
    exit;
}

// Separar nombres/apellidos como en cliente_detalle.php
$clienteNombres = trim($cliente['nombre'] ?? '');
$clienteApellidos = trim($cliente['apellidos'] ?? '');
if ($clienteApellidos === '' && strpos($clienteNombres, ' ') !== false) {
    $partes = preg_split('/\s+/', $clienteNombres);
    if (is_array($partes) && count($partes) > 1) {
        $clienteApellidos = array_pop($partes);
        $clienteNombres = implode(' ', $partes);
    }
}

$empleados = obtenerEmpleadosPorCliente($id);
$notasCliente = obtenerNotasCliente($id);
$documentos = listarDocumentosClienteCtrl($conn, $id);
$notasEmpleados = [];
foreach ($empleados as $e) {
    $notasEmpleados[$e['empleado_id']] = obtenerNotasEmpleado($e['empleado_id']);
}

// Armar payload empleado similar al detalle
$empleadosOut = [];
foreach ($empleados as $e) {
    $nombresEmp = trim($e['nombre'] ?? '');
    $apellidosEmp = trim($e['apellidos'] ?? '');
    if ($apellidosEmp === '' && strpos($nombresEmp, ' ') !== false) {
        $partesEmp = preg_split('/\s+/', $nombresEmp);
        if (is_array($partesEmp) && count($partesEmp) > 1) {
            $apellidosEmp = array_pop($partesEmp);
            $nombresEmp = implode(' ', $partesEmp);
        }
    }
    $empleadosOut[] = [
        'empleado_id' => (int) $e['empleado_id'],
        'nombres' => $nombresEmp ?: ($e['nombre'] ?? ''),
        'apellidos' => $apellidosEmp ?: '',
        'tipo_documento' => trim($e['tipo_documento'] ?? ''),
        'numero_documento' => trim($e['numero_documento'] ?? ''),
        'email' => $e['email'] ?? '',
        'cargo' => $e['cargo'] ?? '',
        'notas' => $notasEmpleados[$e['empleado_id']] ?? [],
    ];
}

echo json_encode([
    'ok' => true,
    'cliente' => [
        'cliente_id' => (int) $cliente['cliente_id'],
        'nombres' => $clienteNombres ?: ($cliente['nombre'] ?? ''),
        'apellidos' => $clienteApellidos ?: '',
        'email' => $cliente['email'] ?? '',
        'tipo_cliente' => $cliente['tipo_cliente'] ?? '',
        'tipo_identificacion' => $cliente['tipo_identificacion'] ?? '',
        'identificacion' => $cliente['identificacion'] ?? '',
        'estado_pago' => $cliente['estado_pago'] ?? '',
        'cobrar_seguridad_social_mensual' => !empty($cliente['cobrar_seguridad_social_mensual']),
        'creado_at' => $cliente['creado_at'] ?? null,
        'notas' => $notasCliente,
    ],
    'empleados' => $empleadosOut,
    'documentos' => $documentos ?: [],
]);

