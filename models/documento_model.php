<?php

function listarDocumentosCliente($conn, $clienteId) {
    $stmt = $conn->prepare("SELECT documento_id, nombre_tipo, nombre_archivo, nombre_original, extension, creado_at FROM cliente_documento WHERE cliente_id = ? ORDER BY creado_at DESC");
    $stmt->execute([$clienteId]);
    return $stmt->fetchAll();
}

function guardarDocumentoCliente($conn, $clienteId, $nombreTipo, $nombreArchivo, $nombreOriginal, $extension) {
    $stmt = $conn->prepare("INSERT INTO cliente_documento (cliente_id, nombre_tipo, nombre_archivo, nombre_original, extension) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$clienteId, $nombreTipo, $nombreArchivo, $nombreOriginal, $extension]);
}

function obtenerDocumentoPorId($conn, $documentoId) {
    $stmt = $conn->prepare("SELECT d.*, c.nombre as cliente_nombre FROM cliente_documento d JOIN cliente c ON c.cliente_id = d.cliente_id WHERE d.documento_id = ?");
    $stmt->execute([$documentoId]);
    return $stmt->fetch();
}

function eliminarDocumentoCliente($conn, $documentoId) {
    $stmt = $conn->prepare("DELETE FROM cliente_documento WHERE documento_id = ?");
    return $stmt->execute([$documentoId]);
}
