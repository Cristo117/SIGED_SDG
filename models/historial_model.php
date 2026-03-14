<?php

/**
 * Registra una actividad en el historial.
 */
function registrarActividad($conn, $accion, $descripcion, $detalle = null) {
    $usuarioId = (int) ($_SESSION['usuario_id'] ?? 0);
    $usuarioNombre = $_SESSION['nombre'] ?? 'Desconocido';
    $usuarioRol = strtoupper($_SESSION['rol'] ?? 'COLABORADOR');

    $stmt = $conn->prepare(
        "INSERT INTO historial_actividad (usuario_id, usuario_nombre, usuario_rol, accion, descripcion, detalle) 
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    return $stmt->execute([
        $usuarioId,
        $usuarioNombre,
        $usuarioRol,
        $accion,
        $descripcion,
        $detalle,
    ]);
}

/**
 * Lista el historial de actividad (ordenado por más reciente).
 */
function listarHistorial($conn, $limite = 500, $soloColaboradores = false) {
    $sql = "SELECT id, usuario_id, usuario_nombre, usuario_rol, accion, descripcion, detalle, creado_at 
            FROM historial_actividad";
    $params = [];
    if ($soloColaboradores) {
        $sql .= " WHERE usuario_rol = 'COLABORADOR'";
    }
    $sql .= " ORDER BY creado_at DESC LIMIT " . (int) $limite;

    $stmt = $params ? $conn->prepare($sql) : $conn->query($sql);
    if ($params) {
        $stmt->execute($params);
    }
    return $stmt->fetchAll();
}

/**
 * Limpia todo el historial de actividad.
 */
function limpiarHistorial($conn) {
    return $conn->exec("TRUNCATE TABLE historial_actividad");
}
