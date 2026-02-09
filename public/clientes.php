<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';

requireAuth();

$pageTitle = 'Clientes';
$activePage = 'clientes';

$filtroTipo = $_GET['tipo'] ?? null;
$filtroPago = $_GET['pago'] ?? null;
$busqueda = $_GET['busqueda'] ?? null;

$clientes = obtenerClientes($filtroTipo, $filtroPago, $busqueda);
$totalClientes = count($clientes);

// Notificaciones
$notificacionesCount = 0;
if (!empty($_SESSION['usuario_id'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificacion WHERE usuario_id = ? AND leida = 0");
    $stmt->execute([$_SESSION['usuario_id']]);
    $notificacionesCount = (int) $stmt->fetch()['total'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="clients-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Gestión de Clientes</h2>
            <p class="section-subtitle">Administra y organiza la información de tus clientes</p>
        </div>
        <button class="btn-add-client" id="btnAddClient">
            <i class="fas fa-plus"></i>
            <span>Agregar Cliente</span>
        </button>
    </div>

    <form method="GET" class="search-filter-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="busqueda" id="searchInput" placeholder="Buscar por nombre o correo..."
                value="<?= htmlspecialchars($busqueda ?? '') ?>" />
        </div>
        <div class="filters">
            <select class="filter-select" name="tipo" id="filterType">
                <option value="">Todos los tipos</option>
                <option value="INDEPENDIENTE" <?= $filtroTipo === 'INDEPENDIENTE' ? 'selected' : '' ?>>Independiente</option>
                <option value="EMPLEADOR" <?= $filtroTipo === 'EMPLEADOR' ? 'selected' : '' ?>>Empleador</option>
            </select>
            <select class="filter-select" name="pago" id="filterPayment">
                <option value="">Todos los pagos</option>
                <option value="al-dia" <?= $filtroPago === 'al-dia' ? 'selected' : '' ?>>Al Día</option>
                <option value="pendiente" <?= $filtroPago === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            </select>
            <button type="submit" class="btn-filter">Filtrar</button>
        </div>
    </form>

    <div class="table-card">
        <div class="table-header">
            <h3 class="table-title">Lista de Clientes <span class="client-count">(<?= $totalClientes ?>)</span></h3>
        </div>
        <table class="clients-management-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Correo</th>
                    <th>Documento</th>
                    <th>Empleados</th>
                    <th>Estado de Pago</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="clientsTableBody">
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem;">No hay clientes que coincidan con los filtros</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $c):
                        $doc = trim(($c['tipo_identificacion'] ?? '') . ' ' . ($c['identificacion'] ?? ''));
                        $tipoClase = strtolower($c['tipo_cliente']) === 'empleador' ? 'empleador' : 'independiente';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nombre']) ?></td>
                            <td>
                                <span class="badge badge-type badge-<?= $tipoClase ?>">
                                    <i class="fas fa-<?= $tipoClase === 'empleador' ? 'building' : 'user' ?>"></i>
                                    <?= htmlspecialchars($c['tipo_cliente']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($doc ?: '-') ?></td>
                            <td>
                                <?php if (!empty($c['num_empleados']) && $c['num_empleados'] > 0): ?>
                                    <i class="fas fa-user"></i> <?= (int)$c['num_empleados'] ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $c['estado_pago'] === 'AL_DIA' ? 'success' : 'pending' ?>">
                                    <?= $c['estado_pago'] === 'AL_DIA' ? 'Al Día' : 'Pendiente' ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($c['creado_at'])) ?></td>
                            <td class="action-buttons">
                                <a href="cliente_detalle.php?id=<?= $c['cliente_id'] ?>" class="btn-action btn-view" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="cliente_editar.php?id=<?= $c['cliente_id'] ?>" class="btn-action btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn-action btn-delete" title="Eliminar"
                                    data-id="<?= $c['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php
$extraScripts = <<<'SCRIPT'
<script>
document.getElementById('btnAddClient')?.addEventListener('click', function () {
    window.location.href = 'cliente_editar.php';
});

document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.getAttribute('data-id');
        const nombre = this.getAttribute('data-nombre');
        if (confirm('¿Está seguro de eliminar el cliente "' + nombre + '"?')) {
            window.location.href = 'cliente_eliminar.php?id=' + id;
        }
    });
});
</script>
SCRIPT;
require_once __DIR__ . '/../includes/footer.php';
?>