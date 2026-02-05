<?php

require_once __DIR__ . '/../config/db.php';

/**
 * Obtiene el valor de afiliación activo.
 */
function obtenerValorAfiliacion() {
    global $conn;
    $stmt = $conn->query("SELECT valor_afiliacion FROM configuracion WHERE activo = 1 ORDER BY configuracion_id DESC LIMIT 1");
    $row = $stmt->fetch();
    return $row ? (float) $row['valor_afiliacion'] : 250000;
}

/**
 * Obtiene estadísticas para el dashboard de reportes.
 */
function obtenerEstadisticasReportes() {
    global $conn;
    $valorAfiliacion = obtenerValorAfiliacion();
    
    // Clientes con al menos un proceso
    $stmt = $conn->query("
        SELECT c.cliente_id, c.nombre, c.tipo_cliente,
               COUNT(pc.proceso_cliente_id) as num_procesos,
               COALESCE(SUM(pc.valor_aplicado), 0) as total_procesos
        FROM cliente c
        LEFT JOIN proceso_cliente pc ON c.cliente_id = pc.cliente_id AND pc.estado = 'ACTIVO'
        GROUP BY c.cliente_id, c.nombre, c.tipo_cliente
        HAVING num_procesos > 0
    ");
    $cuentas = $stmt->fetchAll();
    
    $ingresosTotales = 0;
    $clientesActivos = count($cuentas);
    $totalProcesos = 0;
    
    foreach ($cuentas as &$c) {
        $c['afiliacion'] = $valorAfiliacion;
        $c['total_general'] = $valorAfiliacion + (float)$c['total_procesos'];
        $ingresosTotales += $c['total_general'];
        $totalProcesos += (int)$c['num_procesos'];
    }
    
    $promedioCliente = $clientesActivos > 0 ? round($ingresosTotales / $clientesActivos) : 0;
    
    return [
        'ingresos_totales' => $ingresosTotales,
        'clientes_activos' => $clientesActivos,
        'total_procesos' => $totalProcesos,
        'promedio_cliente' => $promedioCliente,
        'cuentas' => $cuentas,
        'valor_afiliacion' => $valorAfiliacion
    ];
}

/**
 * Top 5 clientes por facturación.
 */
function obtenerTopClientes($limite = 5) {
    $stats = obtenerEstadisticasReportes();
    $cuentas = $stats['cuentas'];
    usort($cuentas, function($a, $b) {
        return $b['total_general'] <=> $a['total_general'];
    });
    return array_slice($cuentas, 0, $limite);
}

/**
 * Ingresos por tipo de cliente.
 */
function obtenerIngresosPorTipo() {
    $stats = obtenerEstadisticasReportes();
    $empleador = 0;
    $independiente = 0;
    foreach ($stats['cuentas'] as $c) {
        if (stripos($c['tipo_cliente'], 'EMPLEADOR') !== false) {
            $empleador += $c['total_general'];
        } else {
            $independiente += $c['total_general'];
        }
    }
    return ['empleador' => $empleador, 'independiente' => $independiente];
}
