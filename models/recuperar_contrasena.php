<?php

require_once __DIR__ . '/models/usuario_model.php';

function actualizarcontrasenaCtrl($conn, $usuarioId, $passwordNueva) {

    $nuevoHash = password_hash($passwordNueva, PASSWORD_DEFAULT);
    
    return actualizarPasswordUsuario(
        $conn,
        $usuarioId,
        $nuevoHash
    );
}



