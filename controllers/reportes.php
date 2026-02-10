<?php

require_once __DIR__ . '/../config/db.php';

/**
 * Asegura que proceso_cliente tenga la columna estado_pago.
 */
function asegurarColumnaEstadoPagoProceso() {
    global $conn;
    try {
        $conn->exec("ALTER TABLE proceso_cliente ADD COLUMN estado_pago VARCHAR(30) DEFAULT 'PENDIENTE'");
        $conn->exec("
            UPDATE cliente c SET estado_pago = IF(
                (SELECT COUNT(*) FROM proceso_cliente pc WHERE pc.cliente_id = c.cliente_id AND pc.estado = 'ACTIVO' AND COALESCE(pc.estado_pago, 'PENDIENTE') = 'PENDIENTE') > 0,
                'PENDIENTE', 'AL_DIA'
            )
        ");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
    }
}

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

/**
 * Obtiene todos los procesos activos.
 */
function obtenerProcesosActivos() {
    global $conn;
    $stmt = $conn->query("SELECT proceso_id, nombre, valor FROM proceso WHERE activo = 1 ORDER BY nombre");
    return $stmt->fetchAll();
}

/**
 * Obtiene la configuración completa (valor afiliación + procesos).
 */
function obtenerConfiguracionValores() {
    return [
        'valor_afiliacion' => obtenerValorAfiliacion(),
        'procesos' => obtenerProcesosActivos()
    ];
}

/**
 * Guarda el valor de afiliación en la tabla configuracion.
 * Desactiva la config anterior y crea una nueva.
 */
function guardarValorAfiliacion($valor) {
    global $conn;
    $valor = max(0, (float) $valor);
    $conn->exec("UPDATE configuracion SET activo = 0 WHERE activo = 1");
    $stmt = $conn->prepare("INSERT INTO configuracion (valor_afiliacion, activo) VALUES (?, 1)");
    $stmt->execute([$valor]);
    return true;
}

/**
 * Inserta la primera fila en configuracion si no existe ninguna.
 */
function asegurarConfiguracionInicial() {
    global $conn;
    $stmt = $conn->query("SELECT COUNT(*) as n FROM configuracion");
    if ((int) $stmt->fetch()['n'] === 0) {
        $conn->exec("INSERT INTO configuracion (valor_afiliacion, activo) VALUES (250000, 1)");
    }
}

/**
 * Agrega un nuevo proceso.
 */
function agregarProceso($nombre, $valor) {
    global $conn;
    $nombre = trim($nombre);
    $valor = max(0, (float) preg_replace('/[^\d]/', '', (string) $valor));
    if (empty($nombre)) {
        return ['ok' => false, 'error' => 'El nombre del proceso es obligatorio.'];
    }
    $stmt = $conn->prepare("INSERT INTO proceso (nombre, valor, activo) VALUES (?, ?, 1)");
    $stmt->execute([$nombre, $valor]);
    return ['ok' => true, 'id' => (int) $conn->lastInsertId()];
}

/**
 * Actualiza un proceso existente.
 */
function actualizarProceso($id, $nombre, $valor) {
    global $conn;
    $id = (int) $id;
    $nombre = trim($nombre);
    $valor = max(0, (float) preg_replace('/[^\d]/', '', (string) $valor));
    if ($id <= 0 || empty($nombre)) {
        return ['ok' => false, 'error' => 'Datos inválidos.'];
    }
    $stmt = $conn->prepare("UPDATE proceso SET nombre = ?, valor = ? WHERE proceso_id = ?");
    $stmt->execute([$nombre, $valor, $id]);
    return ['ok' => true];
}

/**
 * Elimina (soft delete) un proceso poniendo activo = 0.
 */
function eliminarProceso($id) {
    global $conn;
    $id = (int) $id;
    if ($id <= 0) {
        return ['ok' => false, 'error' => 'ID inválido.'];
    }
    $stmt = $conn->prepare("UPDATE proceso SET activo = 0 WHERE proceso_id = ?");
    $stmt->execute([$id]);
    return ['ok' => true];
}

/**
 * Obtiene clientes para dropdown (id, nombre, estado_pago).
 */
function obtenerClientesParaAsignar() {
    global $conn;
    $stmt = $conn->query("SELECT cliente_id, nombre, estado_pago FROM cliente ORDER BY nombre");
    return $stmt->fetchAll();
}

/**
 * Asigna un proceso a un cliente.
 */
function asignarProcesoACliente($clienteId, $procesoId) {
    global $conn;
    asegurarColumnaEstadoPagoProceso();
    $clienteId = (int) $clienteId;
    $procesoId = (int) $procesoId;
    if ($clienteId <= 0 || $procesoId <= 0) {
        return ['ok' => false, 'error' => 'Cliente y proceso son obligatorios.'];
    }
    $stmt = $conn->prepare("SELECT valor FROM proceso WHERE proceso_id = ? AND activo = 1");
    $stmt->execute([$procesoId]);
    $proceso = $stmt->fetch();
    if (!$proceso) {
        return ['ok' => false, 'error' => 'Proceso no encontrado.'];
    }
    $valor = (float) $proceso['valor'];
    $fecha = date('Y-m-d');
    $ins = $conn->prepare("INSERT INTO proceso_cliente (cliente_id, proceso_id, valor_aplicado, estado, estado_pago, fecha_asignacion) VALUES (?, ?, ?, 'ACTIVO', 'PENDIENTE', ?)");
    $ins->execute([$clienteId, $procesoId, $valor, $fecha]);
    $conn->prepare("UPDATE cliente SET estado_pago = 'PENDIENTE' WHERE cliente_id = ?")->execute([$clienteId]);
    return ['ok' => true];
}

/**
 * Obtiene los procesos asignados a un cliente (estado ACTIVO).
 */
function obtenerProcesosPorCliente($clienteId) {
    global $conn;
    $clienteId = (int) $clienteId;
    $stmt = $conn->prepare("
        SELECT pc.proceso_cliente_id, pc.proceso_id, pc.valor_aplicado, pc.fecha_asignacion, pc.estado_pago, p.nombre as proceso_nombre
        FROM proceso_cliente pc
        JOIN proceso p ON p.proceso_id = pc.proceso_id
        WHERE pc.cliente_id = ? AND pc.estado = 'ACTIVO'
        ORDER BY pc.fecha_asignacion DESC
    ");
    $stmt->execute([$clienteId]);
    return $stmt->fetchAll();
}

/**
 * Obtiene nombre del cliente por ID.
 */
function obtenerNombreCliente($clienteId) {
    global $conn;
    $stmt = $conn->prepare("SELECT nombre FROM cliente WHERE cliente_id = ?");
    $stmt->execute([(int) $clienteId]);
    $row = $stmt->fetch();
    return $row ? $row['nombre'] : '';
}

/**
 * Obtiene el estado de pago del cliente calculado desde sus procesos.
 * Si al menos un proceso está pendiente, el cliente está pendiente.
 */
function obtenerEstadoPagoCliente($clienteId) {
    global $conn;
    $clienteId = (int) $clienteId;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as n FROM proceso_cliente
        WHERE cliente_id = ? AND estado = 'ACTIVO' AND COALESCE(estado_pago, 'PENDIENTE') = 'PENDIENTE'
    ");
    $stmt->execute([$clienteId]);
    $row = $stmt->fetch();
    $tienePendiente = isset($row['n']) && (int)$row['n'] > 0;
    return $tienePendiente ? 'PENDIENTE' : 'AL_DIA';
}

/**
 * Alterna el estado de pago de un proceso (PENDIENTE <-> AL_DIA) y actualiza el del cliente.
 */
function toggleEstadoPagoProcesoCliente($procesoClienteId) {
    global $conn;
    $id = (int) $procesoClienteId;
    if ($id <= 0) {
        return ['ok' => false, 'error' => 'ID inválido.'];
    }
    $stmt = $conn->prepare("SELECT cliente_id, COALESCE(estado_pago, 'PENDIENTE') as estado_pago FROM proceso_cliente WHERE proceso_cliente_id = ? AND estado = 'ACTIVO'");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'error' => 'Proceso no encontrado.'];
    }
    $nuevo = $row['estado_pago'] === 'PENDIENTE' ? 'AL_DIA' : 'PENDIENTE';
    $clienteId = (int) $row['cliente_id'];
    $conn->prepare("UPDATE proceso_cliente SET estado_pago = ? WHERE proceso_cliente_id = ?")->execute([$nuevo, $id]);
    $estadoCliente = obtenerEstadoPagoCliente($clienteId);
    $conn->prepare("UPDATE cliente SET estado_pago = ? WHERE cliente_id = ?")->execute([$estadoCliente, $clienteId]);
    return ['ok' => true, 'estado_pago' => $nuevo, 'estado_cliente' => $estadoCliente];
}
