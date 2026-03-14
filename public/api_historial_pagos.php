<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/reportes.php';

try {
    requireAuth();

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $limite = (int) ($_GET['limite'] ?? 500);
    $offset = (int) ($_GET['offset'] ?? 0);

    $historial = obtenerHistorialPagos($limite, $offset);
    $total = contarHistorialPagos();

    echo json_encode([
        'ok' => true,
        'historial' => $historial,
        'total' => $total
    ]);
} catch (Throwable $e) {
    error_log("SIGED api_historial_pagos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'Error al cargar el historial'
    ]);
}
