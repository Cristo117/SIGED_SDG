<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';

requireAuth();

$pageTitle = 'Agregar Cliente';
$activePage = 'clientes';
$cliente = null;
$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $cliente = obtenerClientePorId($id);
    if (!$cliente) {
        header('Location: clientes.php');
        exit;
    }
    $pageTitle = 'Editar Cliente';
}

$msg = $_SESSION['cliente_msg'] ?? null;
$msgType = $_SESSION['cliente_msg_type'] ?? 'success';
unset($_SESSION['cliente_msg'], $_SESSION['cliente_msg_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'email' => trim($_POST['email'] ?? '') ?: null,
        'tipo_identificacion' => trim($_POST['tipo_identificacion'] ?? '') ?: null,
        'identificacion' => trim($_POST['identificacion'] ?? '') ?: null,
        'tipo_cliente' => $_POST['tipo_cliente'] ?? 'INDEPENDIENTE',
        'estado_pago' => $_POST['estado_pago'] ?? 'AL_DIA'
    ];
    if (!empty($datos['nombre'])) {
        guardarCliente($datos, $id ?: null);
        $_SESSION['cliente_msg'] = $id ? 'Cliente actualizado' : 'Cliente creado correctamente';
        header('Location: clientes.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="clients-section">
    <div class="section-header">
        <div>
            <h2 class="section-title"><?= $id ? 'Editar' : 'Agregar' ?> Cliente</h2>
            <p class="section-subtitle">Complete los datos del cliente</p>
        </div>
        <a href="clientes.php" class="btn-add-client" style="background:#6c757d;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType === 'error' ? 'danger' : 'success' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST" class="profile-card" style="max-width: 600px;">
        <div class="form-row">
            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?= htmlspecialchars($cliente['nombre'] ?? $_POST['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($cliente['email'] ?? $_POST['email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="tipo_identificacion">Tipo Documento</label>
                <select id="tipo_identificacion" name="tipo_identificacion">
                    <option value="">Seleccione</option>
                    <option value="CC" <?= ($cliente['tipo_identificacion'] ?? '') === 'CC' ? 'selected' : '' ?>>Cédula</option>
                    <option value="NIT" <?= ($cliente['tipo_identificacion'] ?? '') === 'NIT' ? 'selected' : '' ?>>NIT</option>
                    <option value="Pasaporte" <?= ($cliente['tipo_identificacion'] ?? '') === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                </select>
            </div>
            <div class="form-group">
                <label for="identificacion">Número Documento</label>
                <input type="text" id="identificacion" name="identificacion" 
                       value="<?= htmlspecialchars($cliente['identificacion'] ?? $_POST['identificacion'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="tipo_cliente">Tipo Cliente</label>
                <select id="tipo_cliente" name="tipo_cliente">
                    <option value="INDEPENDIENTE" <?= ($cliente['tipo_cliente'] ?? '') === 'INDEPENDIENTE' ? 'selected' : '' ?>>Independiente</option>
                    <option value="EMPLEADOR" <?= ($cliente['tipo_cliente'] ?? '') === 'EMPLEADOR' ? 'selected' : '' ?>>Empleador</option>
                </select>
            </div>
            <div class="form-group">
                <label for="estado_pago">Estado de Pago</label>
                <select id="estado_pago" name="estado_pago">
                    <option value="AL_DIA" <?= ($cliente['estado_pago'] ?? '') === 'AL_DIA' ? 'selected' : '' ?>>Al Día</option>
                    <option value="PENDIENTE" <?= ($cliente['estado_pago'] ?? '') === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </form>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
