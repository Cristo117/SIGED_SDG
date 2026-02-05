<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../controllers/clientes.php';

requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: clientes.php');
    exit;
}

$eliminado = false;
try {
    $eliminado = eliminarCliente($id);
} catch (Exception $e) {
    // El cliente puede tener datos relacionados (empleados, procesos, etc.)
}

$_SESSION['cliente_msg'] = $eliminado ? 'Cliente eliminado correctamente' : 'No se pudo eliminar. El cliente puede tener datos relacionados.';
$_SESSION['cliente_msg_type'] = $eliminado ? 'success' : 'error';

header('Location: clientes.php');
exit;
