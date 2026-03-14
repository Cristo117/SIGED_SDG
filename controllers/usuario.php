<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/usuario_model.php';

function listarUsuariosCtrl($conn) {
    return listarUsuarios($conn);
}

function crearUsuarioCtrl($conn, $email, $password, $nombre, $rol) {
    $email = trim($email);
    $nombre = trim($nombre);
    if (empty($email) || empty($nombre) || empty($password)) {
        return ['ok' => false, 'error' => 'Complete todos los campos'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Correo no válido'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres'];
    }
    if (!in_array($rol, ['ADMINISTRADOR', 'COLABORADOR'])) {
        return ['ok' => false, 'error' => 'Rol no válido'];
    }
    if (emailEnUso($conn, $email)) {
        return ['ok' => false, 'error' => 'Ese correo ya está registrado'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        crearUsuario($conn, $email, $hash, $nombre, $rol);
        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Error al crear usuario'];
    }
}

function eliminarUsuarioCtrl($conn, $usuarioId, $usuarioActualId) {
    $usuarioId = (int) $usuarioId;
    if ($usuarioId <= 0) {
        return ['ok' => false, 'error' => 'Usuario no válido'];
    }
    if ($usuarioId === $usuarioActualId) {
        return ['ok' => false, 'error' => 'No puedes eliminar tu propia cuenta'];
    }
    $user = obtenerUsuarioPorId($conn, $usuarioId);
    if (!$user) {
        return ['ok' => false, 'error' => 'Usuario no encontrado'];
    }
    $esAdmin = strtoupper($user['rol'] ?? 'ADMINISTRADOR') === 'ADMINISTRADOR';
    if ($esAdmin && contarAdminsActivos($conn) <= 1) {
        return ['ok' => false, 'error' => 'Debe quedar al menos un administrador activo'];
    }
    try {
        desactivarUsuario($conn, $usuarioId);
        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Error al eliminar usuario'];
    }
}
