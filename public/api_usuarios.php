<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/usuario.php';
require_once __DIR__ . '/../models/historial_model.php';

requireAuth();

if (!esAdministrador()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Solo el administrador puede gestionar colaboradores']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $usuarios = listarUsuariosCtrl($conn);
    echo json_encode(['ok' => true, 'usuarios' => $usuarios]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}
if (!csrf_validate()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token de sesión inválido. Recargue la página.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

if ($action === 'crear') {
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $nombre = trim($input['nombre'] ?? '');
    $rol = trim($input['rol'] ?? 'COLABORADOR');
    $res = crearUsuarioCtrl($conn, $email, $password, $nombre, $rol);
    if (!$res['ok']) {
        http_response_code(400);
    } else {
        registrarActividad($conn, 'CREAR_USUARIO', 'Agregó al colaborador "' . $nombre . '" con rol ' . $rol);
    }
    echo json_encode($res);
    exit;
}

if ($action === 'eliminar') {
    $usuarioId = (int) ($input['usuario_id'] ?? 0);
    $nombre = '';
    if ($usuarioId > 0) {
        $user = obtenerUsuarioPorId($conn, $usuarioId);
        $nombre = $user ? $user['nombre'] : '';
    }
    $res = eliminarUsuarioCtrl($conn, $usuarioId, $_SESSION['usuario_id']);
    if (!$res['ok']) {
        http_response_code(400);
    } else {
        registrarActividad($conn, 'ELIMINAR_USUARIO', 'Eliminó al colaborador "' . $nombre . '"');
    }
    echo json_encode($res);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
