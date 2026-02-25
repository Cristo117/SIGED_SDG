<?php
require_once __DIR__ . '/../models/UsuarioModel.php';

function actualizarPerfilCtrl($conn, $usuarioId, $nombre, $email) {

    return actualizarPerfilUsuario(
        $conn,
        $usuarioId,
        $nombre,
        $email
    );
}


function cambiarPasswordCtrl($conn, $usuarioId, $passwordActual, $passwordNueva) {

    $hashActual = obtenerHashPassword($conn, $usuarioId);
    

    if (!$hashActual) {
        return false;
    }

    if (!password_verify($passwordActual, $hashActual)) {
        return false;
    }

    $nuevoHash = password_hash($passwordNueva, PASSWORD_DEFAULT);

    return actualizarPasswordUsuario(
        $conn,
        $usuarioId,
        $nuevoHash
    );
}
