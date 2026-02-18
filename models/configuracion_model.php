<?php

/**
 * Inserta la primera fila en configuracion si no existe ninguna.
 */
function asegurarConfiguracionInicial($conn) {
    $stmt = $conn->query("SELECT COUNT(*) as n FROM configuracion");
    if ((int) $stmt->fetch()['n'] === 0) {
        $conn->exec("INSERT INTO configuracion (valor_afiliacion, activo) VALUES (250000, 1)");
    }
}

/**
 * Obtiene el valor de afiliación activo.
 */
function obtenerValorAfiliacion($conn) {
    $stmt = $conn->query("SELECT valor_afiliacion FROM configuracion WHERE activo = 1 ORDER BY configuracion_id DESC LIMIT 1");
    $row = $stmt->fetch();
    return $row ? (float) $row['valor_afiliacion'] : 250000;
}

/**
 * Guarda el valor de afiliación en la tabla configuracion.
 * Desactiva la config anterior y crea una nueva.
 */
function guardarValorAfiliacion($conn, $valor) {
    $valor = max(0, (float) $valor);
    $conn->exec("UPDATE configuracion SET activo = 0 WHERE activo = 1");
    $stmt = $conn->prepare("INSERT INTO configuracion (valor_afiliacion, activo) VALUES (?, 1)");
    $stmt->execute([$valor]);
    return true;
}
