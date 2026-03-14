<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/notificaciones.php';

requireAuth();

if (!esAdministrador()) {
    header('Location: ajustes.php');
    exit;
}

$pageTitle = 'Historial de actividad';
$activePage = 'historial';

$notificacionesCount = obtenerNotificacionesNoLeidas($conn, $_SESSION['usuario_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="panel-section historial-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Historial de actividad</h2>
            <p class="section-subtitle">Registro de acciones realizadas por los usuarios del sistema</p>
        </div>
    </div>

    <div class="historial-toolbar">
        <label class="checkbox-label">
            <input type="checkbox" id="filtroSoloColab">
            <span>Solo colaboradores</span>
        </label>
        <button type="button" class="btn-save" id="btnCargarHistorial">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
        <button type="button" class="btn-save btn-limpiar" id="btnLimpiarHistorial">
            <i class="fas fa-trash-alt"></i> Limpiar historial
        </button>
    </div>

    <div class="table-card">
        <table class="clients-table">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Acción</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody id="tbodyHistorial">
                <tr><td colspan="5" class="text-muted">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div id="toast" class="toast"></div>
</section>

<?php
$extraScripts = <<<'SCRIPT'
<script>
(function() {
    function showToast(message, isError) {
        var toast = document.getElementById('toast');
        if (toast) {
            toast.textContent = message;
            toast.className = 'toast ' + (isError ? 'toast-error' : 'toast-success') + ' show';
            setTimeout(function() { toast.classList.remove('show'); }, 3000);
        }
    }
    function loadHistorial() {
        var tbody = document.getElementById('tbodyHistorial');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Cargando...</td></tr>';
        var soloColab = document.getElementById('filtroSoloColab');
        var url = 'api_historial.php' + (soloColab && soloColab.checked ? '?solo_colaboradores=1' : '');
        fetch(url).then(function(r) { return r.json(); }).then(function(data) {
            if (!data.ok || !data.historial) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Error al cargar</td></tr>';
                return;
            }
            var rows = data.historial || [];
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">No hay registros en el historial</td></tr>';
                return;
            }
            var accionLabels = {
                CREAR_CLIENTE: 'Crear cliente',
                EDITAR_CLIENTE: 'Editar cliente',
                AGREGAR_EMPLEADOS: 'Agregar empleados',
                EDITAR_EMPLEADOS: 'Editar empleados',
                ELIMINAR_CLIENTE: 'Mover a inactivos',
                REACTIVAR_CLIENTE: 'Reactivar cliente',
                REASIGNAR_EMPLEADO: 'Reasignar empleado',
                AGREGAR_PROCESO: 'Agregar proceso',
                EDITAR_PROCESO: 'Editar proceso',
                ELIMINAR_PROCESO: 'Eliminar proceso',
                ELIMINAR_PROCESOS_CLIENTE: 'Eliminar procesos',
                ASIGNAR_PROCESO: 'Asignar proceso',
                CAMBIO_ESTADO_PAGO: 'Cambio estado pago',
                CREAR_USUARIO: 'Agregar usuario',
                TOGGLE_SS: 'Cambio cobro Seg. Social',
                GENERAR_COBROS_SS: 'Generar cobros Seg. Social'
            };
            var accionIcons = {
                CREAR_CLIENTE: 'fa-user-plus',
                EDITAR_CLIENTE: 'fa-user-edit',
                AGREGAR_EMPLEADOS: 'fa-users',
                EDITAR_EMPLEADOS: 'fa-users-cog',
                ELIMINAR_CLIENTE: 'fa-user-minus',
                REACTIVAR_CLIENTE: 'fa-user-check',
                REASIGNAR_EMPLEADO: 'fa-user-tag',
                AGREGAR_PROCESO: 'fa-plus-circle',
                EDITAR_PROCESO: 'fa-edit',
                ELIMINAR_PROCESO: 'fa-trash-alt',
                ELIMINAR_PROCESOS_CLIENTE: 'fa-trash-restore',
                ASIGNAR_PROCESO: 'fa-tasks',
                CAMBIO_ESTADO_PAGO: 'fa-money-check-alt',
                CREAR_USUARIO: 'fa-user-plus',
                TOGGLE_SS: 'fa-calendar-check',
                GENERAR_COBROS_SS: 'fa-calendar-alt'
            };
            function limpiarDescripcion(txt) {
                if (!txt) return '-';
                return txt.replace(/\s*\(ID:\s*\d+\)\s*/gi, '').replace(/\s+/g, ' ').trim();
            }
            function escapeHtml(str) {
                if (!str) return '';
                var d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }
            tbody.innerHTML = rows.map(function(h) {
                var fecha = h.creado_at ? h.creado_at.replace(' ', '<br>') : '-';
                var accionTexto = accionLabels[h.accion] || (h.accion ? h.accion.replace(/_/g, ' ').toLowerCase().replace(/\b\w/g, function(c) { return c.toUpperCase(); }) : '-');
                var icono = accionIcons[h.accion] ? '<i class="fas ' + accionIcons[h.accion] + ' historial-accion-icon"></i>' : '';
                var desc = escapeHtml(limpiarDescripcion(h.descripcion));
                return '<tr><td class="historial-fecha">' + fecha + '</td><td>' + escapeHtml(h.usuario_nombre || '-') + '</td><td><span class="badge badge-' + (h.usuario_rol === 'ADMINISTRADOR' ? 'success' : 'info') + '">' + escapeHtml(h.usuario_rol || '-') + '</span></td><td class="historial-accion"><span class="historial-accion-label">' + icono + accionTexto + '</span></td><td class="historial-desc">' + desc + '</td></tr>';
            }).join('');
        }).catch(function() {
            tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Error al cargar</td></tr>';
        });
    }
    document.getElementById('btnCargarHistorial').addEventListener('click', loadHistorial);
    document.getElementById('filtroSoloColab').addEventListener('change', loadHistorial);
    document.getElementById('btnLimpiarHistorial').addEventListener('click', function() {
        if (!confirm('¿Está seguro de que desea borrar todo el historial? Esta acción no se puede deshacer.')) return;
        var btn = this;
        btn.disabled = true;
        fetch('api_historial.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'limpiar' })
        }).then(function(r) { return r.json(); }).then(function(data) {
            btn.disabled = false;
            if (data.ok) {
                showToast('Historial limpiado correctamente.');
                loadHistorial();
            } else {
                showToast(data.error || 'Error al limpiar', true);
            }
        }).catch(function() {
            btn.disabled = false;
            showToast('Error de conexión.', true);
        });
    });
    loadHistorial();
})();
</script>
SCRIPT;
require_once __DIR__ . '/../includes/footer.php';
?>
