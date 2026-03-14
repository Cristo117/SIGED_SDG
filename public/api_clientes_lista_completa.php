<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/empleados.php';
require_once __DIR__ . '/../controllers/info_adicional.php';

requireAuth();

$clientes = obtenerClientes(null, null, null);
$resultado = [];

foreach ($clientes as $c) {
    $empleados = obtenerEmpleadosPorCliente($c['cliente_id']);
    $notasCliente = obtenerNotasCliente($c['cliente_id']);
    $notasEmpleados = [];
    foreach ($empleados as $e) {
        $notasEmpleados[$e['empleado_id']] = obtenerNotasEmpleado($e['empleado_id']);
    }

    $nombresCl = trim($c['nombre'] ?? '');
    $apellidosCl = trim($c['apellidos'] ?? '');
    if ($apellidosCl === '' && strpos($nombresCl, ' ') !== false) {
        $partes = preg_split('/\s+/', $nombresCl);
        if (count($partes) > 1) {
            $apellidosCl = array_pop($partes);
            $nombresCl = implode(' ', $partes);
        }
    }

    $empleadosOut = [];
    foreach ($empleados as $e) {
        $nombresEmp = trim($e['nombre'] ?? '');
        $apellidosEmp = trim($e['apellidos'] ?? '');
        if ($apellidosEmp === '' && strpos($nombresEmp, ' ') !== false) {
            $partesEmp = preg_split('/\s+/', $nombresEmp);
            if (count($partesEmp) > 1) {
                $apellidosEmp = array_pop($partesEmp);
                $nombresEmp = implode(' ', $partesEmp);
            }
        }
        $notasEmp = $notasEmpleados[$e['empleado_id']] ?? [];
        $empleadosOut[] = [
            'empleado_id' => (int) $e['empleado_id'],
            'nombres' => $nombresEmp ?: ($e['nombre'] ?? ''),
            'apellidos' => $apellidosEmp ?: '',
            'tipo_documento' => trim($e['tipo_documento'] ?? ''),
            'numero_documento' => trim($e['numero_documento'] ?? ''),
            'email' => $e['email'] ?? '',
            'cargo' => $e['cargo'] ?? '',
            'notas' => $notasEmp,
        ];
    }

    $resultado[] = [
        'cliente_id' => (int) $c['cliente_id'],
        'nombre' => $nombresCl . ($apellidosCl ? ' ' . $apellidosCl : '') ?: $c['nombre'],
        'tipo_cliente' => $c['tipo_cliente'] ?? '',
        'tipo_documento' => trim($c['tipo_identificacion'] ?? ''),
        'numero_documento' => trim($c['identificacion'] ?? ''),
        'email' => $c['email'] ?? '',
        'estado_pago' => $c['estado_pago'] ?? '',
        'notas' => $notasCliente,
        'empleados' => $empleadosOut,
    ];
}

echo json_encode(['ok' => true, 'clientes' => $resultado]);
