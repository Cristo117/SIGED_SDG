<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/documento_model.php';

requireAuth();

$documentoId = (int) ($_GET['id'] ?? 0);
if ($documentoId <= 0) {
    http_response_code(400);
    exit;
}

$doc = obtenerDocumentoPorId($conn, $documentoId);
if (!$doc) {
    http_response_code(404);
    exit;
}

$dirBase = __DIR__ . '/../uploads/documentos/';
$rutaArchivo = $dirBase . $doc['nombre_archivo'];

if (!file_exists($rutaArchivo)) {
    http_response_code(404);
    exit;
}

$nombreDescarga = preg_replace('/[^a-zA-Z0-9._-]/', '_', $doc['nombre_tipo']) . '_' . $doc['nombre_original'];

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
header('Content-Length: ' . filesize($rutaArchivo));
readfile($rutaArchivo);
exit;
