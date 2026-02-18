<?php

/**
 * Obtiene los empleados de un cliente.
 */
function obtenerEmpleadosPorCliente($conn, $clienteId) {
    $stmt = $conn->prepare("SELECT * FROM empleado WHERE cliente_id = ? ORDER BY empleado_id");
    $stmt->execute([$clienteId]);
    return $stmt->fetchAll();
}

/**
 * Guarda un empleado (crear o actualizar).
 */
function guardarEmpleado($conn, $datos, $empleadoId = null) {
    if ($empleadoId) {
        $stmt = $conn->prepare("UPDATE empleado SET nombre=?, email=?, tipo_documento=?, numero_documento=?, cargo=? WHERE empleado_id=? AND cliente_id=?");
        $stmt->execute([
            $datos['nombre'],
            $datos['email'] ?? null,
            $datos['tipo_documento'] ?? null,
            $datos['numero_documento'] ?? null,
            $datos['cargo'] ?? null,
            $empleadoId,
            $datos['cliente_id']
        ]);
        return $empleadoId;
    } else {
        $stmt = $conn->prepare("INSERT INTO empleado (cliente_id, nombre, email, tipo_documento, numero_documento, cargo) VALUES (?,?,?,?,?,?)");
        $stmt->execute([
            $datos['cliente_id'],
            $datos['nombre'],
            $datos['email'] ?? null,
            $datos['tipo_documento'] ?? null,
            $datos['numero_documento'] ?? null,
            $datos['cargo'] ?? null
        ]);
        return (int) $conn->lastInsertId();
    }
}

/**
 * Elimina un empleado por ID (y sus notas).
 */
function eliminarEmpleado($conn, $empleadoId, $clienteId) {
    $conn->prepare("DELETE FROM info_adicional WHERE empleado_id = ?")->execute([$empleadoId]);
    $stmt = $conn->prepare("DELETE FROM empleado WHERE empleado_id = ? AND cliente_id = ?");
    return $stmt->execute([$empleadoId, $clienteId]);
}
