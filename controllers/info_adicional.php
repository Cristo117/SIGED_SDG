<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/info_adicional_model.php';

// Almacenar referencias a las funciones del modelo
$model_obtenerNotasCliente = 'obtenerNotasCliente';
$model_obtenerNotasEmpleado = 'obtenerNotasEmpleado';
$model_guardarNotasCliente = 'guardarNotasCliente';
$model_guardarNotasEmpleado = 'guardarNotasEmpleado';

/**
 * Obtiene las notas (título, valor) de un cliente.
 */
function obtenerNotasCliente($clienteId) {
    global $conn, $model_obtenerNotasCliente;
    return call_user_func($model_obtenerNotasCliente, $conn, $clienteId);
}

/**
 * Obtiene las notas de un empleado.
 */
function obtenerNotasEmpleado($empleadoId) {
    global $conn, $model_obtenerNotasEmpleado;
    return call_user_func($model_obtenerNotasEmpleado, $conn, $empleadoId);
}

/**
 * Guarda las notas de un cliente. Reemplaza todas las existentes.
 */
function guardarNotasCliente($clienteId, $pares) {
    global $conn, $model_guardarNotasCliente;
    return call_user_func($model_guardarNotasCliente, $conn, $clienteId, $pares);
}

/**
 * Guarda las notas de un empleado. Reemplaza todas las existentes.
 */
function guardarNotasEmpleado($empleadoId, $pares) {
    global $conn, $model_guardarNotasEmpleado;
    return call_user_func($model_guardarNotasEmpleado, $conn, $empleadoId, $pares);
}
