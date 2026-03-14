<?php

/**
 * Protección contra ataques CSRF.
 * Genera y valida tokens para formularios y peticiones POST.
 */

require_once __DIR__ . '/session_init.php';

/**
 * Genera un token CSRF y lo guarda en sesión.
 * @return string Token para incluir en formularios/headers
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Obtiene el campo HTML input hidden para incluir en formularios.
 * @return string <input type="hidden" name="csrf_token" value="...">
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Valida el token CSRF de la petición actual.
 * Para formularios POST: busca en $_POST['csrf_token']
 * Para APIs JSON: busca en header X-CSRF-Token o en body csrf_token
 * @return bool true si es válido
 */
function csrf_validate() {
    $token = null;
    if (!empty($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input) && !empty($input['csrf_token'])) {
            $token = $input['csrf_token'];
        }
    }
    return $token !== null && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
