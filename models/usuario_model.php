<?php

function actualizarPerfilUsuario($conn, $usuarioId, $nombre, $email) {

    $stmt = $conn->prepare(
        "UPDATE usuario_admin 
         SET nombre = ?, email = ? 
         WHERE usuario_id = ?"
    );

    return $stmt->execute([$nombre, $email, $usuarioId]);
}


function obtenerHashPassword($conn, $usuarioId) {

    $stmt = $conn->prepare(
        "SELECT password_hash 
         FROM usuario_admin 
         WHERE usuario_id = ?"
    );

    $stmt->execute([$usuarioId]);
    return $stmt->fetchColumn();
}


function actualizarPasswordUsuario($conn, $usuarioId, $hash) {

    $stmt = $conn->prepare(
        "UPDATE usuario_admin 
         SET password_hash = ? 
         WHERE usuario_id = ?"
    );

    return $stmt->execute([$hash, $usuarioId]);
}

/**
 * Verifica si existe al menos un usuario admin.
 */
function existeUsuarioAdmin($conn) {
    $stmt = $conn->query("SELECT COUNT(*) as n FROM usuario_admin");
    $row = $stmt->fetch();
    return (int) $row['n'] > 0;
}

/**
 * Crea un nuevo usuario administrador.
 */
function crearUsuarioAdmin($conn, $username, $email, $passwordHash, $nombre) {
    $stmt = $conn->prepare("INSERT INTO usuario_admin (username, email, password_hash, nombre, activo) VALUES (?, ?, ?, ?, 1)");
    return $stmt->execute([$username, $email, $passwordHash, $nombre]);
}

/**
 * Obtiene un usuario por username para autenticación.
 */
function obtenerUsuarioPorUsername($conn, $username) {
    $stmt = $conn->prepare("SELECT usuario_id, username, nombre, email, password_hash FROM usuario_admin WHERE username = ? AND activo = 1");
    $stmt->execute([$username]);
    return $stmt->fetch();
}
