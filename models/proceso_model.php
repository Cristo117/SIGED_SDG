<?php

/**
 * Asegura que proceso_cliente tenga la columna fecha_pago.
 */
function proceso_model_asegurarColumnaFechaPago($conn) {
    try {
        $conn->exec("ALTER TABLE proceso_cliente ADD COLUMN fecha_pago DATETIME DEFAULT NULL");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
    }
}

/**
 * Asegura que proceso_cliente tenga la columna empleado_id.
 */
function proceso_model_asegurarColumnaEmpleadoId($conn) {
    try {
        $conn->exec("ALTER TABLE proceso_cliente ADD COLUMN empleado_id INT(11) DEFAULT NULL");
        $conn->exec("ALTER TABLE proceso_cliente ADD KEY empleado_id (empleado_id)");
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false && strpos($e->getMessage(), 'Duplicate key') === false) {
            throw $e;
        }
    }
}

/**
 * Asegura que proceso_cliente tenga la columna estado_pago.
 */
function proceso_model_asegurarColumnaEstadoPagoProceso($conn) {
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
 * Obtiene todos los procesos activos.
 */
function proceso_model_obtenerProcesosActivos($conn) {
    $stmt = $conn->query("SELECT proceso_id, nombre, valor FROM proceso WHERE activo = 1 ORDER BY nombre");
    return $stmt->fetchAll();
}

/**
 * Obtiene o crea el proceso "Seguridad Social" para cobros mensuales.
 */
function proceso_model_obtenerOcrearProcesoSeguridadSocial($conn) {
    $stmt = $conn->query("SELECT proceso_id, nombre, valor FROM proceso WHERE activo = 1 AND LOWER(TRIM(nombre)) = 'seguridad social' LIMIT 1");
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }
    require_once __DIR__ . '/configuracion_model.php';
    $valor = configuracion_model_obtenerValorAfiliacion($conn);
    if ($valor <= 0) {
        $valor = 150000;
    }
    $conn->exec("INSERT INTO proceso (nombre, valor, activo) VALUES ('Seguridad Social', $valor, 1)");
    return [
        'proceso_id' => (int) $conn->lastInsertId(),
        'nombre' => 'Seguridad Social',
        'valor' => $valor
    ];
}

/**
 * Genera los cobros mensuales de Seguridad Social para el mes actual.
 * Crea un proceso_cliente "Seguridad Social" para cada cliente con cobrar_seguridad_social_mensual=1
 * que aún no tenga uno para este mes.
 */
function proceso_model_generarCobrosMensualesSeguridadSocial($conn) {
    proceso_model_asegurarColumnaEstadoPagoProceso($conn);
    $proceso = proceso_model_obtenerOcrearProcesoSeguridadSocial($conn);
    $procesoId = (int) $proceso['proceso_id'];
    $valor = (float) $proceso['valor'];

    $primerDia = date('Y-m-01');
    $ultimoDia = date('Y-m-t');

    $stmt = $conn->query("
        SELECT c.cliente_id
        FROM cliente c
        WHERE COALESCE(c.cobrar_seguridad_social_mensual, 1) = 1
          AND (c.activo = 1 OR c.activo IS NULL)
    ");
    $clientes = $stmt->fetchAll();

    $insertados = 0;
    $check = $conn->prepare("
        SELECT 1 FROM proceso_cliente
        WHERE cliente_id = ? AND proceso_id = ? AND estado = 'ACTIVO'
          AND fecha_asignacion >= ? AND fecha_asignacion <= ?
        LIMIT 1
    ");
    $ins = $conn->prepare("
        INSERT INTO proceso_cliente (cliente_id, proceso_id, valor_aplicado, estado, estado_pago, fecha_asignacion, fecha_vencimiento_pago)
        VALUES (?, ?, ?, 'ACTIVO', 'PENDIENTE', ?, ?)
    ");

    foreach ($clientes as $c) {
        $clienteId = (int) $c['cliente_id'];
        $check->execute([$clienteId, $procesoId, $primerDia, $ultimoDia]);
        if ($check->fetch()) {
            continue;
        }
        $ins->execute([$clienteId, $procesoId, $valor, $primerDia, $ultimoDia]);
        $insertados++;
        $conn->prepare("UPDATE cliente SET estado_pago = 'PENDIENTE' WHERE cliente_id = ?")->execute([$clienteId]);
    }

    return ['ok' => true, 'insertados' => $insertados, 'total_clientes' => count($clientes)];
}

/**
 * Agrega un nuevo proceso.
 */
function proceso_model_agregarProceso($conn, $nombre, $valor) {
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
function proceso_model_actualizarProceso($conn, $id, $nombre, $valor) {
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
function proceso_model_eliminarProceso($conn, $id) {
    $id = (int) $id;
    if ($id <= 0) {
        return ['ok' => false, 'error' => 'ID inválido.'];
    }
    $stmt = $conn->prepare("UPDATE proceso SET activo = 0 WHERE proceso_id = ?");
    $stmt->execute([$id]);
    return ['ok' => true];
}

/**
 * Obtiene clientes para asignar, con empleados para empleadores.
 * Retorna: [ { cliente_id, nombre, tipo_cliente, empleados: [{ empleado_id, nombre }] } ]
 */
function proceso_model_obtenerClientesParaAsignar($conn) {
    $stmt = $conn->query("SELECT cliente_id, nombre, tipo_cliente, estado_pago FROM cliente WHERE (activo = 1 OR activo IS NULL) ORDER BY nombre");
    $clientes = $stmt->fetchAll();
    require_once __DIR__ . '/empleado_model.php';
    foreach ($clientes as &$c) {
        $c['empleados'] = [];
        if (stripos($c['tipo_cliente'] ?? '', 'EMPLEADOR') !== false) {
            $c['empleados'] = empleado_model_obtenerEmpleadosPorCliente($conn, $c['cliente_id']);
        }
    }
    return $clientes;
}

/**
 * Asigna un proceso a un cliente o a un empleado.
 * @param int|null $empleadoId Si se asigna a empleado, su ID. Null para asignar al cliente.
 */
function proceso_model_asignarProcesoACliente($conn, $clienteId, $procesoId, $empleadoId = null) {
    proceso_model_asegurarColumnaEstadoPagoProceso($conn);
    proceso_model_asegurarColumnaEmpleadoId($conn);
    $clienteId = (int) $clienteId;
    $procesoId = (int) $procesoId;
    $empleadoId = $empleadoId ? (int) $empleadoId : null;

    if ($procesoId <= 0) {
        return ['ok' => false, 'error' => 'Proceso es obligatorio.'];
    }
    if ($empleadoId) {
        $stmt = $conn->prepare("SELECT cliente_id FROM empleado WHERE empleado_id = ? AND cliente_id = ?");
        $stmt->execute([$empleadoId, $clienteId]);
        if (!$stmt->fetch()) {
            return ['ok' => false, 'error' => 'Empleado no pertenece a este cliente.'];
        }
    } elseif ($clienteId <= 0) {
        return ['ok' => false, 'error' => 'Cliente es obligatorio.'];
    }

    $stmt = $conn->prepare("SELECT valor FROM proceso WHERE proceso_id = ? AND activo = 1");
    $stmt->execute([$procesoId]);
    $proceso = $stmt->fetch();
    if (!$proceso) {
        return ['ok' => false, 'error' => 'Proceso no encontrado.'];
    }
    $valor = (float) $proceso['valor'];
    $fecha = date('Y-m-d');

    // Fecha de vencimiento: si el cliente tiene cobrar_seguridad_social_mensual, fin de mes; si no, +30 días
    $stmtCliente = $conn->prepare("SELECT COALESCE(cobrar_seguridad_social_mensual, 1) as cobrar_ss FROM cliente WHERE cliente_id = ?");
    $stmtCliente->execute([$clienteId]);
    $rowCliente = $stmtCliente->fetch();
    $cobrarMensual = isset($rowCliente['cobrar_ss']) && (int)$rowCliente['cobrar_ss'] === 1;
    $fechaVenc = $cobrarMensual ? date('Y-m-t', strtotime($fecha)) : date('Y-m-d', strtotime($fecha . ' +30 days'));

    $cols = "cliente_id, proceso_id, valor_aplicado, estado, estado_pago, fecha_asignacion, fecha_vencimiento_pago";
    $placeholders = "?, ?, ?, 'ACTIVO', 'PENDIENTE', ?, ?";
    $params = [$clienteId, $procesoId, $valor, $fecha, $fechaVenc];

    if ($empleadoId) {
        $cols .= ", empleado_id";
        $placeholders .= ", ?";
        $params[] = $empleadoId;
    }

    $ins = $conn->prepare("INSERT INTO proceso_cliente ($cols) VALUES ($placeholders)");
    $ins->execute($params);
    $conn->prepare("UPDATE cliente SET estado_pago = 'PENDIENTE' WHERE cliente_id = ?")->execute([$clienteId]);
    return ['ok' => true];
}

/**
 * Obtiene los procesos asignados a un cliente (estado ACTIVO).
 * Incluye procesos del cliente y de sus empleados.
 */
function proceso_model_obtenerProcesosPorCliente($conn, $clienteId) {
    proceso_model_asegurarColumnaEmpleadoId($conn);
    $clienteId = (int) $clienteId;
    $stmt = $conn->prepare("
        SELECT pc.proceso_cliente_id, pc.proceso_id, pc.valor_aplicado, pc.fecha_asignacion, pc.estado_pago, pc.empleado_id,
               pc.fecha_vencimiento_pago,
               p.nombre as proceso_nombre, e.nombre as empleado_nombre
        FROM proceso_cliente pc
        JOIN proceso p ON p.proceso_id = pc.proceso_id
        LEFT JOIN empleado e ON e.empleado_id = pc.empleado_id
        WHERE pc.cliente_id = ? AND pc.estado = 'ACTIVO'
        ORDER BY pc.empleado_id IS NULL, e.nombre, pc.fecha_asignacion DESC
    ");
    $stmt->execute([$clienteId]);
    $rows = $stmt->fetchAll();
    $hoy = date('Y-m-d');
    foreach ($rows as &$r) {
        $estado = strtoupper($r['estado_pago'] ?? 'PENDIENTE');
        $venc = $r['fecha_vencimiento_pago'] ?? null;
        $r['dias_retraso'] = ($estado === 'PENDIENTE' && $venc && $venc < $hoy)
            ? (int) ((strtotime($hoy) - strtotime($venc)) / 86400)
            : 0;
    }
    return $rows;
}

/**
 * Obtiene nombre del cliente por ID.
 */
function proceso_model_obtenerNombreCliente($conn, $clienteId) {
    $stmt = $conn->prepare("SELECT nombre FROM cliente WHERE cliente_id = ?");
    $stmt->execute([(int) $clienteId]);
    $row = $stmt->fetch();
    return $row ? $row['nombre'] : '';
}

/**
 * Obtiene el estado de pago del cliente calculado desde sus procesos.
 * Si al menos un proceso está pendiente, el cliente está pendiente.
 */
function proceso_model_obtenerEstadoPagoCliente($conn, $clienteId) {
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
 * Cuando se marca AL_DIA, guarda fecha_pago para el historial.
 */
function proceso_model_toggleEstadoPagoProcesoCliente($conn, $procesoClienteId) {
    proceso_model_asegurarColumnaFechaPago($conn);
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
    if ($nuevo === 'AL_DIA') {
        $conn->prepare("UPDATE proceso_cliente SET estado_pago = ?, fecha_pago = NOW() WHERE proceso_cliente_id = ?")->execute([$nuevo, $id]);
    } else {
        $conn->prepare("UPDATE proceso_cliente SET estado_pago = ?, fecha_pago = NULL WHERE proceso_cliente_id = ?")->execute([$nuevo, $id]);
    }
    $estadoCliente = proceso_model_obtenerEstadoPagoCliente($conn, $clienteId);
    $conn->prepare("UPDATE cliente SET estado_pago = ? WHERE cliente_id = ?")->execute([$estadoCliente, $clienteId]);
    return ['ok' => true, 'estado_pago' => $nuevo, 'estado_cliente' => $estadoCliente];
}

/**
 * Obtiene estadísticas para el dashboard de reportes.
 * Cuentas: solo clientes con al menos un proceso PENDIENTE (procesos activos por cobrar).
 * Historial de pagos: clientes con procesos donde TODOS están AL_DIA (ya al día).
 */
function proceso_model_obtenerEstadisticasReportes($conn) {
    // Clientes con al menos un proceso PENDIENTE (cuentas activas)
    $stmt = $conn->query("
        SELECT c.cliente_id, c.nombre, c.tipo_cliente,
               COUNT(pc.proceso_cliente_id) as num_procesos,
               COALESCE(SUM(CASE WHEN COALESCE(pc.estado_pago, 'PENDIENTE') = 'AL_DIA' THEN pc.valor_aplicado ELSE 0 END), 0) as total_procesos
        FROM cliente c
        INNER JOIN proceso_cliente pc ON c.cliente_id = pc.cliente_id AND pc.estado = 'ACTIVO'
        WHERE (c.activo = 1 OR c.activo IS NULL)
        GROUP BY c.cliente_id, c.nombre, c.tipo_cliente
        HAVING SUM(CASE WHEN COALESCE(pc.estado_pago, 'PENDIENTE') = 'PENDIENTE' THEN 1 ELSE 0 END) > 0
    ");
    $cuentas = $stmt->fetchAll();

    // Máximo días de retraso por cliente (procesos pendientes vencidos)
    $stmtRetraso = $conn->query("
        SELECT pc.cliente_id, MAX(GREATEST(0, DATEDIFF(CURDATE(), pc.fecha_vencimiento_pago))) as dias_retraso_max
        FROM proceso_cliente pc
        WHERE pc.estado = 'ACTIVO' AND COALESCE(pc.estado_pago, 'PENDIENTE') = 'PENDIENTE'
          AND pc.fecha_vencimiento_pago IS NOT NULL AND pc.fecha_vencimiento_pago < CURDATE()
        GROUP BY pc.cliente_id
    ");
    $retrasos = [];
    if ($stmtRetraso) {
        while ($r = $stmtRetraso->fetch()) {
            $retrasos[(int)$r['cliente_id']] = (int)$r['dias_retraso_max'];
        }
    }

    // Historial de pagos: clientes con procesos donde TODOS están AL_DIA
    $stmtHistorial = $conn->query("
        SELECT c.cliente_id, c.nombre, c.tipo_cliente,
               COUNT(pc.proceso_cliente_id) as num_procesos,
               COALESCE(SUM(pc.valor_aplicado), 0) as total_procesos,
               MAX(pc.fecha_pago) as ultima_fecha_pago
        FROM cliente c
        INNER JOIN proceso_cliente pc ON c.cliente_id = pc.cliente_id AND pc.estado = 'ACTIVO'
        WHERE (c.activo = 1 OR c.activo IS NULL)
          AND COALESCE(pc.estado_pago, 'PENDIENTE') = 'AL_DIA'
        GROUP BY c.cliente_id, c.nombre, c.tipo_cliente
        HAVING COUNT(pc.proceso_cliente_id) = (
            SELECT COUNT(*) FROM proceso_cliente pc2
            WHERE pc2.cliente_id = c.cliente_id AND pc2.estado = 'ACTIVO'
        )
    ");
    $historialPagos = $stmtHistorial ? $stmtHistorial->fetchAll() : [];
    
    $ingresosTotales = 0;
    $clientesActivos = count($cuentas);
    $totalProcesos = 0;
    
    foreach ($cuentas as &$c) {
        $c['total_general'] = (float)$c['total_procesos'];
        $c['dias_retraso'] = $retrasos[(int)$c['cliente_id']] ?? 0;
        $ingresosTotales += $c['total_general'];
        $totalProcesos += (int)$c['num_procesos'];
    }

    // Ingresos totales incluyen también los del historial (procesos pagados)
    foreach ($historialPagos as $h) {
        $ingresosTotales += (float)$h['total_procesos'];
    }
    $totalProcesos += array_sum(array_column($historialPagos, 'num_procesos'));
    
    $promedioCliente = 0;
    $totalClientesConProcesos = count($cuentas) + count($historialPagos);
    if ($totalClientesConProcesos > 0) {
        $promedioCliente = round($ingresosTotales / $totalClientesConProcesos);
    }
    
    return [
        'ingresos_totales' => $ingresosTotales,
        'clientes_activos' => $clientesActivos,
        'total_procesos' => (int)$totalProcesos,
        'promedio_cliente' => $promedioCliente,
        'cuentas' => $cuentas,
        'historial_pagos' => $historialPagos
    ];
}

/**
 * Top 5 clientes por facturación (incluye cuentas activas e historial).
 */
function proceso_model_obtenerTopClientes($conn, $limite = 5) {
    $stats = proceso_model_obtenerEstadisticasReportes($conn);
    $historial = [];
    foreach ($stats['historial_pagos'] as $h) {
        $h['total_general'] = (float)($h['total_procesos'] ?? 0);
        $historial[] = $h;
    }
    $todos = array_merge($stats['cuentas'], $historial);
    usort($todos, function($a, $b) {
        $ta = (float)($a['total_general'] ?? 0);
        $tb = (float)($b['total_general'] ?? 0);
        return $tb <=> $ta;
    });
    return array_slice($todos, 0, $limite);
}

/**
 * Ingresos por tipo de cliente (incluye cuentas activas e historial).
 */
function proceso_model_obtenerIngresosPorTipo($conn) {
    $stats = proceso_model_obtenerEstadisticasReportes($conn);
    $empleador = 0;
    $independiente = 0;
    foreach (array_merge($stats['cuentas'], $stats['historial_pagos']) as $c) {
        $total = (float)($c['total_general'] ?? $c['total_procesos'] ?? 0);
        if (stripos($c['tipo_cliente'] ?? '', 'EMPLEADOR') !== false) {
            $empleador += $total;
        } else {
            $independiente += $total;
        }
    }
    return ['empleador' => $empleador, 'independiente' => $independiente];
}

/**
 * Obtiene la configuración completa (procesos disponibles).
 */
function proceso_model_obtenerConfiguracionValores($conn) {
    return [
        'procesos' => proceso_model_obtenerProcesosActivos($conn)
    ];
}

/**
 * Obtiene el historial de pagos (clientes con todos sus procesos AL_DIA).
 * Preparado para paginación futura con $limite y $offset.
 */
function proceso_model_obtenerHistorialPagos($conn, $limite = 500, $offset = 0) {
    proceso_model_asegurarColumnaFechaPago($conn);
    $offset = max(0, (int) $offset);
    $limite = max(1, min(500, (int) $limite));
    $sql = "
        SELECT c.cliente_id, c.nombre, c.tipo_cliente,
               COUNT(pc.proceso_cliente_id) as num_procesos,
               COALESCE(SUM(pc.valor_aplicado), 0) as total_procesos,
               MAX(pc.fecha_pago) as ultima_fecha_pago
        FROM cliente c
        INNER JOIN proceso_cliente pc ON c.cliente_id = pc.cliente_id AND pc.estado = 'ACTIVO'
        WHERE (c.activo = 1 OR c.activo IS NULL)
          AND COALESCE(pc.estado_pago, 'PENDIENTE') = 'AL_DIA'
        GROUP BY c.cliente_id, c.nombre, c.tipo_cliente
        HAVING COUNT(pc.proceso_cliente_id) = (
            SELECT COUNT(*) FROM proceso_cliente pc2
            WHERE pc2.cliente_id = c.cliente_id AND pc2.estado = 'ACTIVO'
        )
        ORDER BY ultima_fecha_pago DESC
        LIMIT " . $limite . " OFFSET " . $offset . "
    ";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll();
}

/**
 * Cuenta los registros del historial de pagos.
 */
function proceso_model_contarHistorialPagos($conn) {
    $stmt = $conn->query("
        SELECT COUNT(*) as total FROM (
            SELECT c.cliente_id
            FROM cliente c
            INNER JOIN proceso_cliente pc ON c.cliente_id = pc.cliente_id AND pc.estado = 'ACTIVO'
            WHERE (c.activo = 1 OR c.activo IS NULL)
              AND COALESCE(pc.estado_pago, 'PENDIENTE') = 'AL_DIA'
            GROUP BY c.cliente_id
            HAVING COUNT(pc.proceso_cliente_id) = (
                SELECT COUNT(*) FROM proceso_cliente pc2
                WHERE pc2.cliente_id = c.cliente_id AND pc2.estado = 'ACTIVO'
            )
        ) sub
    ");
    $row = $stmt->fetch();
    return (int) ($row['total'] ?? 0);
}

/**
 * Elimina todos los procesos de un cliente y reinicia su contabilidad.
 * Marca proceso_cliente como estado='ELIMINADO' (soft delete) y cliente.estado_pago='AL_DIA'.
 */
function proceso_model_eliminarProcesosCliente($conn, $clienteId) {
    $clienteId = (int) $clienteId;
    if ($clienteId <= 0) {
        return ['ok' => false, 'error' => 'Cliente inválido.'];
    }
    $stmt = $conn->prepare("UPDATE proceso_cliente SET estado = 'ELIMINADO' WHERE cliente_id = ? AND estado = 'ACTIVO'");
    $stmt->execute([$clienteId]);
    $affected = $stmt->rowCount();
    $conn->prepare("UPDATE cliente SET estado_pago = 'AL_DIA' WHERE cliente_id = ?")->execute([$clienteId]);
    return ['ok' => true, 'eliminados' => $affected];
}

/**
 * Cuenta los procesos activos (proceso_cliente con estado ACTIVO).
 */
function proceso_model_contarProcesosActivos($conn) {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM proceso_cliente WHERE estado = 'ACTIVO'");
    $row = $stmt->fetch();
    return (int) $row['total'];
}
