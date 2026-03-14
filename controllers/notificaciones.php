<?php
require_once __DIR__ . '/../models/notificacione_model.php';

function obtenerNotificacionesNoLeidas($conn, $usuarioId) {
    return contarNotificacionesNoLeidas($conn, $usuarioId);
}
