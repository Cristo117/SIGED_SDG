<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/empleados.php';
require_once __DIR__ . '/../controllers/info_adicional.php';

requireAuth();

$pageTitle = 'Clientes Inactivos';
$activePage = 'clientes_inactivos';

$inactivos = obtenerClientesInactivos();
$empleadoresActivos = array_filter(obtenerClientes(null, null, null), function ($c) {
    return strtoupper($c['tipo_cliente'] ?? '') === 'EMPLEADOR';
});

$datos = [];
foreach ($inactivos as $c) {
    $empleados = obtenerEmpleadosPorCliente($c['cliente_id']);
    $notas = [];
    foreach ($empleados as $e) {
        $notas[$e['empleado_id']] = obtenerNotasEmpleado($e['empleado_id']);
    }
    $datos[] = ['cliente' => $c, 'empleados' => $empleados, 'notas' => $notas];
}

require_once __DIR__ . '/../controllers/notificaciones.php';
$notificacionesCount = 0;
if (!empty($_SESSION['usuario_id'])) {
    $notificacionesCount = obtenerNotificacionesNoLeidas($conn, $_SESSION['usuario_id']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="clients-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Clientes Inactivos</h2>
            <p class="section-subtitle">Reactiva clientes o reasigna empleados a nuevos empleadores</p>
        </div>
        <a href="clientes.php" class="btn-add-client">
            <i class="fas fa-arrow-left"></i>
            <span>Volver a Clientes</span>
        </a>
    </div>

    <div class="table-card">
        <?php if (empty($datos)): ?>
        <p class="inactivos-vacio">No hay clientes inactivos.</p>
        <?php else: ?>
        <div class="inactivos-lista">
            <?php foreach ($datos as $item):
                $c = $item['cliente'];
                $empleados = $item['empleados'];
                $nombresCl = trim($c['nombre'] ?? '');
                $apellidosCl = trim($c['apellidos'] ?? '');
                if ($apellidosCl === '' && strpos($nombresCl, ' ') !== false) {
                    $p = preg_split('/\s+/', $nombresCl);
                    if (count($p) > 1) {
                        $apellidosCl = array_pop($p);
                        $nombresCl = implode(' ', $p);
                    }
                }
                $nombreCompleto = $nombresCl . ($apellidosCl ? ' ' . $apellidosCl : '') ?: $c['nombre'];
            ?>
            <div class="inactivo-card">
                <div class="inactivo-header">
                    <div class="inactivo-info">
                        <h3><?= htmlspecialchars($nombreCompleto) ?></h3>
                        <span class="badge badge-type badge-<?= strtolower($c['tipo_cliente'] ?? '') === 'empleador' ? 'empleador' : 'independiente' ?>"><?= htmlspecialchars($c['tipo_cliente'] ?? '') ?></span>
                        <span class="inactivo-email"><?= htmlspecialchars($c['email'] ?? '-') ?></span>
                    </div>
                    <button type="button" class="btn-reactivar" data-id="<?= $c['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($nombreCompleto) ?>" title="Reactivar cliente">
                        <i class="fas fa-undo"></i> Reactivar
                    </button>
                </div>

                <?php if (!empty($empleados)): ?>
                <div class="inactivo-empleados">
                    <h4><i class="fas fa-users"></i> Empleados (<?= count($empleados) ?>) – Reasignar a nuevo empleador</h4>
                    <table class="inactivo-empleados-table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Documento</th>
                                <th>Cargo</th>
                                <th>Reasignar a</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $e):
                                $nomEmp = trim($e['nombre'] ?? '') . (trim($e['apellidos'] ?? '') ? ' ' . trim($e['apellidos']) : '');
                                $docEmp = trim(($e['tipo_documento'] ?? '') . ' ' . ($e['numero_documento'] ?? ''));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($nomEmp ?: '-') ?></td>
                                <td><?= htmlspecialchars($docEmp ?: '-') ?></td>
                                <td><?= htmlspecialchars($e['cargo'] ?? '-') ?></td>
                                <td>
                                    <select class="select-reasignar" data-empleado-id="<?= $e['empleado_id'] ?>" data-cliente-inactivo="<?= $c['cliente_id'] ?>">
                                        <option value="">-- Seleccione empleador --</option>
                                        <?php foreach ($empleadoresActivos as $emp): ?>
                                        <option value="<?= $emp['cliente_id'] ?>"><?= htmlspecialchars($emp['nombre'] ?? '') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn-reasignar" data-empleado-id="<?= $e['empleado_id'] ?>" data-cliente-inactivo="<?= $c['cliente_id'] ?>" title="Reasignar">
                                        <i class="fas fa-arrow-right"></i> Reasignar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<div class="toast" id="toastInactivos" role="status"></div>

<script>
(function() {
    function toast(msg, type) {
        var t = document.getElementById('toastInactivos');
        if (!t) return;
        t.textContent = msg;
        t.className = 'toast show ' + (type === 'error' ? 'toast-error' : 'toast-success');
        setTimeout(function() { t.classList.remove('show'); }, 3000);
    }

    document.querySelectorAll('.btn-reactivar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nombre = this.getAttribute('data-nombre');
            if (!confirm('¿Reactivar al cliente "' + nombre + '"?')) return;
            fetch('api_clientes_inactivos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reactivar', cliente_id: parseInt(id, 10) })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok) { toast('Cliente reactivado. Redirigiendo...', 'success'); setTimeout(function() { window.location.reload(); }, 800); }
                else toast(data.error || 'Error', 'error');
            })
            .catch(function() { toast('Error al reactivar', 'error'); });
        });
    });

    document.querySelectorAll('.btn-reasignar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var empId = parseInt(this.getAttribute('data-empleado-id'), 10);
            var cliInact = parseInt(this.getAttribute('data-cliente-inactivo'), 10);
            var row = this.closest('tr');
            var sel = row ? row.querySelector('.select-reasignar') : null;
            var nuevoId = sel ? parseInt(sel.value, 10) : 0;
            if (!nuevoId) { toast('Seleccione un empleador destino', 'error'); return; }
            fetch('api_clientes_inactivos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reasignar_empleado', empleado_id: empId, nuevo_cliente_id: nuevoId, cliente_inactivo_id: cliInact })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok) { toast('Empleado reasignado', 'success'); setTimeout(function() { window.location.reload(); }, 800); }
                else toast(data.error || 'Error', 'error');
            })
            .catch(function() { toast('Error al reasignar', 'error'); });
        });
    });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
