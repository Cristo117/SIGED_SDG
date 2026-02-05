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
                        <td>
                            <a href="cliente_detalle.php?id=<?= $c['cliente_id'] ?>" class="btn-action btn-view" title="Ver detalles"><i class="fas fa-eye"></i></a>
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
    function showToast(msg) {
        var t = document.getElementById('toast');
        if (t) { t.textContent = msg; t.className = 'toast toast-success show'; setTimeout(function() { t.classList.remove('show'); }, 3000); }
    }
    document.getElementById('btnConfigurar')?.addEventListener('click', function() { showToast('Configuración de valores (integrar con backend).'); });
    document.getElementById('btnAsignar')?.addEventListener('click', function() { showToast('Asignar proceso (integrar con backend).'); });
    initStatCards();
    initCharts();
})();
</script>
<?php
$extraScripts = ob_get_clean();
require_once __DIR__ . '/../includes/footer.php';
?>
