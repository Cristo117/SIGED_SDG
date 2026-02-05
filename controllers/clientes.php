<?php

require_once __DIR__ . '/../config/db.php';

/**
 * Obtiene todos los clientes con filtros opcionales.
 */
function obtenerClientes($filtroTipo = null, $filtroPago = null, $busqueda = null) {
    global $conn;
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM empleado e WHERE e.cliente_id = c.cliente_id) as num_empleados
            FROM cliente c WHERE 1=1";
    $params = [];
    
    if ($filtroTipo) {
        $sql .= " AND c.tipo_cliente = :tipo";
        $params['tipo'] = $filtroTipo;
    }
    if ($filtroPago) {
        $sql .= " AND c.estado_pago = :pago";
        $params['pago'] = $filtroPago === 'al-dia' ? 'AL_DIA' : 'PENDIENTE';
    }
    if ($busqueda) {
        $sql .= " AND (c.nombre LIKE :busqueda OR c.email LIKE :busqueda)";
        $params['busqueda'] = '%' . $busqueda . '%';
    }
    
    $sql .= " ORDER BY c.creado_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Obtiene los últimos N clientes registrados.
 */
function obtenerUltimosClientes($limite = 5) {
    global $conn;

    $limite = (int) $limite; // blindaje numérico 🛡️

    $sql = "SELECT nombre, email, estado_pago, creado_at 
            FROM cliente 
            ORDER BY creado_at DESC 
            LIMIT $limite";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
}


/**
 * Cuenta total de clientes.
 */
function contarClientes() {
    global $conn;
    $stmt = $conn->query("SELECT COUNT(*) as total FROM cliente");
    return (int) $stmt->fetch()['total'];
}

/**
 * Cuenta clientes con estado pendiente.
 */
function contarClientesPendientes() {
    global $conn;
    $stmt = $conn->query("SELECT COUNT(*) as total FROM cliente WHERE estado_pago = 'PENDIENTE'");
    return (int) $stmt->fetch()['total'];
}

/**
 * Obtiene un cliente por ID.
 */
function obtenerClientePorId($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE cliente_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Crea o actualiza un cliente.
 */
function guardarCliente($datos, $id = null) {
    global $conn;
    if ($id) {
        $stmt = $conn->prepare("UPDATE cliente SET nombre=?, email=?, tipo_identificacion=?, identificacion=?, tipo_cliente=?, estado_pago=? WHERE cliente_id=?");
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
    } else {
        $stmt = $conn->prepare("INSERT INTO cliente (nombre, email, tipo_identificacion, identificacion, tipo_cliente, estado_pago, creado_por) VALUES (?,?,?,?,?,?,?)");
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
}

/**
 * Elimina un cliente por ID.
 */
function eliminarCliente($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM cliente WHERE cliente_id = ?");
    return $stmt->execute([$id]);
}
