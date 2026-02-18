<?php
require_once __DIR__ . '/../models/NotificacionModel.php';

function obtenerNotificacionesNoLeidas($conn, $usuarioId) {
    return contarNotificacionesNoLeidas($conn, $usuarioId);
}
