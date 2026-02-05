<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';

requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: clientes.php');
    exit;
}

$cliente = obtenerClientePorId($id);
if (!$cliente) {
    header('Location: clientes.php');
    exit;
}

$pageTitle = 'Detalle Cliente';
$activePage = 'clientes';

// Obtener empleados del cliente
$stmt = $conn->prepare("SELECT * FROM empleado WHERE cliente_id = ?");
$stmt->execute([$id]);
$empleados = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="clients-section">
    <div class="section-header">
        <div>
            <h2 class="section-title"><?= htmlspecialchars($cliente['nombre']) ?></h2>
            <p class="section-subtitle">Información del cliente</p>
        </div>
        <a href="cliente_editar.php?id=<?= $id ?>" class="btn-add-client">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>

    <div class="profile-card">
        <h3 class="panel-subtitle">Datos del Cliente</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Nombre</label>
                <p><?= htmlspecialchars($cliente['nombre']) ?></p>
            </div>
            <div class="form-group">
                <label>Correo</label>
                <p><?= htmlspecialchars($cliente['email'] ?? '-') ?></p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Tipo Cliente</label>
                <p><?= htmlspecialchars($cliente['tipo_cliente']) ?></p>
            </div>
            <div class="form-group">
                <label>Documento</label>
                <p><?= htmlspecialchars(trim(($cliente['tipo_identificacion'] ?? '') . ' ' . ($cliente['identificacion'] ?? '')) ?: '-') ?></p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Estado de Pago</label>
                <p><span class="badge badge-<?= $cliente['estado_pago'] === 'AL_DIA' ? 'success' : 'pending' ?>">
                    <?= $cliente['estado_pago'] === 'AL_DIA' ? 'Al Día' : 'Pendiente' ?>
                </span></p>
            </div>
            <div class="form-group">
                <label>Fecha Registro</label>
                <p><?= date('Y-m-d H:i', strtotime($cliente['creado_at'])) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($empleados)): ?>
    <h3 class="section-title" style="margin-top: 2rem;">Empleados (<?= count($empleados) ?>)</h3>
    <div class="table-card">
        <table class="clients-management-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Documento</th>
                    <th>Cargo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($empleados as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['nombre']) ?></td>
                    <td><?= htmlspecialchars($e['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars(trim(($e['tipo_documento'] ?? '') . ' ' . ($e['numero_documento'] ?? '')) ?: '-') ?></td>
                    <td><?= htmlspecialchars($e['cargo'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
