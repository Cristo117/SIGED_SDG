<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/proceso_model.php';
require_once __DIR__ . '/../models/configuracion_model.php';

// Almacenar referencias a las funciones del modelo
$model_asegurarColumnaEstadoPagoProceso = 'proceso_model_asegurarColumnaEstadoPagoProceso';
$model_obtenerValorAfiliacion = 'configuracion_model_obtenerValorAfiliacion';
$model_obtenerEstadisticasReportes = 'proceso_model_obtenerEstadisticasReportes';
$model_obtenerTopClientes = 'proceso_model_obtenerTopClientes';
$model_obtenerIngresosPorTipo = 'proceso_model_obtenerIngresosPorTipo';
$model_obtenerProcesosActivos = 'proceso_model_obtenerProcesosActivos';
$model_obtenerConfiguracionValores = 'proceso_model_obtenerConfiguracionValores';
$model_guardarValorAfiliacion = 'configuracion_model_guardarValorAfiliacion';
$model_asegurarConfiguracionInicial = 'configuracion_model_asegurarConfiguracionInicial';
$model_agregarProceso = 'proceso_model_agregarProceso';
$model_actualizarProceso = 'proceso_model_actualizarProceso';
$model_eliminarProceso = 'proceso_model_eliminarProceso';
$model_obtenerClientesParaAsignar = 'proceso_model_obtenerClientesParaAsignar';
$model_asignarProcesoACliente = 'proceso_model_asignarProcesoACliente';
$model_obtenerProcesosPorCliente = 'proceso_model_obtenerProcesosPorCliente';
$model_obtenerNombreCliente = 'proceso_model_obtenerNombreCliente';
$model_obtenerEstadoPagoCliente = 'proceso_model_obtenerEstadoPagoCliente';
$model_toggleEstadoPagoProcesoCliente = 'proceso_model_toggleEstadoPagoProcesoCliente';
$model_contarProcesosActivos = 'proceso_model_contarProcesosActivos';
$model_obtenerHistorialPagos = 'proceso_model_obtenerHistorialPagos';
$model_contarHistorialPagos = 'proceso_model_contarHistorialPagos';
$model_eliminarProcesosCliente = 'proceso_model_eliminarProcesosCliente';

/**
 * Asegura que proceso_cliente tenga la columna estado_pago.
 */
function asegurarColumnaEstadoPagoProceso() {
    global $conn, $model_asegurarColumnaEstadoPagoProceso;
    return call_user_func($model_asegurarColumnaEstadoPagoProceso, $conn);
}

/**
 * Obtiene el valor de afiliación activo.
 */
function obtenerValorAfiliacion() {
    global $conn, $model_obtenerValorAfiliacion;
    return call_user_func($model_obtenerValorAfiliacion, $conn);
}

/**
 * Obtiene estadísticas para el dashboard de reportes.
 */
function obtenerEstadisticasReportes() {
    global $conn, $model_obtenerEstadisticasReportes;
    return call_user_func($model_obtenerEstadisticasReportes, $conn);
}

/**
 * Top 5 clientes por facturación.
 */
function obtenerTopClientes($limite = 5) {
    global $conn, $model_obtenerTopClientes;
    return call_user_func($model_obtenerTopClientes, $conn, $limite);
}

/**
 * Ingresos por tipo de cliente.
 */
function obtenerIngresosPorTipo() {
    global $conn, $model_obtenerIngresosPorTipo;
    return call_user_func($model_obtenerIngresosPorTipo, $conn);
}

/**
 * Obtiene todos los procesos activos.
 */
function obtenerProcesosActivos() {
    global $conn, $model_obtenerProcesosActivos;
    return call_user_func($model_obtenerProcesosActivos, $conn);
}

/**
 * Obtiene la configuración completa (valor afiliación + procesos).
 */
function obtenerConfiguracionValores() {
    global $conn, $model_obtenerConfiguracionValores;
    return call_user_func($model_obtenerConfiguracionValores, $conn);
}

/**
 * Guarda el valor de afiliación en la tabla configuracion.
 * Desactiva la config anterior y crea una nueva.
 */
function guardarValorAfiliacion($valor) {
    global $conn, $model_guardarValorAfiliacion;
    return call_user_func($model_guardarValorAfiliacion, $conn, $valor);
}

/**
 * Inserta la primera fila en configuracion si no existe ninguna.
 */
function asegurarConfiguracionInicial() {
    global $conn, $model_asegurarConfiguracionInicial;
    return call_user_func($model_asegurarConfiguracionInicial, $conn);
}

/**
 * Agrega un nuevo proceso.
 */
function agregarProceso($nombre, $valor) {
    global $conn, $model_agregarProceso;
    return call_user_func($model_agregarProceso, $conn, $nombre, $valor);
}

/**
 * Actualiza un proceso existente.
 */
function actualizarProceso($id, $nombre, $valor) {
    global $conn, $model_actualizarProceso;
    return call_user_func($model_actualizarProceso, $conn, $id, $nombre, $valor);
}

/**
 * Elimina (soft delete) un proceso poniendo activo = 0.
 */
function eliminarProceso($id) {
    global $conn, $model_eliminarProceso;
    return call_user_func($model_eliminarProceso, $conn, $id);
}

/**
 * Obtiene clientes para dropdown (id, nombre, estado_pago).
 */
function obtenerClientesParaAsignar() {
    global $conn, $model_obtenerClientesParaAsignar;
    return call_user_func($model_obtenerClientesParaAsignar, $conn);
}

/**
 * Asigna un proceso a un cliente.
 */
function asignarProcesoACliente($clienteId, $procesoId, $empleadoId = null) {
    global $conn, $model_asignarProcesoACliente;
    return call_user_func($model_asignarProcesoACliente, $conn, $clienteId, $procesoId, $empleadoId);
}

/**
 * Obtiene los procesos asignados a un cliente (estado ACTIVO).
 */
function obtenerProcesosPorCliente($clienteId) {
    global $conn, $model_obtenerProcesosPorCliente;
    return call_user_func($model_obtenerProcesosPorCliente, $conn, $clienteId);
}

/**
 * Obtiene nombre del cliente por ID.
 */
function obtenerNombreCliente($clienteId) {
    global $conn, $model_obtenerNombreCliente;
    return call_user_func($model_obtenerNombreCliente, $conn, $clienteId);
}

/**
 * Obtiene el estado de pago del cliente calculado desde sus procesos.
 * Si al menos un proceso está pendiente, el cliente está pendiente.
 */
function obtenerEstadoPagoCliente($clienteId) {
    global $conn, $model_obtenerEstadoPagoCliente;
    return call_user_func($model_obtenerEstadoPagoCliente, $conn, $clienteId);
}

/**
 * Alterna el estado de pago de un proceso (PENDIENTE <-> AL_DIA) y actualiza el del cliente.
 */
function toggleEstadoPagoProcesoCliente($procesoClienteId) {
    global $conn, $model_toggleEstadoPagoProcesoCliente;
    return call_user_func($model_toggleEstadoPagoProcesoCliente, $conn, $procesoClienteId);
}

function contarProcesosActivos() {
    global $conn, $model_contarProcesosActivos;
    return call_user_func($model_contarProcesosActivos, $conn);
}

function generarCobrosMensualesSeguridadSocial() {
    global $conn;
    return proceso_model_generarCobrosMensualesSeguridadSocial($conn);
}

/**
 * Obtiene el historial de pagos (clientes al día).
 */
function obtenerHistorialPagos($limite = 500, $offset = 0) {
    global $conn, $model_obtenerHistorialPagos;
    return call_user_func($model_obtenerHistorialPagos, $conn, $limite, $offset);
}

/**
 * Cuenta los registros del historial de pagos.
 */
function contarHistorialPagos() {
    global $conn, $model_contarHistorialPagos;
    return call_user_func($model_contarHistorialPagos, $conn);
}

/**
 * Elimina todos los procesos de un cliente y reinicia su contabilidad.
 */
function eliminarProcesosCliente($clienteId) {
    global $conn, $model_eliminarProcesosCliente;
    return call_user_func($model_eliminarProcesosCliente, $conn, $clienteId);
}
