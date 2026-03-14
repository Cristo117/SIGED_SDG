<?php

require_once __DIR__ . '/../models/documento_model.php';

function listarDocumentosClienteCtrl($conn, $clienteId) {
    return listarDocumentosCliente($conn, $clienteId);
}

function guardarDocumentoClienteCtrl($conn, $clienteId, $nombreTipo, $nombreArchivo, $nombreOriginal, $extension) {
    return guardarDocumentoCliente($conn, $clienteId, $nombreTipo, $nombreArchivo, $nombreOriginal, $extension);
}

function eliminarDocumentoClienteCtrl($conn, $documentoId) {
    return eliminarDocumentoCliente($conn, $documentoId);
}

function obtenerDocumentoPorIdCtrl($conn, $documentoId) {
    return obtenerDocumentoPorId($conn, $documentoId);
}
