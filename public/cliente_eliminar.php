<?php
// Aseguramos que la sesión esté activa para los mensajes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../models/historial_model.php';

requireAuth();

if (!puedeEliminar()) {
    header('Location: clientes.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

// Lógica de redirección dinámica (igual que en tu header)
$archivo_actual = basename($_SERVER['PHP_SELF']);
$en_raiz = ($archivo_actual == 'index.php' || $archivo_actual == 'login.php');
$ruta_retorno = $en_raiz ? 'public/clientes.php' : 'clientes.php';

if ($id <= 0) {
    header("Location: $ruta_retorno");
    exit;
}

$clienteAntes = obtenerClientePorId($id);
$nombreCliente = $clienteAntes ? (($clienteAntes['nombre'] ?? '') . ' ' . ($clienteAntes['apellidos'] ?? '')) : '';

$eliminado = false;
try {
    $eliminado = eliminarCliente($id);
} catch (Exception $e) {
    // Log del error si fuera necesario: error_log($e->getMessage());
    $eliminado = false;
}

if ($eliminado && $nombreCliente) {
    registrarActividad($conn, 'ELIMINAR_CLIENTE', 'Movió a inactivos al cliente "' . trim($nombreCliente) . '"');
}

// Guardamos el mensaje en la sesión
$_SESSION['cliente_msg'] = $eliminado 
    ? 'Cliente movido a inactivos correctamente' 
    : 'No se pudo inactivar. Ejecute config/migration_cliente_activo.sql si es la primera vez.';
$_SESSION['cliente_msg_type'] = $eliminado ? 'success' : 'error';

// Redirección final usando la ruta calculada
header("Location: $ruta_retorno");
exit;