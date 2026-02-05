<?php

require_once __DIR__ . '/../config/db.php';

/**
 * Obtiene las notas (título, valor) de un cliente.
 */
function obtenerNotasCliente($clienteId) {
    global $conn;
    $stmt = $conn->prepare("SELECT info_id, etiqueta as titulo, valor FROM info_adicional WHERE cliente_id = ? AND empleado_id IS NULL ORDER BY info_id");
    $stmt->execute([$clienteId]);
    return $stmt->fetchAll();
}

/**
 * Obtiene las notas de un empleado.
 */
function obtenerNotasEmpleado($empleadoId) {
    global $conn;
    $stmt = $conn->prepare("SELECT info_id, etiqueta as titulo, valor FROM info_adicional WHERE empleado_id = ? ORDER BY info_id");
    $stmt->execute([$empleadoId]);
    return $stmt->fetchAll();
}

/**
 * Guarda las notas de un cliente. Reemplaza todas las existentes.
 */
function guardarNotasCliente($clienteId, $pares) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM info_adicional WHERE cliente_id = ? AND empleado_id IS NULL");
    $stmt->execute([$clienteId]);
    foreach ($pares as $p) {
        $titulo = trim($p['titulo'] ?? '');
        $valor = trim($p['valor'] ?? '');
        if ($titulo === '' && $valor === '') continue;
        if ($titulo === '') $titulo = 'Nota';
        $ins = $conn->prepare("INSERT INTO info_adicional (cliente_id, empleado_id, etiqueta, valor) VALUES (?, NULL, ?, ?)");
        $ins->execute([$clienteId, $titulo, $valor]);
    }
}

/**
 * Guarda las notas de un empleado. Reemplaza todas las existentes.
 */
function guardarNotasEmpleado($empleadoId, $pares) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM info_adicional WHERE empleado_id = ?");
    $stmt->execute([$empleadoId]);
    foreach ($pares as $p) {
        $titulo = trim($p['titulo'] ?? '');
        $valor = trim($p['valor'] ?? '');
        if ($titulo === '' && $valor === '') continue;
        if ($titulo === '') $titulo = 'Nota';
        $ins = $conn->prepare("INSERT INTO info_adicional (cliente_id, empleado_id, etiqueta, valor) VALUES (NULL, ?, ?, ?)");
        $ins->execute([$empleadoId, $titulo, $valor]);
    }
}
