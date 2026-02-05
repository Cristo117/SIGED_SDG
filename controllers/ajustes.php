<?php

require_once __DIR__ . '/../config/db.php';

/**
 * Actualiza el perfil del usuario.
 */
function actualizarPerfil($usuarioId, $nombre, $email, $telefono = null) {
    global $conn;
    $stmt = $conn->prepare("UPDATE usuario_admin SET nombre = ?, email = ? WHERE usuario_id = ?");
    return $stmt->execute([$nombre, $email, $usuarioId]);
}

/**
 * Cambia la contraseña del usuario.
 */
function cambiarPassword($usuarioId, $passwordActual, $passwordNueva) {
    global $conn;
    $stmt = $conn->prepare("SELECT password_hash FROM usuario_admin WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($passwordActual, $row['password_hash'])) {
        return false;
    }
    $hash = password_hash($passwordNueva, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuario_admin SET password_hash = ? WHERE usuario_id = ?");
    return $stmt->execute([$hash, $usuarioId]);
}
