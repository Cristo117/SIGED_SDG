<?php

require_once __DIR__ . '/session_init.php';

/**
 * Verifica si el usuario está autenticado.
 * Redirige a login si no hay sesión activa.
 */
function requireAuth() {
    if (empty($_SESSION['usuario_id']) || (empty($_SESSION['email']) && empty($_SESSION['username']))) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Obtiene el usuario actual de la sesión.
 */
function getUsuarioActual() {
    return [
        'id' => $_SESSION['usuario_id'] ?? null,
        'username' => $_SESSION['username'] ?? 'Admin',
        'nombre' => $_SESSION['nombre'] ?? 'Admin',
        'email' => $_SESSION['email'] ?? '',
        'rol' => $_SESSION['rol'] ?? 'ADMINISTRADOR'
    ];
}

/**
 * Indica si el usuario puede eliminar (solo administrador).
 * Colaborador puede agregar y modificar pero no eliminar.
 */
function puedeEliminar() {
    $rol = strtoupper($_SESSION['rol'] ?? 'ADMINISTRADOR');
    return $rol === 'ADMINISTRADOR';
}

/**
 * Indica si el usuario es administrador (puede gestionar colaboradores).
 */
function esAdministrador() {
    return strtoupper($_SESSION['rol'] ?? 'ADMINISTRADOR') === 'ADMINISTRADOR';
}
