<?php

function contarNotificacionesNoLeidas($conn, $usuarioId) {

    $stmt = $conn->prepare(
        "SELECT COUNT(*) 
         FROM notificacion 
         WHERE usuario_id = ? AND leida = 0"
    );

    $stmt->execute([$usuarioId]);
    return (int) $stmt->fetchColumn();
}
