<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';

requireAuth();

$pageTitle = 'Inicio';
$activePage = 'inicio';

// Consultas a la base de datos
$totalClientes = contarClientes();
$ultimosClientes = obtenerUltimosClientes(5);
$clientesPendientes = contarClientesPendientes();

// Documentos/procesos aprobados (proceso_cliente con estado ACTIVO)
$stmt = $conn->query("SELECT COUNT(*) as total FROM proceso_cliente WHERE estado = 'ACTIVO'");
$documentosAprobados = (int) $stmt->fetch()['total'];

// Notificaciones no leídas
$notificacionesCount = 0;
if (!empty($_SESSION['usuario_id'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificacion WHERE usuario_id = ? AND leida = 0");
    $stmt->execute([$_SESSION['usuario_id']]);
    $notificacionesCount = (int) $stmt->fetch()['total'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="panel-section">
    <h2 class="section-title">Panel General</h2>
    <p class="section-subtitle">Resumen de la actividad del sistema de gestión documental</p>

    <div class="alert-cards">
        <?php if ($clientesPendientes > 0): ?>
        <div class="alert-card alert-danger">
            <div class="alert-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="alert-content">
                <h3>Seguridad Social Pendiente</h3>
                <p><?= $clientesPendientes ?> cliente(s) sin pago mensual de Seguridad Social</p>
            </div>
        </div>
        <?php endif; ?>
        <div class="alert-card alert-warning">
            <div class="alert-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="alert-content">
                <h3>Pagos con Retraso</h3>
                <p>Procesos con pago retrasado (revisar en Reportes)</p>
            </div>
        </div>
    </div>

    <div class="metrics-cards">
        <div class="metric-card">
            <div class="metric-icon metric-blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="metric-content">
                <p class="metric-label">Total Clientes</p>
                <h3 class="metric-value"><?= $totalClientes ?></h3>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-icon metric-green">
                <i class="fas fa-file-check"></i>
            </div>
            <div class="metric-content">
                <p class="metric-label">Procesos Activos</p>
                <h3 class="metric-value"><?= $documentosAprobados ?></h3>
            </div>
        </div>
    </div>
</section>

<section class="table-section">
    <h2 class="section-title">Últimos Clientes Ingresados</h2>
    <p class="section-subtitle">Lista de los últimos clientes registrados en el sistema</p>

    <div class="table-card">
        <table class="clients-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo Electrónico</th>
                    <th>Estado de Pago</th>
                    <th>Fecha de Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimosClientes)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2rem;">No hay clientes registrados</td>
                </tr>
                <?php else: ?>
                <?php foreach ($ultimosClientes as $cli): ?>
                <tr>
                    <td><?= htmlspecialchars($cli['nombre']) ?></td>
                    <td><?= htmlspecialchars($cli['email'] ?? '-') ?></td>
                    <td>
                        <span class="badge badge-<?= $cli['estado_pago'] === 'AL_DIA' ? 'success' : 'pending' ?>">
                            <?= $cli['estado_pago'] === 'AL_DIA' ? 'Al Día' : 'Pendiente' ?>
                        </span>
                    </td>
                    <td><?= date('Y-m-d', strtotime($cli['creado_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
