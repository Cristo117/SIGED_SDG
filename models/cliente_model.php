<?php

function obtenerClientes($conn, $filtroTipo = null, $filtroPago = null, $busqueda = null) {

    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM empleado e WHERE e.cliente_id = c.cliente_id) AS num_empleados
            FROM cliente c WHERE 1=1";

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


function obtenerUltimosClientes($conn, $limite = 5) {

    $limite = (int) $limite;

    $stmt = $conn->prepare(
        "SELECT nombre, email, estado_pago, creado_at 
         FROM cliente 
         ORDER BY creado_at DESC 
         LIMIT $limite"
    );

    $stmt->execute();
    return $stmt->fetchAll();
}


function contarClientes($conn) {
    return (int) $conn->query("SELECT COUNT(*) FROM cliente")->fetchColumn();
}


function contarClientesPendientes($conn) {
    return (int) $conn->query(
        "SELECT COUNT(*) FROM cliente WHERE estado_pago = 'PENDIENTE'"
    )->fetchColumn();
}


function obtenerClientePorId($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE cliente_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}


function guardarCliente($conn, $datos, $id = null) {

    if ($id) {

        $stmt = $conn->prepare("
            UPDATE cliente 
            SET nombre=?, email=?, tipo_identificacion=?, identificacion=?, 
                tipo_cliente=?, estado_pago=? 
            WHERE cliente_id=?
        ");

        $stmt->execute([
            $datos['nombre'],
            $datos['email'] ?? null,
            $datos['tipo_identificacion'] ?? null,
            $datos['identificacion'] ?? null,
            $datos['tipo_cliente'],
            $datos['estado_pago'] ?? 'AL_DIA',
            $id
        ]);

        return $id;
    }

    $stmt = $conn->prepare("
        INSERT INTO cliente 
        (nombre, email, tipo_identificacion, identificacion, tipo_cliente, estado_pago, creado_por) 
        VALUES (?,?,?,?,?,?,?)
    ");

    $stmt->execute([
        $datos['nombre'],
        $datos['email'] ?? null,
        $datos['tipo_identificacion'] ?? null,
        $datos['identificacion'] ?? null,
        $datos['tipo_cliente'],
        $datos['estado_pago'] ?? 'AL_DIA',
        $_SESSION['usuario_id'] ?? null
    ]);

    return (int) $conn->lastInsertId();
}


function eliminarCliente($conn, $id) {

    $conn->prepare("DELETE FROM info_adicional WHERE cliente_id = ?")->execute([$id]);

    $ids = $conn->prepare("SELECT empleado_id FROM empleado WHERE cliente_id = ?");
    $ids->execute([$id]);

    foreach ($ids->fetchAll() as $r) {
        $conn->prepare(
            "DELETE FROM info_adicional WHERE empleado_id = ?"
        )->execute([$r['empleado_id']]);
    }

    $conn->prepare("DELETE FROM empleado WHERE cliente_id = ?")->execute([$id]);

    return $conn->prepare(
        "DELETE FROM cliente WHERE cliente_id = ?"
    )->execute([$id]);
}
