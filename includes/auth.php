<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica si el usuario está autenticado.
 * Redirige a login si no hay sesión activa.
 */
function requireAuth() {
    if (empty($_SESSION['usuario_id']) || empty($_SESSION['username'])) {
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
        'email' => $_SESSION['email'] ?? ''
    ];
}
