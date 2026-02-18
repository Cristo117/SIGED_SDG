<?php
// Aseguramos que la sesión esté activa para los mensajes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../controllers/clientes.php';

requireAuth();

$id = (int) ($_GET['id'] ?? 0);

// Lógica de redirección dinámica (igual que en tu header)
$archivo_actual = basename($_SERVER['PHP_SELF']);
$en_raiz = ($archivo_actual == 'index.php' || $archivo_actual == 'login.php');
$ruta_retorno = $en_raiz ? 'public/clientes.php' : 'clientes.php';

if ($id <= 0) {
    header("Location: $ruta_retorno");
    exit;
}

$eliminado = false;
try {
    $eliminado = eliminarCliente($id);
} catch (Exception $e) {
    // Log del error si fuera necesario: error_log($e->getMessage());
    $eliminado = false;
}

// Guardamos el mensaje en la sesión
$_SESSION['cliente_msg'] = $eliminado 
    ? 'Cliente eliminado correctamente' 
    : 'No se pudo eliminar. Verifique si el cliente tiene procesos o empleados asociados.';
$_SESSION['cliente_msg_type'] = $eliminado ? 'success' : 'error';

// Redirección final usando la ruta calculada
header("Location: $ruta_retorno");
exit;