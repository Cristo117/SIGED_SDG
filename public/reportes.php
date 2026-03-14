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

require_once __DIR__ . '/../controllers/notificaciones.php';
$notificacionesCount = 0;
if (!empty($_SESSION['usuario_id'])) {
    $notificacionesCount = obtenerNotificacionesNoLeidas($conn, $_SESSION['usuario_id']);
}

$loadChartJs = true;
$puedeEliminar = puedeEliminar();
require_once __DIR__ . '/../includes/header.php';
?>

<section class="reportes-section">
    <div class="reportes-header">
        <div>
            <h2 class="section-title">Contabilidad y Procesos</h2>
            <p class="section-subtitle">Gestión de procesos y facturación por cliente</p>
        </div>
        <div class="reportes-actions">
            <button type="button" class="btn-config btn-historial" id="btnHistorialPagos" title="Clientes al día (todos los procesos pagados)">
                <i class="fas fa-history"></i>
                <span>Historial de Pagos</span>
                <span class="historial-badge" id="historialBadge"><?= count($stats['historial_pagos'] ?? []) ?></span>
            </button>
            <button type="button" class="btn-config btn-generar-cobros" id="btnGenerarCobros" title="Genera el cobro de Seguridad Social para todos los clientes con el toggle activado">
                <i class="fas fa-calendar-check"></i>
                <span>Generar cobros del mes</span>
            </button>
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
                <p class="stat-sub">Solo procesos pagados</p>
            </div>
            <div class="stat-icon stat-icon-green">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-card stat-card-2">
            <div class="stat-content">
                <p class="stat-label">Clientes Activos</p>
                <p class="stat-value" data-value="<?= $stats['clientes_activos'] ?>" data-format="number">0</p>
                <p class="stat-sub">Con procesos pendientes</p>
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
        <h3 class="section-title">Cuentas con Procesos Pendientes <span class="client-count">(<?= count($stats['cuentas']) ?>)</span></h3>
        <p class="section-subtitle">Clientes con procesos activos por cobrar</p>
        <div class="table-card">
            <table class="cuentas-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Procesos</th>
                        <th>Total Cobrado</th>
                        <th>Retraso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stats['cuentas'])): ?>
                    <tr>
                        <td colspan="6" class="table-empty-cell">No hay clientes con procesos pendientes de pago</td>
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
                        <td><?= (int)$c['num_procesos'] ?></td>
                        <td class="text-blue">$<?= number_format($totalProc, 0, ',', '.') ?></td>
                        <td><?php $diasRetraso = (int)($c['dias_retraso'] ?? 0); ?>
                            <?php if ($diasRetraso > 0): ?><span class="badge badge-pending" title="Días de retraso en el pago"><?= $diasRetraso ?> día<?= $diasRetraso !== 1 ? 's' : '' ?></span><?php else: ?>—<?php endif; ?></td>
                        <td class="td-acciones">
                            <?php if ($puedeEliminar): ?>
                            <button type="button" class="btn-action btn-eliminar-procesos" title="Eliminar procesos" data-cliente-id="<?= $c['cliente_id'] ?>" data-cliente-nombre="<?= htmlspecialchars($c['nombre']) ?>"><i class="fas fa-trash-alt"></i></button>
                            <?php endif; ?>
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
            <p class="modal-desc">Selecciona un cliente (o trabajador de un empleador) y un proceso para agregar a su cuenta</p>
            <div class="form-group">
                <label for="selectCliente">Cliente o Trabajador</label>
                <select id="selectCliente" class="form-select">
                    <option value="">Seleccione cliente o trabajador</option>
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

<!-- Modal Historial de Pagos -->
<div class="modal-overlay" id="modalHistorialPagos" aria-hidden="true">
    <div class="modal historial-modal" role="dialog" aria-labelledby="modalHistorialTitulo" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalHistorialTitulo" class="modal-title">Historial de Pagos <span class="client-count" id="modalHistorialCount">(0)</span></h2>
            <button type="button" class="modal-close" aria-label="Cerrar" data-close="modalHistorialPagos"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p class="modal-desc">Clientes al día (todos sus procesos pagados). Quedan almacenados aquí y no se muestran como activos.</p>
            <div class="table-card historial-table-wrap">
                <table class="cuentas-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Procesos Pagados</th>
                            <th>Total Cobrado</th>
                            <th>Última Fecha Pago</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="historialPagosBody">
                        <tr><td colspan="6" class="historial-loading">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cerrar" data-close="modalHistorialPagos">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Detalle Cliente (como en módulo Clientes) -->
<div class="modal-overlay" id="modalClienteDetalle" aria-hidden="true">
    <div class="modal cliente-detalle-modal" role="dialog" aria-labelledby="modalClienteDetalleTitulo" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalClienteDetalleTitulo" class="modal-title">Detalle del Cliente</h2>
            <button type="button" class="modal-close" aria-label="Cerrar" data-close="modalClienteDetalle">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="cliente-detalle-body">
                <div class="cliente-detalle-header">
                    <div>
                        <p class="cliente-detalle-nombre" id="detalleNombrePrincipal"></p>
                        <p class="logo-subtitle" id="detalleSubtitulo" style="margin-top:4px;"></p>
                    </div>
                    <span class="badge" id="detalleEstadoBadge"></span>
                </div>
                <div class="profile-card" style="margin:0;">
                    <h3 class="panel-subtitle" style="margin-bottom:10px;">Datos del Cliente</h3>
                    <div class="cliente-detalle-grid">
                        <div class="cliente-detalle-group">
                            <label>Nombres</label>
                            <p id="detalleNombres"></p>
                        </div>
                        <div class="cliente-detalle-group">
                            <label>Apellidos</label>
                            <p id="detalleApellidos"></p>
                        </div>
                        <div class="cliente-detalle-group">
                            <label>Correo</label>
                            <p id="detalleCorreo"></p>
                        </div>
                        <div class="cliente-detalle-group">
                            <label>Tipo Cliente</label>
                            <p id="detalleTipoCliente"></p>
                        </div>
                        <div class="cliente-detalle-group">
                            <label>Tipo documento</label>
                            <p id="detalleTipoDocumento"></p>
                        </div>
                        <div class="cliente-detalle-group">
                            <label>Número documento</label>
                            <p id="detalleNumeroDocumento"></p>
                        </div>
                        <div class="cliente-detalle-group">
                            <label>Fecha Registro</label>
                            <p id="detalleFechaRegistro"></p>
                        </div>
                    </div>
                    <div id="detalleNotasWrapper" style="margin-top:16px; display:none;">
                        <p class="cliente-detalle-notas-title">Información adicional</p>
                        <table class="notas-table">
                            <thead><tr><th>Título</th><th>Valor</th></tr></thead>
                            <tbody id="detalleNotasBody"></tbody>
                        </table>
                    </div>
                </div>
                <div id="detalleEmpleadosWrapper" style="display:none;">
                    <p class="cliente-detalle-empleados-title" id="detalleEmpleadosTitulo"></p>
                    <div class="table-card" style="margin-top:8px;">
                        <table class="clients-management-table">
                            <thead>
                                <tr>
                                    <th>Nombres</th>
                                    <th>Apellidos</th>
                                    <th>Tipo documento</th>
                                    <th>Número documento</th>
                                    <th>Correo</th>
                                    <th>Cargo</th>
                                </tr>
                            </thead>
                            <tbody id="detalleEmpleadosBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="detalleDocumentosWrapper" style="display:none; margin-top:16px;">
                    <p class="cliente-detalle-empleados-title" id="detalleDocumentosTitulo">Documentos de respaldo</p>
                    <div class="documentos-lista-compact" style="margin-top:8px;">
                        <table class="documentos-table" id="detalleDocumentosTable">
                            <thead><tr><th>Nombre</th><th>Archivo</th><th>Fecha</th><th>Descargar</th></tr></thead>
                            <tbody id="detalleDocumentosBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cerrar" data-close="modalClienteDetalle">Cerrar</button>
            <button type="button" id="detalleEditarBtnReportes" class="btn-modal btn-guardar">
                <i class="fas fa-edit"></i> Editar
            </button>
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
            document.getElementById('pieLegend').innerHTML = '<span class="pie-legend-item pie-legend-empleadores"><i class="fas fa-circle"></i> Empleadores: $' + formatNumber(Math.round(emp)) + '</span><span class="pie-legend-item pie-legend-independientes"><i class="fas fa-circle"></i> Independientes: $' + formatNumber(Math.round(ind)) + '</span>';
        }
    }
    function showToast(msg, type) {
        var t = document.getElementById('toast');
        if (t) { t.textContent = msg; t.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success') + ' show'; setTimeout(function() { t.classList.remove('show'); }, 3000); }
    }
    var configData = { procesos: [] };
    var API_CONFIG = 'api_configuracion_valores.php';
    var PUEDE_ELIMINAR = <?= $puedeEliminar ? 'true' : 'false' ?>;

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
            var delBtn = (typeof PUEDE_ELIMINAR !== 'undefined' && PUEDE_ELIMINAR) ? '<button type="button" class="btn-icon btn-delete" title="Eliminar" data-delete="' + p.proceso_id + '"><i class="fas fa-trash"></i></button>' : '';
            return '<div class="proceso-item" data-id="' + p.proceso_id + '">' +
                '<div class="proceso-info"><span class="proceso-nombre">' + escapeHtml(p.nombre) + '</span><span class="proceso-valor">$' + formatted + '</span></div>' +
                '<div class="proceso-actions">' +
                '<button type="button" class="btn-icon btn-edit" title="Editar" data-edit="' + p.proceso_id + '"><i class="fas fa-pen"></i></button>' + delBtn +
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
    function cerrarModalProceso() { closeModal('modalProceso'); openModal('modalConfigurar'); }
    document.getElementById('btnCancelarProceso')?.addEventListener('click', cerrarModalProceso);
    document.getElementById('btnCerrarProceso')?.addEventListener('click', cerrarModalProceso);
    document.getElementById('btnConfigurar')?.addEventListener('click', function() { loadConfig(); openModal('modalConfigurar'); });
    document.getElementById('btnGenerarCobros')?.addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        fetch(API_CONFIG, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'generar_cobros_mensuales' })
        }).then(function(r) { return r.json(); }).then(function(res) {
            btn.disabled = false;
            if (res.ok) {
                showToast('Se generaron cobros para ' + (res.insertados || 0) + ' cliente(s). Recargando...');
                setTimeout(function() { location.reload(); }, 1200);
            } else {
                showToast(res.error || 'Error', 'error');
            }
        }).catch(function() {
            btn.disabled = false;
            showToast('Error al generar cobros.', 'error');
        });
    });
    document.getElementById('btnNuevoProceso')?.addEventListener('click', function() { openAddProceso(); });
    document.getElementById('btnAgregarProceso')?.addEventListener('click', saveProceso);

    var API_ASIGNAR = 'api_asignar_proceso.php';
    var asignarClientesData = [];
    function loadAsignarData() {
        fetch(API_ASIGNAR).then(function(r) { return r.json(); }).then(function(data) {
            asignarClientesData = data.clientes || [];
            var selCliente = document.getElementById('selectCliente');
            var selProceso = document.getElementById('selectProceso');
            var optgroups = [];
            var empleadores = asignarClientesData.filter(function(c) { return (c.tipo_cliente || '').toUpperCase().indexOf('EMPLEADOR') >= 0 && (c.empleados || []).length > 0; });
            var otros = asignarClientesData.filter(function(c) {
                var esEmp = (c.tipo_cliente || '').toUpperCase().indexOf('EMPLEADOR') >= 0;
                return !esEmp || !(c.empleados || []).length;
            });
            var opts = '<option value="">Seleccione cliente o trabajador</option>';
            if (empleadores.length) {
                opts += '<optgroup label="Empleadores y sus trabajadores">';
                empleadores.forEach(function(c) {
                    opts += '<option value="c_' + c.cliente_id + '">' + escapeHtml(c.nombre) + ' (Empleador)</option>';
                    (c.empleados || []).forEach(function(e) {
                        var nomEmp = [e.nombre || '', e.apellidos || ''].filter(Boolean).join(' ') || 'Trabajador';
                    opts += '<option value="e_' + e.empleado_id + '_' + c.cliente_id + '">  └ ' + escapeHtml(nomEmp) + ' (Trabajador)</option>';
                    });
                });
                opts += '</optgroup>';
            }
            if (otros.length) {
                opts += '<optgroup label="' + (empleadores.length ? 'Independientes y empleadores sin trabajadores' : 'Clientes') + '">';
                otros.forEach(function(c) {
                    var tipo = (c.tipo_cliente || '').toUpperCase().indexOf('EMPLEADOR') >= 0 ? 'Empleador' : 'Independiente';
                    opts += '<option value="c_' + c.cliente_id + '">' + escapeHtml(c.nombre) + ' (' + tipo + ')</option>';
                });
                opts += '</optgroup>';
            }
            selCliente.innerHTML = opts;
            selProceso.innerHTML = '<option value="">Seleccione un proceso</option>' + (data.procesos || []).map(function(p) { return '<option value="' + p.proceso_id + '">' + escapeHtml(p.nombre) + ' - $' + parseFloat(p.valor).toLocaleString('es-CO', { minimumFractionDigits: 0 }) + '</option>'; }).join('');
        }).catch(function() { showToast('Error al cargar datos.', 'error'); });
    }
    function submitAsignar() {
        var raw = document.getElementById('selectCliente').value;
        var procesoId = document.getElementById('selectProceso').value;
        if (!raw || !procesoId) { showToast('Selecciona un cliente/trabajador y un proceso.', 'error'); return; }
        var clienteId = 0, empleadoId = null;
        if (raw.indexOf('e_') === 0) {
            var parts = raw.split('_');
            empleadoId = parseInt(parts[1], 10);
            clienteId = parseInt(parts[2], 10);
        } else if (raw.indexOf('c_') === 0) {
            clienteId = parseInt(raw.replace('c_', ''), 10);
        }
        fetch(API_ASIGNAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId, proceso_id: procesoId, empleado_id: empleadoId })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.ok) { showToast('Proceso asignado correctamente.'); closeModal('modalAsignar'); setTimeout(function() { location.reload(); }, 800); }
            else { showToast(res.error || 'Error', 'error'); }
        }).catch(function() { showToast('Error al asignar.', 'error'); });
    }
    document.getElementById('btnAsignar')?.addEventListener('click', function() { loadAsignarData(); openModal('modalAsignar'); });
    document.getElementById('btnAsignarProceso')?.addEventListener('click', submitAsignar);

    var API_HISTORIAL = 'api_historial_pagos.php';
    function loadHistorialPagos() {
        var body = document.getElementById('historialPagosBody');
        body.innerHTML = '<tr><td colspan="6" class="historial-loading">Cargando...</td></tr>';
        openModal('modalHistorialPagos');
        fetch(API_HISTORIAL + '?limite=500&offset=0').then(function(r) { return r.json(); }).then(function(res) {
            if (!res || !res.ok) {
                body.innerHTML = '<tr><td colspan="6" class="historial-error">' + (res && res.error ? escapeHtml(res.error) : 'Error al cargar el historial.') + '</td></tr>';
                return;
            }
            var historial = res.historial || [];
            document.getElementById('modalHistorialCount').textContent = '(' + (res.total || historial.length) + ')';
            if (!historial.length) {
                body.innerHTML = '<tr><td colspan="6" class="table-empty-cell">No hay registros en el historial de pagos</td></tr>';
                return;
            }
            body.innerHTML = historial.map(function(h) {
                var tipoClase = ((h.tipo_cliente || '').toLowerCase().indexOf('empleador') >= 0) ? 'empleador' : 'independiente';
                var totalCobrado = parseFloat(h.total_procesos || 0);
                var fechaPago = '-';
                if (h.ultima_fecha_pago) {
                    try {
                        var d = new Date(h.ultima_fecha_pago);
                        fechaPago = d.getDate().toString().padStart(2, '0') + '/' + (d.getMonth() + 1).toString().padStart(2, '0') + '/' + d.getFullYear() + ' ' + d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
                    } catch (e) {}
                }
                var nom = escapeHtml(h.nombre || '');
                var tipo = escapeHtml(h.tipo_cliente || '');
                var delBtn = (typeof PUEDE_ELIMINAR !== 'undefined' && PUEDE_ELIMINAR) ?
                    '<button type="button" class="btn-action btn-eliminar-procesos" title="Eliminar procesos" data-cliente-id="' + h.cliente_id + '" data-cliente-nombre="' + nom + '"><i class="fas fa-trash-alt"></i></button>' : '';
                return '<tr>' +
                    '<td>' + nom + '</td>' +
                    '<td><span class="badge badge-type badge-' + tipoClase + '"><i class="fas fa-' + (tipoClase === 'empleador' ? 'building' : 'user') + '"></i> ' + tipo + '</span></td>' +
                    '<td>' + (parseInt(h.num_procesos, 10) || 0) + '</td>' +
                    '<td class="text-blue">$' + totalCobrado.toLocaleString('es-CO', { minimumFractionDigits: 0 }) + '</td>' +
                    '<td>' + fechaPago + '</td>' +
                    '<td class="td-acciones">' + delBtn +
                    '<button type="button" class="btn-action btn-procesos" title="Ver procesos y estado" data-cliente-id="' + h.cliente_id + '" data-cliente-nombre="' + nom + '"><i class="fas fa-list"></i></button>' +
                    '</td></tr>';
            }).join('');
            document.getElementById('modalHistorialPagos').querySelectorAll('.btn-eliminar-procesos').forEach(function(btn) {
                btn.addEventListener('click', function() { ejecutarEliminarProcesos(this); });
            });
            document.getElementById('modalHistorialPagos').querySelectorAll('.btn-procesos').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var id = this.getAttribute('data-cliente-id');
                    var nombre = this.getAttribute('data-cliente-nombre');
                    modalProcesosClienteId = id;
                    document.getElementById('modalProcesosClienteNombre').textContent = nombre;
                    closeModal('modalHistorialPagos');
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
                        openModal('modalProcesosCliente');
                    }).catch(function() { showToast('Error al cargar procesos.', 'error'); });
                });
            });
        }).catch(function(err) {
            console.error('Historial error:', err);
            body.innerHTML = '<tr><td colspan="6" class="historial-error">Error al cargar el historial. Verifique la consola del navegador.</td></tr>';
        });
    }
    document.getElementById('btnHistorialPagos')?.addEventListener('click', loadHistorialPagos);

    function ejecutarEliminarProcesos(btn) {
        var id = btn.getAttribute('data-cliente-id');
        var nombre = btn.getAttribute('data-cliente-nombre') || '';
        if (!id) return;
        if (!confirm('¿Eliminar todos los procesos de "' + nombre + '"? El cliente ya no aparecerá en cuentas ni historial.')) return;
        btn.disabled = true;
        fetch(API_PROCESOS_CLIENTE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'eliminar_procesos_cliente', cliente_id: parseInt(id, 10) })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.ok) {
                showToast('Procesos eliminados correctamente.');
                closeModal('modalHistorialPagos');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                btn.disabled = false;
                showToast(res.error || 'Error', 'error');
            }
        }).catch(function() {
            btn.disabled = false;
            showToast('Error al eliminar.', 'error');
        });
    }
    document.querySelectorAll('.reportes-section .btn-eliminar-procesos').forEach(function(btn) {
        btn.addEventListener('click', function() { ejecutarEliminarProcesos(this); });
    });

    function formatoFechaReportes(fechaStr) {
        try {
            var d = new Date(fechaStr);
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            var hh = String(d.getHours()).padStart(2, '0');
            var mm = String(d.getMinutes()).padStart(2, '0');
            return y + '-' + m + '-' + day + ' ' + hh + ':' + mm;
        } catch (e) {
            return fechaStr || '-';
        }
    }
    function abrirDetalleClienteReportes(id) {
        fetch('api_cliente_detalle.php?id=' + encodeURIComponent(id))
            .then(function(r) { return r.json().then(function(data) { return { ok: r.ok, data: data }; }); })
            .then(function(res) {
                if (!res.ok || !res.data.ok) {
                    showToast(res.data && res.data.error ? res.data.error : 'Error al cargar el detalle.', 'error');
                    return;
                }
                var c = res.data.cliente;
                var empleados = res.data.empleados || [];
                document.getElementById('detalleNombrePrincipal').textContent = c.nombres || '(Sin nombre)';
                document.getElementById('detalleSubtitulo').textContent = c.tipo_cliente ? ('Tipo: ' + c.tipo_cliente) : '';
                document.getElementById('detalleNombres').textContent = c.nombres || '-';
                document.getElementById('detalleApellidos').textContent = c.apellidos || '-';
                document.getElementById('detalleCorreo').textContent = c.email || '-';
                document.getElementById('detalleTipoCliente').textContent = c.tipo_cliente || '-';
                document.getElementById('detalleTipoDocumento').textContent = (c.tipo_identificacion || '').trim() || '-';
                document.getElementById('detalleNumeroDocumento').textContent = (c.identificacion || '').trim() || '-';
                document.getElementById('detalleFechaRegistro').textContent = formatoFechaReportes(c.creado_at);
                var estadoBadge = document.getElementById('detalleEstadoBadge');
                if (estadoBadge) {
                    estadoBadge.className = 'badge ' + ((c.estado_pago || '') === 'AL_DIA' ? 'badge-success' : 'badge-pending');
                    estadoBadge.textContent = (c.estado_pago || '') === 'AL_DIA' ? 'Al Día' : 'Pendiente';
                }
                var notasWrapper = document.getElementById('detalleNotasWrapper');
                var notasBody = document.getElementById('detalleNotasBody');
                notasBody.innerHTML = '';
                var notas = c.notas || [];
                if (notas.length) {
                    notas.forEach(function(n) {
                        var tr = document.createElement('tr');
                        var td1 = document.createElement('td');
                        var td2 = document.createElement('td');
                        td1.textContent = n.titulo || '';
                        td2.textContent = n.valor || '';
                        tr.appendChild(td1);
                        tr.appendChild(td2);
                        notasBody.appendChild(tr);
                    });
                    notasWrapper.style.display = '';
                } else {
                    notasWrapper.style.display = 'none';
                }
                var empleadosWrapper = document.getElementById('detalleEmpleadosWrapper');
                var empleadosBody = document.getElementById('detalleEmpleadosBody');
                var empleadosTitulo = document.getElementById('detalleEmpleadosTitulo');
                empleadosBody.innerHTML = '';
                if (empleados.length) {
                    empleadosTitulo.textContent = 'Empleados (' + empleados.length + ')';
                    empleados.forEach(function(e) {
                        var tr = document.createElement('tr');
                        [e.nombres || '-', e.apellidos || '-', (e.tipo_documento || '').trim() || '-', (e.numero_documento || '').trim() || '-', e.email || '-', e.cargo || '-'].forEach(function(txt) {
                            var td = document.createElement('td');
                            td.textContent = txt;
                            tr.appendChild(td);
                        });
                        empleadosBody.appendChild(tr);
                    });
                    empleadosWrapper.style.display = '';
                } else {
                    empleadosWrapper.style.display = 'none';
                }
                var docs = res.data.documentos || [];
                var docsWrapper = document.getElementById('detalleDocumentosWrapper');
                var docsTitulo = document.getElementById('detalleDocumentosTitulo');
                var docsBody = document.getElementById('detalleDocumentosBody');
                if (docsWrapper && docsTitulo && docsBody) {
                    docsBody.innerHTML = '';
                    if (docs.length) {
                        docsTitulo.textContent = 'Documentos de respaldo (' + docs.length + ')';
                        docs.forEach(function(d) {
                            var tr = document.createElement('tr');
                            var fecha = d.creado_at ? d.creado_at.replace('T', ' ').substr(0, 16) : '-';
                            var fa = fecha.split(/[- :]/);
                            if (fa.length >= 5) fecha = fa[2] + '/' + fa[1] + '/' + fa[0] + ' ' + fa[3] + ':' + fa[4];
                            tr.innerHTML = '<td>' + (d.nombre_tipo || '-') + '</td><td>' + (d.nombre_original || '-') + '</td><td>' + fecha + '</td><td><a href="descargar_documento.php?id=' + d.documento_id + '" class="btn-icon btn-edit" title="Descargar" download target="_blank"><i class="fas fa-download"></i></a></td>';
                            docsBody.appendChild(tr);
                        });
                        docsWrapper.style.display = '';
                    } else {
                        docsWrapper.style.display = 'none';
                    }
                }
                var editarBtn = document.getElementById('detalleEditarBtnReportes');
                if (editarBtn) {
                    editarBtn.onclick = function() {
                        window.location.href = 'clientes.php?editar=' + encodeURIComponent(c.cliente_id);
                    };
                }
                openModal('modalClienteDetalle');
            })
            .catch(function() {
                showToast('Error al cargar el detalle.', 'error');
            });
    }
    var API_PROCESOS_CLIENTE = 'api_procesos_cliente.php';
    var modalProcesosClienteId = null;
    function renderProcesoItem(p) {
        var val = parseFloat(p.valor_aplicado);
        var formatted = val.toLocaleString('es-CO', { minimumFractionDigits: 0 });
        var estado = (p.estado_pago || 'PENDIENTE').toUpperCase();
        var isAlDia = estado === 'AL_DIA';
        var btnClass = isAlDia ? 'btn-estado-aldia' : 'btn-estado-pendiente';
        var btnText = isAlDia ? 'Al Día' : 'Pendiente';
        var diasRetraso = parseInt(p.dias_retraso || 0, 10);
        var titulo = escapeHtml(p.proceso_nombre);
        if (p.empleado_nombre) {
            titulo += ' <span class="proceso-para-empleado">(' + escapeHtml(p.empleado_nombre) + ')</span>';
        }
        var retrasoHtml = (!isAlDia && diasRetraso > 0) ? ' <span class="badge badge-pending" title="Días de retraso">' + diasRetraso + ' día' + (diasRetraso !== 1 ? 's' : '') + '</span>' : '';
        return '<div class="proceso-item proceso-cliente-item" data-id="' + p.proceso_cliente_id + '" data-estado="' + estado + '">' +
            '<div class="proceso-info"><span class="proceso-nombre">' + titulo + '</span><span class="proceso-valor">$' + formatted + retrasoHtml + '</span></div>' +
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
