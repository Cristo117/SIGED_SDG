<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/documento.php';
require_once __DIR__ . '/../models/historial_model.php';

requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $clienteId = (int) ($_GET['cliente_id'] ?? 0);
    if ($clienteId <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'cliente_id requerido']);
        exit;
    }
    $cliente = obtenerClientePorId($clienteId);
    if (!$cliente) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Cliente no encontrado']);
        exit;
    }
    $documentos = listarDocumentosClienteCtrl($conn, $clienteId);
    echo json_encode(['ok' => true, 'documentos' => $documentos]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// POST: subir o eliminar
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? $_POST['action'] ?? '';

if ($action === 'eliminar') {
    if (!csrf_validate()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token inválido']);
        exit;
    }
    $documentoId = (int) ($input['documento_id'] ?? 0);
    $doc = obtenerDocumentoPorIdCtrl($conn, $documentoId);
    if (!$doc) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Documento no encontrado']);
        exit;
    }
    $dirBase = __DIR__ . '/../uploads/documentos/';
    $rutaArchivo = $dirBase . $doc['nombre_archivo'];
    eliminarDocumentoClienteCtrl($conn, $documentoId);
    if (file_exists($rutaArchivo)) {
        unlink($rutaArchivo);
    }
    registrarActividad($conn, 'ELIMINAR_DOCUMENTO', 'Eliminó documento "' . $doc['nombre_tipo'] . '" del cliente "' . ($doc['cliente_nombre'] ?? '') . '"');
    echo json_encode(['ok' => true]);
    exit;
}

// Subir archivo (multipart/form-data)
if (!csrf_validate()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token inválido. Recargue la página.']);
    exit;
}

$clienteId = (int) ($_POST['cliente_id'] ?? 0);
$nombreTipo = trim($_POST['nombre_tipo'] ?? '');
$archivo = $_FILES['archivo'] ?? null;

if ($clienteId <= 0 || empty($nombreTipo) || !$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Complete cliente_id, nombre del documento (ej: Cédula, Registro) y seleccione un archivo.']);
    exit;
}

$cliente = obtenerClientePorId($clienteId);
if (!$cliente) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Cliente no encontrado']);
    exit;
}

$permitidos = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
$ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $permitidos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Solo se permiten: PDF, JPG, PNG, DOC, DOCX']);
    exit;
}

$maxSize = 10 * 1024 * 1024; // 10 MB
if ($archivo['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo no puede superar 10 MB']);
    exit;
}

$dirBase = __DIR__ . '/../uploads/documentos/';
if (!is_dir($dirBase)) {
    mkdir($dirBase, 0755, true);
}

$nombreOriginal = basename($archivo['name']);
$nombreArchivo = 'cliente_' . $clienteId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$rutaDestino = $dirBase . $nombreArchivo;

if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al guardar el archivo']);
    exit;
}

guardarDocumentoClienteCtrl($conn, $clienteId, $nombreTipo, $nombreArchivo, $nombreOriginal, $ext);
registrarActividad($conn, 'SUBIR_DOCUMENTO', 'Subió documento "' . $nombreTipo . '" para el cliente "' . ($cliente['nombre'] ?? '') . '"');

echo json_encode([
    'ok' => true,
    'documento' => [
        'documento_id' => (int) $conn->lastInsertId(),
        'nombre_tipo' => $nombreTipo,
        'nombre_original' => $nombreOriginal,
        'extension' => $ext,
        'creado_at' => date('Y-m-d H:i:s')
    ]
]);
