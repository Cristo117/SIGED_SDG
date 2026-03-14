<?php

function cliente_model_obtenerClientes($conn, $filtroTipo = null, $filtroPago = null, $busqueda = null) {

    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM empleado e WHERE e.cliente_id = c.cliente_id) AS num_empleados,
            (SELECT COUNT(*) FROM cliente_documento d WHERE d.cliente_id = c.cliente_id) AS num_documentos
            FROM cliente c WHERE (c.activo = 1 OR c.activo IS NULL)";

    $params = [];

    if ($filtroTipo) {
        $sql .= " AND c.tipo_cliente = ?";
        $params[] = $filtroTipo;
    }

    if ($filtroPago) {
        $sql .= " AND c.estado_pago = ?";
        $params[] = $filtroPago === 'al-dia' ? 'AL_DIA' : 'PENDIENTE';
    }

    if ($busqueda) {
        $sql .= " AND (c.nombre LIKE ? OR c.email LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }

    $sql .= " ORDER BY c.creado_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}


function cliente_model_obtenerUltimosClientes($conn, $limite = 5) {

    $limite = (int) $limite;

    $stmt = $conn->prepare(
        "SELECT nombre, email, estado_pago, creado_at 
         FROM cliente 
         WHERE (activo = 1 OR activo IS NULL)
         ORDER BY creado_at DESC 
         LIMIT $limite"
    );

    $stmt->execute();
    return $stmt->fetchAll();
}


function cliente_model_contarClientes($conn) {
    return (int) $conn->query("SELECT COUNT(*) FROM cliente WHERE (activo = 1 OR activo IS NULL)")->fetchColumn();
}


function cliente_model_contarClientesPendientes($conn) {
    // Solo cuenta clientes con cobrar_seguridad_social_mensual activo y pago pendiente
    return (int) $conn->query(
        "SELECT COUNT(*) FROM cliente WHERE estado_pago = 'PENDIENTE' AND COALESCE(cobrar_seguridad_social_mensual, 1) = 1 AND (activo = 1 OR activo IS NULL)"
    )->fetchColumn();
}


function cliente_model_obtenerClientePorId($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE cliente_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}


function cliente_model_guardarCliente($conn, $datos, $id = null) {

    if ($id) {

        $stmt = $conn->prepare("
            UPDATE cliente 
            SET nombre=?, apellidos=?, email=?, tipo_identificacion=?, identificacion=?, 
                tipo_cliente=?, estado_pago=?, cobrar_seguridad_social_mensual=? 
            WHERE cliente_id=?
        ");

        try {
            $stmt->execute([
                $datos['nombre'],
                $datos['apellidos'] ?? null,
                $datos['email'] ?? null,
                $datos['tipo_identificacion'] ?? null,
                $datos['identificacion'] ?? null,
                $datos['tipo_cliente'],
                $datos['estado_pago'] ?? 'AL_DIA',
                empty($datos['cobrar_seguridad_social_mensual']) ? 0 : 1,
                $id
            ]);
        } catch (PDOException $e) {
            // Duplicado de número de identificación
            if ($e->getCode() === '23000' && strpos($e->getMessage(), 'identificacion') !== false) {
                return false;
            }
            throw $e;
        }

        return $id;
    }

    $stmt = $conn->prepare("
        INSERT INTO cliente 
        (nombre, apellidos, email, tipo_identificacion, identificacion, tipo_cliente, estado_pago, cobrar_seguridad_social_mensual, creado_por) 
        VALUES (?,?,?,?,?,?,?,?,?)
    ");

    try {
        $stmt->execute([
            $datos['nombre'],
            $datos['apellidos'] ?? null,
            $datos['email'] ?? null,
            $datos['tipo_identificacion'] ?? null,
            $datos['identificacion'] ?? null,
            $datos['tipo_cliente'],
            $datos['estado_pago'] ?? 'AL_DIA',
            empty($datos['cobrar_seguridad_social_mensual']) ? 0 : 1,
            $_SESSION['usuario_id'] ?? null
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' && strpos($e->getMessage(), 'identificacion') !== false) {
            return false;
        }
        throw $e;
    }

    return (int) $conn->lastInsertId();
}


function cliente_model_eliminarCliente($conn, $id) {
    $stmt = $conn->prepare("UPDATE cliente SET activo = 0 WHERE cliente_id = ?");
    return $stmt->execute([$id]);
}

function cliente_model_obtenerClientesInactivos($conn) {
    $stmt = $conn->prepare(
        "SELECT c.*, 
         (SELECT COUNT(*) FROM empleado e WHERE e.cliente_id = c.cliente_id) AS num_empleados,
         (SELECT COUNT(*) FROM cliente_documento d WHERE d.cliente_id = c.cliente_id) AS num_documentos
         FROM cliente c WHERE c.activo = 0 ORDER BY c.creado_at DESC"
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function cliente_model_reactivarCliente($conn, $id) {
    $stmt = $conn->prepare("UPDATE cliente SET activo = 1 WHERE cliente_id = ?");
    return $stmt->execute([$id]);
}
