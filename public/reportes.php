<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/reportes.php';

requireAuth();

$pageTitle = 'Reportes';
$activePage = 'reportes';

$stats = obtenerEstadisticasReportes();
$topClientes = obtenerTopClientes(5);
$ingresosPorTipo = obtenerIngresosPorTipo();

$notificacionesCount = 0;
if (!empty($_SESSION['usuario_id'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificacion WHERE usuario_id = ? AND leida = 0");
    $stmt->execute([$_SESSION['usuario_id']]);
    $notificacionesCount = (int) $stmt->fetch()['total'];
}

$loadChartJs = true;
require_once __DIR__ . '/../includes/header.php';
?>

<section class="reportes-section">
    <div class="reportes-header">
        <div>
            <h2 class="section-title">Contabilidad y Procesos</h2>
            <p class="section-subtitle">Gestión de procesos y facturación por cliente</p>
        </div>
        <div class="reportes-actions">
            <button type="button" class="btn-config" id="btnConfigurar">
                <i class="fas fa-cog"></i>
                <span>Configurar Valores</span>
            </button>
            <button type="button" class="btn-asignar" id="btnAsignar">
                <i class="fas fa-plus"></i>
                <span>Asignar Proceso</span>
            </button>
        </div>
    </div>

    <div class="stats-cards">
        <div class="stat-card stat-card-1">
            <div class="stat-content">
                <p class="stat-label">Ingresos Totales</p>
                <p class="stat-value" data-value="<?= (int)$stats['ingresos_totales'] ?>" data-prefix="$" data-format="number">0</p>
                <p class="stat-sub">Todos los clientes</p>
            </div>
            <div class="stat-icon stat-icon-green">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-card stat-card-2">
            <div class="stat-content">
                <p class="stat-label">Clientes Activos</p>
                <p class="stat-value" data-value="<?= $stats['clientes_activos'] ?>" data-format="number">0</p>
                <p class="stat-sub">Con procesos</p>
            </div>
            <div class="stat-icon stat-icon-blue">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="stat-card stat-card-3">
            <div class="stat-content">
                <p class="stat-label">Total Procesos</p>
                <p class="stat-value" data-value="<?= $stats['total_procesos'] ?>" data-format="number">0</p>
                <p class="stat-sub">Realizados</p>
            </div>
            <div class="stat-icon stat-icon-purple">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
        <div class="stat-card stat-card-4">
            <div class="stat-content">
                <p class="stat-label">Promedio por Cliente</p>
                <p class="stat-value" data-value="<?= (int)$stats['promedio_cliente'] ?>" data-prefix="$" data-format="number">0</p>
                <p class="stat-sub">Valor medio</p>
            </div>
            <div class="stat-icon stat-icon-orange">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
    </div>

    <div class="charts-row">
        <div class="chart-card">
            <h3 class="chart-title">Top 5 Clientes</h3>
            <p class="chart-subtitle">Clientes con mayor facturación</p>
            <div class="chart-wrap">
                <canvas id="chartTopClientes"></canvas>
            </div>
        </div>
        <div class="chart-card">
            <h3 class="chart-title">Ingresos por Tipo</h3>
            <p class="chart-subtitle">Distribución por tipo de cliente</p>
            <div class="chart-wrap chart-pie-wrap">
                <canvas id="chartIngresosTipo"></canvas>
            </div>
            <div class="pie-legend" id="pieLegend"></div>
        </div>
    </div>

    <div class="cuentas-section">
        <h3 class="section-title">Cuentas de Clientes <span class="client-count">(<?= count($stats['cuentas']) ?>)</span></h3>
        <p class="section-subtitle">Resumen de procesos y facturación por cliente</p>
        <div class="table-card">
            <table class="cuentas-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Afiliación</th>
                        <th>Procesos</th>
                        <th>Total Procesos</th>
                        <th>Total General</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats['cuentas'])): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">No hay clientes con procesos asignados</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($stats['cuentas'] as $c): 
                        $tipoClase = stripos($c['tipo_cliente'], 'EMPLEADOR') !== false ? 'empleador' : 'independiente';
                        $totalProc = (float)$c['total_procesos'];
                        $totalGen = (float)$c['total_general'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                        <td><span class="badge badge-type badge-<?= $tipoClase ?>"><i class="fas fa-<?= $tipoClase === 'empleador' ? 'building' : 'user' ?>"></i> <?= htmlspecialchars($c['tipo_cliente']) ?></span></td>
                        <td class="text-green">$<?= number_format($c['afiliacion'], 0, ',', '.') ?></td>
                        <td><?= (int)$c['num_procesos'] ?></td>
                        <td class="text-blue">$<?= number_format($totalProc, 0, ',', '.') ?></td>
                        <td>$<?= number_format($totalGen, 0, ',', '.') ?></td>
                        <td class="td-acciones">
                            <a href="cliente_detalle.php?id=<?= $c['cliente_id'] ?>" class="btn-action btn-view" title="Ver detalles"><i class="fas fa-eye"></i></a>
                            <button type="button" class="btn-action btn-procesos" title="Ver procesos y estado" data-cliente-id="<?= $c['cliente_id'] ?>" data-cliente-nombre="<?= htmlspecialchars($c['nombre']) ?>"><i class="fas fa-list"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<!-- Modal Configurar Valores -->
<div class="modal-overlay" id="modalConfigurar" aria-hidden="true">
    <div class="modal config-modal" role="dialog" aria-labelledby="modalConfigTitulo" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalConfigTitulo" class="modal-title">Configurar Valores de Procesos</h2>
            <button type="button" class="modal-close" aria-label="Cerrar" data-close="modalConfigurar"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p class="modal-desc">Ajusta el valor de cada proceso. Estos valores se aplicarán a todos los clientes.</p>
            <div class="form-group config-afiliacion">
                <label for="valorAfiliacion">Valor de Afiliación</label>
                <input type="number" id="valorAfiliacion" min="0" step="1" value="250000">
                <small class="form-hint">Este valor se suma automáticamente a cada cliente</small>
            </div>
            <div class="procesos-section">
                <div class="procesos-header">
                    <h3 class="procesos-title">Procesos Disponibles <span class="procesos-count" id="procesosCount">(0)</span></h3>
                    <button type="button" class="btn-nuevo-proceso" id="btnNuevoProceso"><i class="fas fa-plus"></i> Nuevo Proceso</button>
                </div>
                <div class="procesos-list" id="procesosList"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cerrar" data-close="modalConfigurar">Cerrar</button>
            <button type="button" class="btn-modal btn-guardar" id="btnGuardarConfig">Guardar Configuración</button>
        </div>
    </div>
</div>

<!-- Modal Procesos y Estado del Cliente -->
<div class="modal-overlay" id="modalProcesosCliente" aria-hidden="true">
    <div class="modal procesos-cliente-modal" role="dialog" aria-labelledby="modalProcesosClienteTitulo" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalProcesosClienteTitulo" class="modal-title">Procesos de <span id="modalProcesosClienteNombre"></span></h2>
            <button type="button" class="modal-close" aria-label="Cerrar" data-close="modalProcesosCliente"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="cliente-estado-pago-wrap cliente-estado-readonly">
                <label>Estado del Cliente</label>
                <p class="estado-cliente-badge" id="estadoClienteBadge"><span class="badge badge-success">Al Día</span></p>
                <small class="form-hint">Se calcula automáticamente: si algún proceso está pendiente, el cliente queda pendiente.</small>
            </div>
            <h4 class="procesos-subtitulo">Procesos asignados</h4>
            <p class="modal-desc">Haz clic en el botón para cambiar el estado de cada proceso (Pendiente / Al Día).</p>
            <div class="procesos-cliente-list" id="procesosClienteList"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cerrar" data-close="modalProcesosCliente">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Asignar Proceso a Cliente -->
<div class="modal-overlay" id="modalAsignar" aria-hidden="true">
    <div class="modal asignar-modal" role="dialog" aria-labelledby="modalAsignarTitulo" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalAsignarTitulo" class="modal-title">Asignar Proceso a Cliente</h2>
            <button type="button" class="modal-close" aria-label="Cerrar" data-close="modalAsignar"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p class="modal-desc">Selecciona un cliente y un proceso para agregar a su cuenta</p>
            <div class="form-group">
                <label for="selectCliente">Cliente</label>
                <select id="selectCliente" class="form-select">
                    <option value="">Seleccione un cliente</option>
                </select>
            </div>
            <div class="form-group">
                <label for="selectProceso">Proceso</label>
                <select id="selectProceso" class="form-select">
                    <option value="">Seleccione un proceso</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cancelar" data-close="modalAsignar">Cancelar</button>
            <button type="button" class="btn-modal btn-asignar" id="btnAsignarProceso">Asignar Proceso</button>
        </div>
    </div>
</div>

<!-- Modal Agregar/Editar Proceso -->
<div class="modal-overlay" id="modalProceso" aria-hidden="true">
    <div class="modal proceso-modal" role="dialog" aria-labelledby="modalProcesoTitulo" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalProcesoTitulo" class="modal-title">Agregar Nuevo Proceso</h2>
            <button type="button" class="modal-close" aria-label="Cerrar" id="btnCerrarProceso"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p class="modal-desc">Define un nuevo tipo de proceso con su valor correspondiente.</p>
            <input type="hidden" id="procesoIdEdit" value="">
            <div class="form-group">
                <label for="procesoNombre">Nombre del Proceso</label>
                <input type="text" id="procesoNombre" placeholder="Ej: Certificado de Trabajo">
            </div>
            <div class="form-group">
                <label for="procesoValor">Valor del Proceso</label>
                <input type="number" id="procesoValor" min="0" step="1" value="150000" placeholder="150000">
                <small class="form-hint">Formato: Número sin puntos ni comas (Ej: 150000)</small>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cancelar" id="btnCancelarProceso">Cancelar</button>
            <button type="button" class="btn-modal btn-agregar" id="btnAgregarProceso">Agregar Proceso</button>
        </div>
    </div>
</div>

<?php
$topLabels = array_map(function($c) { return $c['nombre']; }, $topClientes);
$topData = array_map(function($c) { return (float)$c['total_general']; }, $topClientes);
$pieEmpleador = $ingresosPorTipo['empleador'];
$pieIndependiente = $ingresosPorTipo['independiente'];
ob_start();
?>
<script>
(function() {
    function formatNumber(n) { return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
    function animateValue(el, start, end, duration, prefix) {
        prefix = prefix || '';
        var startTime = null;
        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var easeOut = 1 - Math.pow(1 - progress, 2);
            var current = Math.floor(start + (end - start) * easeOut);
            el.textContent = prefix + formatNumber(current);
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = prefix + formatNumber(end);
        }
        requestAnimationFrame(step);
    }
    function initStatCards() {
        var cards = document.querySelectorAll('.stat-card');
        cards.forEach(function(card) { card.style.opacity = '0'; card.style.transform = 'translateY(20px)'; });
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry, idx) {
                if (!entry.isIntersecting) return;
                var card = entry.target;
                card.style.transition = 'opacity 0.5s ease ' + (idx * 0.1) + 's, transform 0.5s ease ' + (idx * 0.1) + 's';
                card.style.opacity = '1'; card.style.transform = 'translateY(0)';
                var valEl = card.querySelector('.stat-value');
                if (valEl) {
                    var value = parseInt(valEl.getAttribute('data-value'), 10);
                    animateValue(valEl, 0, value, 1200, valEl.getAttribute('data-prefix') || '');
                }
                observer.unobserve(card);
            });
        }, { threshold: 0.2 });
        cards.forEach(function(c) { observer.observe(c); });
    }
    function initCharts() {
        var labels = <?= json_encode($topLabels) ?>;
        var data = <?= json_encode($topData) ?>;
        var ctxBar = document.getElementById('chartTopClientes');
        if (ctxBar && labels.length) {
            new Chart(ctxBar.getContext('2d'), {
                type: 'bar',
                data: { labels: labels, datasets: [{ label: 'Facturación', data: data, backgroundColor: '#1a2049', borderRadius: 6 }] },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: true, animation: { duration: 1200 },
                    plugins: { legend: { display: false } },
                    scales: { x: { ticks: { callback: function(v) { return formatNumber(v); } }, grid: { color: 'rgba(0,0,0,0.06)' } }, y: { grid: { display: false } } }
                }
            });
        }
        var ctxPie = document.getElementById('chartIngresosTipo');
        if (ctxPie) {
            var emp = <?= (float)$pieEmpleador ?>;
            var ind = <?= (float)$pieIndependiente ?>;
            new Chart(ctxPie.getContext('2d'), {
                type: 'doughnut',
                data: { labels: ['Empleadores', 'Independientes'], datasets: [{ data: [emp, ind], backgroundColor: ['#1a2049', '#fcc107'], borderWidth: 0 }] },
                options: { responsive: true, animation: { duration: 1000 }, plugins: { legend: { display: false } }, cutout: '60%' }
            });
            document.getElementById('pieLegend').innerHTML = '<span class="pie-legend-item" style="color:#1a2049"><i class="fas fa-circle"></i> Empleadores: $' + formatNumber(Math.round(emp)) + '</span><span class="pie-legend-item" style="color:#fcc107"><i class="fas fa-circle"></i> Independientes: $' + formatNumber(Math.round(ind)) + '</span>';
        }
    }
    function showToast(msg, type) {
        var t = document.getElementById('toast');
        if (t) { t.textContent = msg; t.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success') + ' show'; setTimeout(function() { t.classList.remove('show'); }, 3000); }
    }
    var configData = { valor_afiliacion: 250000, procesos: [] };
    var API_CONFIG = 'api_configuracion_valores.php';

    function openModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.add('show'); m.setAttribute('aria-hidden', 'false'); }
    }
    function closeModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.remove('show'); m.setAttribute('aria-hidden', 'true'); }
    }
    document.querySelectorAll('[data-close]').forEach(function(btn) {
        btn.addEventListener('click', function() { closeModal(this.getAttribute('data-close')); });
    });
    document.querySelectorAll('.modal-overlay').forEach(function(ov) {
        ov.addEventListener('click', function(e) { if (e.target === ov) closeModal(ov.id); });
    });

    function loadConfig() {
        fetch(API_CONFIG).then(function(r) { return r.json(); }).then(function(data) {
            configData = data;
            document.getElementById('valorAfiliacion').value = Math.round(data.valor_afiliacion);
            renderProcesos(data.procesos || []);
        }).catch(function() { showToast('Error al cargar la configuración.', 'error'); });
    }
    function renderProcesos(procesos) {
        var list = document.getElementById('procesosList');
        var count = document.getElementById('procesosCount');
        count.textContent = '(' + (procesos.length) + ')';
        if (!procesos.length) {
            list.innerHTML = '<p class="procesos-empty">No hay procesos configurados. Haz clic en "Nuevo Proceso" para agregar.</p>';
            return;
        }
        list.innerHTML = procesos.map(function(p) {
            var val = parseFloat(p.valor);
            var formatted = val.toLocaleString('es-CO', { minimumFractionDigits: 0 });
            return '<div class="proceso-item" data-id="' + p.proceso_id + '">' +
                '<div class="proceso-info"><span class="proceso-nombre">' + escapeHtml(p.nombre) + '</span><span class="proceso-valor">$' + formatted + '</span></div>' +
                '<div class="proceso-actions">' +
                '<button type="button" class="btn-icon btn-edit" title="Editar" data-edit="' + p.proceso_id + '"><i class="fas fa-pen"></i></button>' +
                '<button type="button" class="btn-icon btn-delete" title="Eliminar" data-delete="' + p.proceso_id + '"><i class="fas fa-trash"></i></button>' +
                '</div></div>';
        }).join('');
        list.querySelectorAll('[data-edit]').forEach(function(btn) {
            btn.addEventListener('click', function() { openEditProceso(parseInt(this.getAttribute('data-edit'), 10)); });
        });
        list.querySelectorAll('[data-delete]').forEach(function(btn) {
            btn.addEventListener('click', function() { deleteProceso(parseInt(this.getAttribute('data-delete'), 10)); });
        });
    }
    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function openEditProceso(id) {
        var p = configData.procesos.find(function(x) { return x.proceso_id == id; });
        if (!p) return;
        document.getElementById('modalProcesoTitulo').textContent = 'Editar Proceso';
        document.getElementById('procesoIdEdit').value = p.proceso_id;
        document.getElementById('procesoNombre').value = p.nombre;
        document.getElementById('procesoValor').value = Math.round(parseFloat(p.valor));
        document.getElementById('btnAgregarProceso').textContent = 'Guardar Cambios';
        closeModal('modalConfigurar');
        openModal('modalProceso');
    }
    function openAddProceso() {
        document.getElementById('modalProcesoTitulo').textContent = 'Agregar Nuevo Proceso';
        document.getElementById('procesoIdEdit').value = '';
        document.getElementById('procesoNombre').value = '';
        document.getElementById('procesoValor').value = '150000';
        document.getElementById('btnAgregarProceso').textContent = 'Agregar Proceso';
        closeModal('modalConfigurar');
        openModal('modalProceso');
    }
    function deleteProceso(id) {
        if (!confirm('¿Está seguro de eliminar este proceso?')) return;
        fetch(API_CONFIG, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'eliminar_proceso', proceso_id: id })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.ok) { showToast('Proceso eliminado.'); loadConfig(); } else { showToast(res.error || 'Error', 'error'); }
        }).catch(function() { showToast('Error al eliminar.', 'error'); });
    }
    function saveProceso() {
        var id = document.getElementById('procesoIdEdit').value;
        var nombre = (document.getElementById('procesoNombre').value || '').trim();
        var valor = document.getElementById('procesoValor').value || '0';
        if (!nombre) { showToast('El nombre del proceso es obligatorio.', 'error'); return; }
        var body = id ? { action: 'editar_proceso', proceso_id: parseInt(id, 10), nombre: nombre, valor: valor } : { action: 'agregar_proceso', nombre: nombre, valor: valor };
        fetch(API_CONFIG, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.ok) {
                showToast(id ? 'Proceso actualizado.' : 'Proceso agregado.');
                closeModal('modalProceso');
                loadConfig();
                openModal('modalConfigurar');
            } else { showToast(res.error || 'Error', 'error'); }
        }).catch(function() { showToast('Error al guardar.', 'error'); });
    }
    function saveConfig() {
        var valor = document.getElementById('valorAfiliacion').value || '0';
        fetch(API_CONFIG, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'guardar_afiliacion', valor_afiliacion: valor })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.ok) { showToast('Configuración guardada correctamente.'); setTimeout(function() { location.reload(); }, 800); }
            else { showToast(res.error || 'Error', 'error'); }
        }).catch(function() { showToast('Error al guardar.', 'error'); });
    }

    function cerrarModalProceso() { closeModal('modalProceso'); openModal('modalConfigurar'); }
    document.getElementById('btnCancelarProceso')?.addEventListener('click', cerrarModalProceso);
    document.getElementById('btnCerrarProceso')?.addEventListener('click', cerrarModalProceso);
    document.getElementById('btnConfigurar')?.addEventListener('click', function() { loadConfig(); openModal('modalConfigurar'); });
    document.getElementById('btnNuevoProceso')?.addEventListener('click', function() { openAddProceso(); });
    document.getElementById('btnAgregarProceso')?.addEventListener('click', saveProceso);
    document.getElementById('btnGuardarConfig')?.addEventListener('click', saveConfig);

    var API_ASIGNAR = 'api_asignar_proceso.php';
    var asignarClientesData = [];
    function loadAsignarData() {
        fetch(API_ASIGNAR).then(function(r) { return r.json(); }).then(function(data) {
            asignarClientesData = data.clientes || [];
            var selCliente = document.getElementById('selectCliente');
            var selProceso = document.getElementById('selectProceso');
            selCliente.innerHTML = '<option value="">Seleccione un cliente</option>' + asignarClientesData.map(function(c) { return '<option value="' + c.cliente_id + '">' + escapeHtml(c.nombre) + '</option>'; }).join('');
            selProceso.innerHTML = '<option value="">Seleccione un proceso</option>' + (data.procesos || []).map(function(p) { return '<option value="' + p.proceso_id + '">' + escapeHtml(p.nombre) + ' - $' + parseFloat(p.valor).toLocaleString('es-CO', { minimumFractionDigits: 0 }) + '</option>'; }).join('');
        }).catch(function() { showToast('Error al cargar datos.', 'error'); });
    }
    function submitAsignar() {
        var clienteId = document.getElementById('selectCliente').value;
        var procesoId = document.getElementById('selectProceso').value;
        if (!clienteId || !procesoId) { showToast('Selecciona un cliente y un proceso.', 'error'); return; }
        fetch(API_ASIGNAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId, proceso_id: procesoId })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.ok) { showToast('Proceso asignado correctamente.'); closeModal('modalAsignar'); setTimeout(function() { location.reload(); }, 800); }
            else { showToast(res.error || 'Error', 'error'); }
        }).catch(function() { showToast('Error al asignar.', 'error'); });
    }
    document.getElementById('btnAsignar')?.addEventListener('click', function() { loadAsignarData(); openModal('modalAsignar'); });
    document.getElementById('btnAsignarProceso')?.addEventListener('click', submitAsignar);

    var API_PROCESOS_CLIENTE = 'api_procesos_cliente.php';
    var modalProcesosClienteId = null;
    function renderProcesoItem(p) {
        var val = parseFloat(p.valor_aplicado);
        var formatted = val.toLocaleString('es-CO', { minimumFractionDigits: 0 });
        var estado = (p.estado_pago || 'PENDIENTE').toUpperCase();
        var isAlDia = estado === 'AL_DIA';
        var btnClass = isAlDia ? 'btn-estado-aldia' : 'btn-estado-pendiente';
        var btnText = isAlDia ? 'Al Día' : 'Pendiente';
        return '<div class="proceso-item proceso-cliente-item" data-id="' + p.proceso_cliente_id + '" data-estado="' + estado + '">' +
            '<div class="proceso-info"><span class="proceso-nombre">' + escapeHtml(p.proceso_nombre) + '</span><span class="proceso-valor">$' + formatted + '</span></div>' +
            '<button type="button" class="btn-toggle-estado ' + btnClass + '" title="Cambiar estado" data-id="' + p.proceso_cliente_id + '">' + btnText + '</button></div>';
    }
    function actualizarBadgeEstado(estado) {
        var badge = document.getElementById('estadoClienteBadge');
        if (!badge) return;
        badge.innerHTML = estado === 'AL_DIA' ? '<span class="badge badge-success">Al Día</span>' : '<span class="badge badge-pending">Pendiente</span>';
    }
    document.querySelectorAll('.btn-procesos').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-cliente-id');
            var nombre = this.getAttribute('data-cliente-nombre');
            modalProcesosClienteId = id;
            document.getElementById('modalProcesosClienteNombre').textContent = nombre;
            fetch(API_PROCESOS_CLIENTE + '?cliente_id=' + id).then(function(r) { return r.json(); }).then(function(data) {
                var list = document.getElementById('procesosClienteList');
                actualizarBadgeEstado(data.estado_pago || 'AL_DIA');
                var procesos = data.procesos || [];
                if (!procesos.length) {
                    list.innerHTML = '<p class="procesos-empty">No hay procesos asignados.</p>';
                } else {
                    list.innerHTML = procesos.map(renderProcesoItem).join('');
                    list.querySelectorAll('.btn-toggle-estado').forEach(function(b) {
                        b.addEventListener('click', function() {
                            var pcId = parseInt(this.getAttribute('data-id'), 10);
                            var btn = this;
                            fetch(API_PROCESOS_CLIENTE, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'toggle_estado_pago', proceso_cliente_id: pcId })
                            }).then(function(r) { return r.json(); }).then(function(res) {
                                if (res.ok) {
                                    showToast('Estado actualizado.');
                                    var item = list.querySelector('.proceso-cliente-item[data-id="' + pcId + '"]');
                                    if (item) {
                                        var nuevo = res.estado_pago;
                                        item.setAttribute('data-estado', nuevo);
                                        btn.textContent = nuevo === 'AL_DIA' ? 'Al Día' : 'Pendiente';
                                        btn.className = 'btn-toggle-estado ' + (nuevo === 'AL_DIA' ? 'btn-estado-aldia' : 'btn-estado-pendiente');
                                        actualizarBadgeEstado(res.estado_cliente);
                                    }
                                } else { showToast(res.error || 'Error', 'error'); }
                            }).catch(function() { showToast('Error al actualizar.', 'error'); });
                        });
                    });
                }
            }).catch(function() { showToast('Error al cargar procesos.', 'error'); });
            openModal('modalProcesosCliente');
        });
    });
    var urlParams = new URLSearchParams(window.location.search);
    var verProcesosId = urlParams.get('ver_procesos');
    if (verProcesosId) {
        modalProcesosClienteId = verProcesosId;
        fetch(API_PROCESOS_CLIENTE + '?cliente_id=' + verProcesosId).then(function(r) { return r.json(); }).then(function(data) {
            document.getElementById('modalProcesosClienteNombre').textContent = data.nombre_cliente || ('Cliente #' + verProcesosId);
            var list = document.getElementById('procesosClienteList');
            actualizarBadgeEstado(data.estado_pago || 'AL_DIA');
            var procesos = data.procesos || [];
            if (!procesos.length) {
                list.innerHTML = '<p class="procesos-empty">No hay procesos asignados.</p>';
            } else {
                list.innerHTML = procesos.map(renderProcesoItem).join('');
                list.querySelectorAll('.btn-toggle-estado').forEach(function(b) {
                    b.addEventListener('click', function() {
                        var pcId = parseInt(this.getAttribute('data-id'), 10);
                        var btn = this;
                        fetch(API_PROCESOS_CLIENTE, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'toggle_estado_pago', proceso_cliente_id: pcId }) })
                            .then(function(r) { return r.json(); }).then(function(res) {
                                if (res.ok) {
                                    showToast('Estado actualizado.');
                                    var item = list.querySelector('.proceso-cliente-item[data-id="' + pcId + '"]');
                                    if (item) {
                                        var nuevo = res.estado_pago;
                                        item.setAttribute('data-estado', nuevo);
                                        btn.textContent = nuevo === 'AL_DIA' ? 'Al Día' : 'Pendiente';
                                        btn.className = 'btn-toggle-estado ' + (nuevo === 'AL_DIA' ? 'btn-estado-aldia' : 'btn-estado-pendiente');
                                        actualizarBadgeEstado(res.estado_cliente);
                                    }
                                } else { showToast(res.error || 'Error', 'error'); }
                            }).catch(function() { showToast('Error al actualizar.', 'error'); });
                    });
                });
            }
            openModal('modalProcesosCliente');
            history.replaceState({}, '', 'reportes.php');
        }).catch(function() { showToast('Error al cargar datos.', 'error'); });
    }
    initStatCards();
    initCharts();
})();
</script>
<?php
$extraScripts = ob_get_clean();
require_once __DIR__ . '/../includes/footer.php';
?>
