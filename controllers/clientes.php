<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/cliente_model.php';

// Almacenar referencias a las funciones del modelo
$model_obtenerClientes = 'obtenerClientes';
$model_obtenerClientePorId = 'obtenerClientePorId';
$model_guardarCliente = 'guardarCliente';
$model_eliminarCliente = 'eliminarCliente';
$model_obtenerUltimosClientes = 'obtenerUltimosClientes';
$model_contarClientes = 'contarClientes';
$model_contarClientesPendientes = 'contarClientesPendientes';

function listarClientes($conn, $tipo = null, $pago = null, $busqueda = null) {
    global $model_obtenerClientes;
    return call_user_func($model_obtenerClientes, $conn, $tipo, $pago, $busqueda);
}

function verCliente($conn, $id) {
    global $model_obtenerClientePorId;
    return call_user_func($model_obtenerClientePorId, $conn, $id);
}

function obtenerClientes($tipo = null, $pago = null, $busqueda = null) {
    global $conn, $model_obtenerClientes;
    return call_user_func($model_obtenerClientes, $conn, $tipo, $pago, $busqueda);
}

function obtenerClientePorId($id) {
    global $conn, $model_obtenerClientePorId;
    return call_user_func($model_obtenerClientePorId, $conn, $id);
}

function guardarCliente($datos, $id = null) {
    global $conn, $model_guardarCliente;
    return call_user_func($model_guardarCliente, $conn, $datos, $id);
}

function eliminarCliente($id) {
    global $conn, $model_eliminarCliente;
    return call_user_func($model_eliminarCliente, $conn, $id);
}

function guardarClienteCtrl($conn, $datos, $id = null) {
    global $model_guardarCliente;
    return call_user_func($model_guardarCliente, $conn, $datos, $id);
}

function eliminarClienteCtrl($conn, $id) {
    global $model_eliminarCliente;
    return call_user_func($model_eliminarCliente, $conn, $id);
}

function dashboardUltimosClientes($conn) {
    global $model_obtenerUltimosClientes;
    return call_user_func($model_obtenerUltimosClientes, $conn, 5);
}

function dashboardTotales($conn) {
    global $model_contarClientes, $model_contarClientesPendientes;
    return [
        'total' => call_user_func($model_contarClientes, $conn),
        'pendientes' => call_user_func($model_contarClientesPendientes, $conn)
    ];
}

function contarClientes() {
    global $conn, $model_contarClientes;
    return call_user_func($model_contarClientes, $conn);
}

function contarClientesPendientes() {
    global $conn, $model_contarClientesPendientes;
    return call_user_func($model_contarClientesPendientes, $conn);
}

function obtenerUltimosClientes($limite = 5) {
    global $conn, $model_obtenerUltimosClientes;
    return call_user_func($model_obtenerUltimosClientes, $conn, $limite);
}
