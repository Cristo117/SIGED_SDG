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
    $stmt = $conn->prepare("SELECT usuario_id, username, nombre, email, password_hash, COALESCE(rol, 'ADMINISTRADOR') as rol FROM usuario_admin WHERE username = ? AND activo = 1");
    $stmt->execute([$username]);
    return $stmt->fetch();
}

/**
 * Obtiene un usuario por email para autenticación.
 */
function obtenerUsuarioPorEmail($conn, $email) {
    $stmt = $conn->prepare("SELECT usuario_id, username, nombre, email, password_hash, COALESCE(rol, 'ADMINISTRADOR') as rol FROM usuario_admin WHERE email = ? AND activo = 1");
    $stmt->execute([trim($email)]);
    return $stmt->fetch();
}

/**
 * Obtiene un usuario por ID.
 */
function obtenerUsuarioPorId($conn, $usuarioId) {
    $stmt = $conn->prepare("SELECT usuario_id, nombre, email, COALESCE(rol, 'ADMINISTRADOR') as rol, activo FROM usuario_admin WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    return $stmt->fetch();
}

/**
 * Lista todos los usuarios (para administrador).
 */
function listarUsuarios($conn) {
    $stmt = $conn->query("SELECT usuario_id, username, nombre, email, COALESCE(rol, 'ADMINISTRADOR') as rol, activo, creado_at FROM usuario_admin ORDER BY nombre");
    return $stmt->fetchAll();
}

/**
 * Crea un nuevo usuario (colaborador o administrador).
 */
function crearUsuario($conn, $email, $passwordHash, $nombre, $rol = 'COLABORADOR') {
    $username = str_replace(['@', '.'], ['_', '_'], $email);
    $stmt = $conn->prepare("INSERT INTO usuario_admin (username, email, password_hash, nombre, rol, activo) VALUES (?, ?, ?, ?, ?, 1)");
    return $stmt->execute([$username, trim($email), $passwordHash, $nombre, $rol]);
}

/**
 * Desactiva un usuario (soft delete). El usuario no podrá iniciar sesión.
 */
function desactivarUsuario($conn, $usuarioId) {
    $stmt = $conn->prepare("UPDATE usuario_admin SET activo = 0 WHERE usuario_id = ?");
    return $stmt->execute([$usuarioId]);
}

/**
 * Cuenta usuarios administradores activos.
 */
function contarAdminsActivos($conn) {
    $stmt = $conn->query("SELECT COUNT(*) FROM usuario_admin WHERE activo = 1 AND COALESCE(rol, 'ADMINISTRADOR') = 'ADMINISTRADOR'");
    return (int) $stmt->fetchColumn();
}

/**
 * Verifica si el email ya está en uso.
 */
function emailEnUso($conn, $email, $excluirId = null) {
    $sql = "SELECT COUNT(*) FROM usuario_admin WHERE email = ?";
    $params = [trim($email)];
    if ($excluirId) {
        $sql .= " AND usuario_id != ?";
        $params[] = $excluirId;
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}
