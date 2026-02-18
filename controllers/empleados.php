<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/empleado_model.php';

// Almacenar referencias a las funciones del modelo antes de definir las del controlador
$model_obtenerEmpleadosPorCliente = 'obtenerEmpleadosPorCliente';
$model_guardarEmpleado = 'guardarEmpleado';
$model_eliminarEmpleado = 'eliminarEmpleado';

/**
 * Obtiene los empleados de un cliente.
 */
function obtenerEmpleadosPorCliente($clienteId) {
    global $conn, $model_obtenerEmpleadosPorCliente;
    return call_user_func($model_obtenerEmpleadosPorCliente, $conn, $clienteId);
}

/**
 * Guarda un empleado (crear o actualizar).
 */
function guardarEmpleado($datos, $empleadoId = null) {
    global $conn, $model_guardarEmpleado;
    return call_user_func($model_guardarEmpleado, $conn, $datos, $empleadoId);
}

/**
 * Elimina un empleado por ID (y sus notas).
 */
function eliminarEmpleado($empleadoId, $clienteId) {
    global $conn, $model_eliminarEmpleado;
    return call_user_func($model_eliminarEmpleado, $conn, $empleadoId, $clienteId);
}
