<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';

requireAuth();

$puedeEliminar = puedeEliminar();
$pageTitle = 'Clientes';
$activePage = 'clientes';

$filtroTipo = $_GET['tipo'] ?? null;
$filtroPago = $_GET['pago'] ?? null;
$busqueda = $_GET['busqueda'] ?? null;

$clientes = obtenerClientes($filtroTipo, $filtroPago, $busqueda);
$totalClientes = count($clientes);

// Notificaciones
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
            <h2 class="section-title">Gestión de Clientes</h2>
            <p class="section-subtitle">Administra y organiza la información de tus clientes</p>
        </div>
        <div class="section-header-actions">
            <button type="button" class="btn-add-client btn-lista-completa" id="btnListaCompleta">
                <i class="fas fa-users-cog"></i>
                <span>Lista completa Clientes y Trabajadores</span>
            </button>
            <button class="btn-add-client" id="btnAddClient">
                <i class="fas fa-plus"></i>
                <span>Agregar Cliente</span>
            </button>
        </div>
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
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Tipo documento</th>
                    <th>Número documento</th>
                    <th>Tipo de cliente</th>
                    <th>Correo</th>
                    <th>Empleados</th>
                    <th>Documentos</th>
                    <th>Estado de Pago</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="clientsTableBody">
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="11" class="table-empty-cell">No hay clientes que coincidan con los filtros</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $c):
                        // Preferir columnas separadas si existen
                        $nombres = trim($c['nombre'] ?? '');
                        $apellidos = trim($c['apellidos'] ?? '');

                        // Fallback: si no hay apellidos pero el nombre completo trae todo
                        if ($apellidos === '' && strpos($nombres, ' ') !== false) {
                            $partesNombre = preg_split('/\s+/', $nombres);
                            if (is_array($partesNombre) && count($partesNombre) > 1) {
                                $apellidos = array_pop($partesNombre);
                                $nombres = implode(' ', $partesNombre);
                            }
                        }
                        $tipoDoc = trim($c['tipo_identificacion'] ?? '');
                        $numDoc  = trim($c['identificacion'] ?? '');
                        $tipoClase = strtolower($c['tipo_cliente']) === 'empleador' ? 'empleador' : 'independiente';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($nombres ?: $c['nombre']) ?></td>
                            <td><?= htmlspecialchars($apellidos ?: '-') ?></td>
                            <td><?= htmlspecialchars($tipoDoc ?: '-') ?></td>
                            <td><?= htmlspecialchars($numDoc ?: '-') ?></td>
                            <td>
                                <span class="badge badge-type badge-<?= $tipoClase ?>">
                                    <i class="fas fa-<?= $tipoClase === 'empleador' ? 'building' : 'user' ?>"></i>
                                    <?= htmlspecialchars($c['tipo_cliente']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['email'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($c['num_empleados']) && $c['num_empleados'] > 0): ?>
                                    <i class="fas fa-user"></i> <?= (int)$c['num_empleados'] ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($c['num_documentos']) && $c['num_documentos'] > 0): ?>
                                    <i class="fas fa-file-alt"></i> <?= (int)$c['num_documentos'] ?>
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
                                <a href="reportes.php?ver_procesos=<?= $c['cliente_id'] ?>" class="btn-action btn-procesos" title="Procesos y estado">
                                    <i class="fas fa-list"></i>
                                </a>
                                <button type="button" class="btn-action btn-edit btn-edit-cliente" title="Editar" data-id="<?= $c['cliente_id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php $cobrarSS = !empty($c['cobrar_seguridad_social_mensual']); ?>
                                <button type="button" class="toggle-ss <?= $cobrarSS ? 'toggle-ss-on' : 'toggle-ss-off' ?>" title="Cobrar Seg. Social mensual: <?= $cobrarSS ? 'Activo' : 'Inactivo' ?>" data-id="<?= $c['cliente_id'] ?>" data-active="<?= $cobrarSS ? '1' : '0' ?>">
                                    <span class="toggle-ss-track"><span class="toggle-ss-handle"></span></span>
                                </button>
                                <?php if (puedeEliminar()): ?>
                                <button type="button" class="btn-action btn-delete" title="Mover a inactivos"
                                    data-id="<?= $c['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="toast" id="toastClientes" role="status" aria-live="polite"></div>

<!-- Modal Lista completa Clientes y Trabajadores -->
<div class="modal-overlay" id="modalListaCompleta" aria-hidden="true">
    <div class="modal modal-lista-completa" role="dialog" aria-labelledby="modalListaCompletaTitulo" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalListaCompletaTitulo" class="modal-title">Clientes y Trabajadores</h2>
            <button type="button" class="modal-close" aria-label="Cerrar" data-close="modalListaCompleta">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div id="listaCompletaContenido">
                <p class="lista-completa-cargando"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cerrar" data-close="modalListaCompleta">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Detalle Cliente (visualización) -->
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
                                    <th>Información adicional</th>
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
            <button type="button" id="detalleEditarBtn" class="btn-modal btn-guardar">
                <i class="fas fa-edit"></i> Editar
            </button>
        </div>
    </div>
</div>

<!-- Modal Agregar/Editar Cliente (mismo formulario para ambos) -->
<div class="modal-overlay" id="modalCliente" aria-hidden="true">
    <div class="modal cliente-modal modal-cliente-wide" role="dialog" aria-labelledby="modalClienteTitulo" aria-modal="true">
        <div class="modal-header">
            <h2 id="modalClienteTitulo" class="modal-title">Agregar Cliente</h2>
            <button type="button" class="modal-close" aria-label="Cerrar" data-close="modalCliente">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cliente_id_edit" value="">
            <div class="modal-cliente-grid" id="modalClienteGrid">
            <div class="modal-cliente-form-col">
            <div class="form-row">
                <div class="form-group">
                    <label for="cliente_nombre">Nombres *</label>
                    <input type="text" id="cliente_nombre" required>
                </div>
                <div class="form-group">
                    <label for="cliente_apellidos">Apellidos</label>
                    <input type="text" id="cliente_apellidos">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cliente_email">Correo Electrónico</label>
                    <input type="email" id="cliente_email">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cliente_tipo_identificacion">Tipo Documento</label>
                    <select id="cliente_tipo_identificacion">
                        <option value="">Seleccione</option>
                        <option value="CC">Cédula</option>
                        <option value="NIT">NIT</option>
                        <option value="Pasaporte">Pasaporte</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cliente_identificacion">Número Documento</label>
                    <input type="text" id="cliente_identificacion" inputmode="numeric" pattern="[0-9]*"
                           oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cliente_tipo_cliente">Tipo de Cliente</label>
                    <select id="cliente_tipo_cliente">
                        <option value="INDEPENDIENTE">Independiente</option>
                        <option value="EMPLEADOR">Empleador</option>
                    </select>
                </div>
                <div class="form-group form-group-check">
                    <label class="checkbox-label">
                        <input type="checkbox" id="cliente_cobrar_ss" checked>
                        <span>Cobrar Seguridad Social mensualmente</span>
                    </label>
                </div>
            </div>

            <hr class="form-separator">
            <div class="form-row">
                <div class="form-group form-group-full notas-section" id="modalNotasCliente">
                    <label>Información adicional</label>
                    <div class="notas-lista" id="listaNotasClienteModal">
                        <div class="nota-fila">
                            <input type="text" placeholder="Título" data-nota-titulo>
                            <input type="text" placeholder="Valor" data-nota-valor>
                            <button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn-add-nota" id="btnAddNotaClienteModal">
                        <i class="fas fa-plus"></i> Agregar nota
                    </button>
                </div>
            </div>
            </div>
            <div id="modalEmpleadosCol" class="modal-empleados-col" style="display:none;">
                <h3 class="panel-subtitle"><i class="fas fa-users"></i> Empleados</h3>
                <div id="listaEmpleadosNuevo"></div>
                <button type="button" class="btn-add-empleado" id="btnAddEmpleadoNuevo">
                    <i class="fas fa-plus"></i> Agregar otro empleado
                </button>
            </div>

            <div id="modalDocumentosCol" class="modal-documentos-col documentos-seccion documentos-compact" style="display:none;">
                <h3 class="panel-subtitle documentos-subtitle"><i class="fas fa-file-alt"></i> Documentos</h3>
                <form id="formDocumentoModal" class="documento-upload-form documento-upload-compact">
                    <input type="hidden" name="cliente_id" id="doc_cliente_id" value="">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <div class="documento-row-compact">
                        <input type="text" id="doc_nombre_tipo" name="nombre_tipo" placeholder="Nombre (ej: Cédula, RUT)" maxlength="100" class="doc-input-nombre">
                        <label class="doc-file-wrap"><span class="doc-file-label">Elegir archivo</span><input type="file" id="doc_archivo" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></label>
                        <button type="submit" class="btn-doc-subir" id="btnSubirDocModal"><i class="fas fa-plus"></i> Agregar</button>
                    </div>
                </form>
                <div id="listaDocumentosModal" class="documentos-lista documentos-lista-compact">
                    <p class="text-muted">Sin documentos.</p>
                </div>
            </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cerrar" data-close="modalCliente">Cancelar</button>
            <button type="button" class="btn-modal btn-guardar" id="btnGuardarCliente">Guardar Cliente</button>
        </div>
    </div>
</div>

<?php
$editarId = isset($_GET['editar']) ? (int)$_GET['editar'] : 0;
$puedeEliminarJs = $puedeEliminar ? 'true' : 'false';
$extraScripts = <<<SCRIPT
<script>
document.addEventListener('DOMContentLoaded', function () {
    var PUEDE_ELIMINAR_EMPLEADO = $puedeEliminarJs;
(function () {
    var editarIdOnLoad = $editarId;
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.add('show');
            m.setAttribute('aria-hidden', 'false');
        }
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.remove('show');
            m.setAttribute('aria-hidden', 'true');
        }
    }

    function showToastClientes(msg, type) {
        const t = document.getElementById('toastClientes');
        if (!t) return;
        t.textContent = msg;
        t.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success') + ' show';
        setTimeout(function () {
            t.classList.remove('show');
        }, 3000);
    }

    document.querySelectorAll('[data-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeModal(this.getAttribute('data-close'));
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (ov) {
        ov.addEventListener('click', function (e) {
            if (e.target === ov) {
                closeModal(ov.id);
            }
        });
    });

    let nuevoClienteId = null;

    function actualizarVisibilidadEmpleados() {
        const tipoCliente = document.getElementById('cliente_tipo_cliente');
        const empleadosCol = document.getElementById('modalEmpleadosCol');
        const grid = document.getElementById('modalClienteGrid');
        const esEmpleador = tipoCliente && tipoCliente.value === 'EMPLEADOR';
        if (empleadosCol) empleadosCol.style.display = esEmpleador ? 'block' : 'none';
        if (grid) grid.classList.toggle('form-solo', !esEmpleador);
        if (esEmpleador) {
            const lista = document.getElementById('listaEmpleadosNuevo');
            if (lista && lista.children.length === 0) reiniciarEmpleadosNuevo();
        }
    }

    function abrirModalCliente(modo) {
        const idInput = document.getElementById('cliente_id_edit');
        const titulo = document.getElementById('modalClienteTitulo');
        const nombre = document.getElementById('cliente_nombre');
        const apellidos = document.getElementById('cliente_apellidos');
        const email = document.getElementById('cliente_email');
        const tipoIdent = document.getElementById('cliente_tipo_identificacion');
        const ident = document.getElementById('cliente_identificacion');
        const tipoCliente = document.getElementById('cliente_tipo_cliente');
        const listaNotas = document.getElementById('listaNotasClienteModal');

        if (modo === 'add') {
            idInput.value = '';
            titulo.textContent = 'Agregar Cliente';
            if (nombre) nombre.value = '';
            if (apellidos) apellidos.value = '';
            if (email) email.value = '';
            if (tipoIdent) tipoIdent.value = '';
            if (ident) ident.value = '';
            if (tipoCliente) tipoCliente.value = 'INDEPENDIENTE';
            const chkSSAdd = document.getElementById('cliente_cobrar_ss');
            if (chkSSAdd) chkSSAdd.checked = true;
            listaNotas.innerHTML = '<div class="nota-fila"><input type="text" placeholder="Título" data-nota-titulo><input type="text" placeholder="Valor" data-nota-valor><button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button></div>';
            actualizarVisibilidadEmpleados();
            var docCol = document.getElementById('modalDocumentosCol');
            if (docCol) docCol.style.display = 'none';
            document.getElementById('doc_cliente_id').value = '';
        }
        bindNotasRemove();
        openModal('modalCliente');
    }

    function escapeHtmlAttr(s) {
        if (!s) return '';
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function cargarClienteParaEditar(id) {
        fetch('api_cliente_detalle.php?id=' + encodeURIComponent(id))
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
                if (!res.ok || !res.data.ok) {
                    showToastClientes(res.data && res.data.error ? res.data.error : 'Error al cargar el cliente.', 'error');
                    return;
                }
                const c = res.data.cliente;
                document.getElementById('cliente_id_edit').value = c.cliente_id;
                document.getElementById('modalClienteTitulo').textContent = 'Editar Cliente';
                document.getElementById('cliente_nombre').value = c.nombres || '';
                document.getElementById('cliente_apellidos').value = c.apellidos || '';
                document.getElementById('cliente_email').value = c.email || '';
                document.getElementById('cliente_tipo_identificacion').value = (c.tipo_identificacion || '').trim();
                document.getElementById('cliente_identificacion').value = (c.identificacion || '').trim();
                document.getElementById('cliente_tipo_cliente').value = c.tipo_cliente || 'INDEPENDIENTE';
                const chkSS = document.getElementById('cliente_cobrar_ss');
                if (chkSS) chkSS.checked = c.cobrar_seguridad_social_mensual !== false;
                const notas = c.notas || [];
                const listaNotas = document.getElementById('listaNotasClienteModal');
                listaNotas.innerHTML = '';
                (notas.length ? notas : [{ titulo: '', valor: '' }]).forEach(function (n) {
                    const fila = document.createElement('div');
                    fila.className = 'nota-fila';
                    fila.innerHTML = '<input type="text" placeholder="Título" data-nota-titulo value="' + escapeHtmlAttr(n.titulo || '') + '"><input type="text" placeholder="Valor" data-nota-valor value="' + escapeHtmlAttr(n.valor || '') + '"><button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button>';
                    listaNotas.appendChild(fila);
                });
                bindNotasRemove();
                actualizarVisibilidadEmpleados();
                poblarlosEmpleadosParaEditar(res.data.empleados || []);
                var docCol = document.getElementById('modalDocumentosCol');
                if (docCol) { docCol.style.display = 'block'; }
                document.getElementById('doc_cliente_id').value = c.cliente_id;
                cargarDocumentosModal(c.cliente_id);
                openModal('modalCliente');
            })
            .catch(function () { showToastClientes('Error al cargar el cliente.', 'error'); });
    }

    function cargarDocumentosModal(clienteId) {
        var lista = document.getElementById('listaDocumentosModal');
        if (!lista || !clienteId) return;
        fetch('api_documentos_cliente.php?cliente_id=' + clienteId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok || !data.documentos) {
                    lista.innerHTML = '<p class="text-muted">Error al cargar.</p>';
                    return;
                }
                var docs = data.documentos;
                if (docs.length === 0) {
                    lista.innerHTML = '<p class="text-muted">Sin documentos.</p>';
                    return;
                }
                function esc(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
                var html = '<table class="documentos-table"><thead><tr><th>Nombre</th><th>Archivo</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>';
                docs.forEach(function (d) {
                    var fecha = d.creado_at ? d.creado_at.replace('T', ' ').substr(0, 16) : '-';
                    var fa = fecha.split(/[- :]/);
                    if (fa.length >= 5) fecha = fa[2] + '/' + fa[1] + '/' + fa[0] + ' ' + fa[3] + ':' + fa[4];
                    html += '<tr><td>' + esc(d.nombre_tipo) + '</td><td>' + esc(d.nombre_original) + '</td><td>' + fecha + '</td><td>' +
                        '<a href="descargar_documento.php?id=' + d.documento_id + '" class="btn-icon btn-edit" title="Descargar" download target="_blank"><i class="fas fa-download"></i></a> ' +
                        '<button type="button" class="btn-icon btn-delete btn-eliminar-doc-modal" data-id="' + d.documento_id + '" data-cliente="' + clienteId + '" title="Eliminar"><i class="fas fa-trash-alt"></i></button></td></tr>';
                });
                html += '</tbody></table>';
                lista.innerHTML = html;
                lista.querySelectorAll('.btn-eliminar-doc-modal').forEach(function (btn) {
                    btn.onclick = function () {
                        var id = this.getAttribute('data-id');
                        var cid = this.getAttribute('data-cliente');
                        if (!confirm('¿Eliminar este documento?')) return;
                        btn.disabled = true;
                        fetch('api_documentos_cliente.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ action: 'eliminar', documento_id: parseInt(id, 10) })
                        }).then(function (r) { return r.json(); }).then(function (res) {
                            if (res.ok) cargarDocumentosModal(cid); else { showToastClientes(res.error || 'Error', 'error'); }
                            btn.disabled = false;
                        }).catch(function () { btn.disabled = false; showToastClientes('Error de conexión', 'error'); });
                    };
                });
            })
            .catch(function () { lista.innerHTML = '<p class="text-muted">Error al cargar.</p>'; });
    }

    function mostrarSeccionDocumentos(clienteId) {
        document.getElementById('cliente_id_edit').value = clienteId;
        var docCol = document.getElementById('modalDocumentosCol');
        if (docCol) docCol.style.display = 'block';
        document.getElementById('doc_cliente_id').value = clienteId;
        cargarDocumentosModal(clienteId);
    }

    function bindFormDocumentoModal() {
        var form = document.getElementById('formDocumentoModal');
        if (!form || form.dataset.bound) return;
        form.dataset.bound = '1';
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var clienteId = document.getElementById('doc_cliente_id').value;
            var nombreTipo = document.getElementById('doc_nombre_tipo').value.trim();
            var archivo = document.getElementById('doc_archivo').files[0];
            if (!clienteId || !nombreTipo || !archivo) {
                showToastClientes('Complete el nombre del documento y seleccione un archivo.', 'error');
                return;
            }
            var btn = document.getElementById('btnSubirDocModal');
            var fd = new FormData(form);
            btn.disabled = true;
            fetch('api_documentos_cliente.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    btn.disabled = false;
                    if (res.ok) {
                        document.getElementById('doc_nombre_tipo').value = '';
                        document.getElementById('doc_archivo').value = '';
                        cargarDocumentosModal(clienteId);
                        showToastClientes('Documento subido correctamente.', 'success');
                    } else {
                        showToastClientes(res.error || 'Error al subir', 'error');
                    }
                })
                .catch(function () { btn.disabled = false; showToastClientes('Error de conexión', 'error'); });
        });
    }

    function getEmpleadoNotasHtml() {
        return '<div class="form-row empleado-notas-row"><div class="form-group form-group-full"><label>Información adicional</label><div class="empleado-notas-lista"><div class="empleado-nota-fila"><input type="text" placeholder="Título" data-emp-nota-titulo><input type="text" placeholder="Valor" data-emp-nota-valor><button type="button" class="btn-remove-emp-nota" title="Quitar"><i class="fas fa-times"></i></button></div></div><button type="button" class="btn-add-emp-nota"><i class="fas fa-plus"></i> Agregar nota</button></div></div>';
    }

    function getEmpleadoItemHtml(num) {
        var btnRemove = PUEDE_ELIMINAR_EMPLEADO ? '<button type="button" class="btn-remove-empleado" title="Quitar empleado"><i class="fas fa-times"></i></button>' : '';
        return '<div class="empleado-item-header"><span class="empleado-num"><i class="fas fa-chevron-right empleado-toggle"></i> Empleado ' + num + '</span>' + btnRemove + '</div><div class="empleado-fields"><div class="form-row"><div class="form-group"><label>Nombres *</label><input type="text" name="empleado_nombre_nuevo[]" placeholder="Nombres"></div><div class="form-group"><label>Apellidos</label><input type="text" name="empleado_apellidos_nuevo[]" placeholder="Apellidos"></div></div><div class="form-row"><div class="form-group"><label>Correo</label><input type="email" name="empleado_email_nuevo[]" placeholder="correo@ejemplo.com"></div></div><div class="form-row"><div class="form-group"><label>Tipo Documento</label><select name="empleado_tipo_documento_nuevo[]"><option value="">Seleccione</option><option value="CC">Cédula</option><option value="NIT">NIT</option><option value="Pasaporte">Pasaporte</option></select></div><div class="form-group"><label>Número Documento</label><input type="text" name="empleado_numero_documento_nuevo[]" placeholder="Número" inputmode="numeric" pattern="[0-9]*" oninput="this.value=this.value.replace(/[^0-9]/g,\'\')"></div></div><div class="form-row"><div class="form-group"><label>Cargo</label><input type="text" name="empleado_cargo_nuevo[]" placeholder="Cargo o puesto"></div></div>' + getEmpleadoNotasHtml() + '</div>';
    }

    function collectNotasEmpleado(item) {
        const pares = [];
        (item.querySelectorAll('.empleado-nota-fila') || []).forEach(function (fila) {
            const titulo = (fila.querySelector('[data-emp-nota-titulo]')?.value || '').trim();
            const valor = (fila.querySelector('[data-emp-nota-valor]')?.value || '').trim();
            if (titulo || valor) pares.push({ titulo: titulo, valor: valor });
        });
        return pares;
    }

    function poblarlosEmpleadosParaEditar(empleados) {
        const lista = document.getElementById('listaEmpleadosNuevo');
        if (!lista) return;
        lista.innerHTML = '';
        if (empleados.length === 0) {
            const base = document.createElement('div');
            base.className = 'empleado-item nuevo-empleado';
            base.innerHTML = '<input type="hidden" name="empleado_id[]" value="">' + getEmpleadoItemHtml(1);
            lista.appendChild(base);
        } else {
                empleados.forEach(function (emp, idx) {
                const base = document.createElement('div');
                base.className = 'empleado-item nuevo-empleado' + (idx > 0 ? ' collapsed' : '');
                base.innerHTML = '<input type="hidden" name="empleado_id[]" value="' + escapeHtmlAttr(String(emp.empleado_id || '')) + '">' + getEmpleadoItemHtml(idx + 1);
                lista.appendChild(base);
                const item = lista.lastElementChild;
                item.querySelector('input[name="empleado_nombre_nuevo[]"]').value = emp.nombres || '';
                item.querySelector('input[name="empleado_apellidos_nuevo[]"]').value = emp.apellidos || '';
                item.querySelector('input[name="empleado_email_nuevo[]"]').value = emp.email || '';
                item.querySelector('select[name="empleado_tipo_documento_nuevo[]"]').value = emp.tipo_documento || '';
                item.querySelector('input[name="empleado_numero_documento_nuevo[]"]').value = emp.numero_documento || '';
                item.querySelector('input[name="empleado_cargo_nuevo[]"]').value = emp.cargo || '';
                const notasLista = item.querySelector('.empleado-notas-lista');
                if (notasLista) {
                    notasLista.innerHTML = '';
                    const notas = emp.notas || [];
                    if (notas.length > 0) {
                        notas.forEach(function (n) {
                            const fila = document.createElement('div');
                            fila.className = 'empleado-nota-fila';
                            fila.innerHTML = '<input type="text" placeholder="Título" data-emp-nota-titulo value="' + escapeHtmlAttr(n.titulo || n.etiqueta || '') + '"><input type="text" placeholder="Valor" data-emp-nota-valor value="' + escapeHtmlAttr(n.valor || '') + '"><button type="button" class="btn-remove-emp-nota" title="Quitar"><i class="fas fa-times"></i></button>';
                            notasLista.appendChild(fila);
                        });
                    }
                    const f = document.createElement('div');
                    f.className = 'empleado-nota-fila';
                    f.innerHTML = '<input type="text" placeholder="Título" data-emp-nota-titulo><input type="text" placeholder="Valor" data-emp-nota-valor><button type="button" class="btn-remove-emp-nota" title="Quitar"><i class="fas fa-times"></i></button>';
                    notasLista.appendChild(f);
                }
            });
        }
        bindRemoveEmpleadoNuevo();
        bindEmpleadoAccordion();
        bindEmpNotas();
        bindEmpleadoNombreChange();
        reindexarEmpleadosNuevo();
        expandirSoloEmpleado(lista.querySelector('.empleado-item'));
    }

    function bindEmpNotas() {
        document.querySelectorAll('#listaEmpleadosNuevo .btn-remove-emp-nota').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                const fila = this.closest('.empleado-nota-fila');
                const lista = fila?.closest('.empleado-notas-lista');
                if (fila && lista && lista.querySelectorAll('.empleado-nota-fila').length > 1) fila.remove();
                else if (fila) { fila.querySelectorAll('input').forEach(function (i) { i.value = ''; }); }
            });
        });
        document.querySelectorAll('#listaEmpleadosNuevo .btn-add-emp-nota').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                const lista = this.closest('.empleado-item')?.querySelector('.empleado-notas-lista');
                if (!lista) return;
                const f = document.createElement('div');
                f.className = 'empleado-nota-fila';
                f.innerHTML = '<input type="text" placeholder="Título" data-emp-nota-titulo><input type="text" placeholder="Valor" data-emp-nota-valor><button type="button" class="btn-remove-emp-nota" title="Quitar"><i class="fas fa-times"></i></button>';
                lista.appendChild(f);
                bindEmpNotas();
            });
        });
    }

    function bindNotasRemove() {
        document.querySelectorAll('#listaNotasClienteModal .btn-remove-nota').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                const fila = this.closest('.nota-fila');
                const lista = document.getElementById('listaNotasClienteModal');
                if (fila && lista) {
                    if (lista.querySelectorAll('.nota-fila').length > 1) {
                        fila.remove();
                    } else {
                        fila.querySelectorAll('input').forEach(function (i) { i.value = ''; });
                    }
                }
            });
        });
    }

    document.getElementById('cliente_tipo_cliente')?.addEventListener('change', actualizarVisibilidadEmpleados);

    bindFormDocumentoModal();

    document.getElementById('btnAddClient')?.addEventListener('click', function () {
        abrirModalCliente('add');
    });

    document.getElementById('btnListaCompleta')?.addEventListener('click', function () {
        const contenido = document.getElementById('listaCompletaContenido');
        contenido.innerHTML = '<p class="lista-completa-cargando"><i class="fas fa-spinner fa-spin"></i> Cargando...</p>';
        openModal('modalListaCompleta');
        fetch('api_clientes_lista_completa.php')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok || !data.clientes) {
                    contenido.innerHTML = '<p class="lista-completa-vacio">Error al cargar los datos.</p>';
                    return;
                }
                let html = '';
                data.clientes.forEach(function (c) {
                    const estadoClase = (c.estado_pago || '') === 'AL_DIA' ? 'al-dia' : 'pendiente';
                    const estadoTexto = (c.estado_pago || '') === 'AL_DIA' ? 'Al Día' : 'Pendiente';
                    const notasCl = (c.notas || []).map(function (n) { return (n.titulo || n.etiqueta || '') + ': ' + (n.valor || ''); }).join('; ');
                    html += '<div class="lista-completa-grupo">';
                    html += '<div class="lista-completa-cliente">';
                    html += '<div><span><i class="fas fa-user-tie"></i> ' + escapeHtmlAttr(c.nombre) + '</span>';
                    if (c.email) html += '<small style="opacity:0.9;font-weight:400;"> ' + escapeHtmlAttr(c.email) + '</small>';
                    html += '</div>';
                    html += '<span class="estado-pago ' + estadoClase + '">' + estadoTexto + '</span>';
                    html += '</div>';
                    if (notasCl) html += '<div class="lista-completa-cliente-notas">' + escapeHtmlAttr(notasCl) + '</div>';
                    if (c.empleados && c.empleados.length > 0) {
                        c.empleados.forEach(function (e) {
                            const nombreEmp = (e.nombres || '') + (e.apellidos ? ' ' + e.apellidos : '');
                            const docEmp = ((e.tipo_documento || '') + ' ' + (e.numero_documento || '')).trim() || '-';
                            const notasEmp = (e.notas || []).map(function (n) { return (n.titulo || n.etiqueta || '') + ': ' + (n.valor || ''); }).join('; ');
                            html += '<div class="lista-completa-empleado">';
                            html += '<span class="emp-nombre"><i class="fas fa-user"></i> ' + escapeHtmlAttr(nombreEmp) + '</span>';
                            html += '<span class="emp-doc">Doc: ' + escapeHtmlAttr(docEmp) + '</span>';
                            html += '<span class="emp-email">' + escapeHtmlAttr(e.email || '-') + '</span>';
                            html += '<span class="emp-cargo">' + escapeHtmlAttr(e.cargo || '-') + '</span>';
                            if (notasEmp) html += '<span class="emp-notas" title="' + escapeHtmlAttr(notasEmp) + '">' + escapeHtmlAttr(notasEmp) + '</span>';
                            html += '</div>';
                        });
                    } else {
                        html += '<div class="lista-completa-sin-empleados">Sin trabajadores registrados</div>';
                    }
                    html += '</div>';
                });
                contenido.innerHTML = html || '<p class="lista-completa-vacio">No hay clientes registrados.</p>';
            })
            .catch(function () {
                contenido.innerHTML = '<p class="lista-completa-vacio">Error al cargar los datos.</p>';
            });
    });

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-edit-cliente');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            if (id) cargarClienteParaEditar(id);
        }
    });

    document.getElementById('btnAddNotaClienteModal')?.addEventListener('click', function () {
        const lista = document.getElementById('listaNotasClienteModal');
        if (!lista) return;
        const fila = document.createElement('div');
        fila.className = 'nota-fila';
        fila.innerHTML = '<input type="text" placeholder="Título" data-nota-titulo><input type="text" placeholder="Valor" data-nota-valor><button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button>';
        lista.appendChild(fila);
        bindNotasRemove();
    });

    function collectNotasCliente() {
        const lista = document.getElementById('listaNotasClienteModal');
        if (!lista) return [];
        const pares = [];
        lista.querySelectorAll('.nota-fila').forEach(function (fila) {
            const titulo = (fila.querySelector('input[data-nota-titulo]')?.value || '').trim();
            const valor = (fila.querySelector('input[data-nota-valor]')?.value || '').trim();
            if (titulo || valor) {
                pares.push({ titulo: titulo, valor: valor });
            }
        });
        return pares;
    }

    function guardarCliente() {
        const clienteId = document.getElementById('cliente_id_edit')?.value || '';
        const esEdicion = clienteId !== '';
        const nombre = (document.getElementById('cliente_nombre')?.value || '').trim();
        const apellidos = (document.getElementById('cliente_apellidos')?.value || '').trim();
        const email = (document.getElementById('cliente_email')?.value || '').trim();
        const tipoIdent = (document.getElementById('cliente_tipo_identificacion')?.value || '').trim();
        const ident = (document.getElementById('cliente_identificacion')?.value || '').trim();
        const tipoCliente = document.getElementById('cliente_tipo_cliente')?.value || 'INDEPENDIENTE';
        const notasCliente = collectNotasCliente();

        if (!nombre) {
            showToastClientes('Los nombres del cliente son obligatorios.', 'error');
            return;
        }

        const cobrarSS = document.getElementById('cliente_cobrar_ss');
        const body = {
            nombre: nombre,
            apellidos: apellidos,
            email: email,
            tipo_identificacion: tipoIdent,
            identificacion: ident,
            tipo_cliente: tipoCliente,
            cobrar_seguridad_social_mensual: cobrarSS && cobrarSS.checked,
            notas_cliente: notasCliente
        };
        if (esEdicion) {
            body.action = 'actualizar';
            body.cliente_id = parseInt(clienteId, 10);
        } else {
            body.action = 'crear';
        }

        fetch('api_clientes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        })
        .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
        .then(function (res) {
            if (!res.ok || !res.data.ok) {
                showToastClientes(res.data && res.data.error ? res.data.error : 'Error al guardar el cliente.', 'error');
                return;
            }
            const cId = res.data.cliente_id || clienteId;
            nuevoClienteId = esEdicion ? null : cId;
            showToastClientes(esEdicion ? 'Cliente actualizado correctamente.' : 'Cliente creado correctamente.', 'success');

            if (tipoCliente === 'EMPLEADOR' && cId) {
                const items = document.querySelectorAll('#listaEmpleadosNuevo .empleado-item');
                const empleados = [];
                items.forEach(function (item) {
                    const nombre = (item.querySelector('input[name="empleado_nombre_nuevo[]"]')?.value || '').trim();
                    if (!nombre) return;
                    const idInput = item.querySelector('input[name="empleado_id[]"]');
                    const empId = (idInput && idInput.value) ? parseInt(idInput.value, 10) : null;
                    const datos = {
                        empleado_id: empId || undefined,
                        nombre: nombre,
                        apellidos: (item.querySelector('input[name="empleado_apellidos_nuevo[]"]')?.value || '').trim(),
                        email: (item.querySelector('input[name="empleado_email_nuevo[]"]')?.value || '').trim(),
                        tipo_documento: (item.querySelector('select[name="empleado_tipo_documento_nuevo[]"]')?.value || '').trim(),
                        numero_documento: (item.querySelector('input[name="empleado_numero_documento_nuevo[]"]')?.value || '').trim(),
                        cargo: (item.querySelector('input[name="empleado_cargo_nuevo[]"]')?.value || '').trim(),
                        notas: collectNotasEmpleado(item)
                    };
                    empleados.push(datos);
                });
                const bodyEmp = esEdicion
                    ? { action: 'actualizar_empleados', cliente_id: parseInt(cId, 10), empleados: empleados }
                    : { action: 'agregar_empleados', cliente_id: parseInt(cId, 10), empleados: empleados.map(function(e) { return { nombre: e.nombre, apellidos: e.apellidos, email: e.email, tipo_documento: e.tipo_documento, numero_documento: e.numero_documento, cargo: e.cargo, notas: e.notas || [] }; }) };
                if (empleados.length > 0 || esEdicion) {
                    fetch('api_clientes.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(bodyEmp)
                    })
                    .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                    .then(function (resEmp) {
                        if (resEmp.ok && resEmp.data.ok) showToastClientes(esEdicion ? 'Cliente y empleados actualizados correctamente.' : 'Cliente y empleados guardados correctamente.', 'success');
                        mostrarSeccionDocumentos(cId);
                    })
                    .catch(function () {
                        mostrarSeccionDocumentos(cId);
                    });
                } else if (!esEdicion) {
                    mostrarSeccionDocumentos(cId);
                }
            } else {
                mostrarSeccionDocumentos(cId);
            }
        })
        .catch(function () {
            showToastClientes('Error al guardar el cliente.', 'error');
        });
    }

    document.getElementById('btnGuardarCliente')?.addEventListener('click', guardarCliente);

    function reiniciarEmpleadosNuevo() {
        const lista = document.getElementById('listaEmpleadosNuevo');
        if (!lista) return;
        lista.innerHTML = '';
        const base = document.createElement('div');
        base.className = 'empleado-item nuevo-empleado';
        base.innerHTML = '<input type="hidden" name="empleado_id[]" value="">' + getEmpleadoItemHtml(1);
        lista.appendChild(base);
        expandirSoloEmpleado(base);
        bindRemoveEmpleadoNuevo();
        bindEmpleadoAccordion();
        bindEmpNotas();
        bindEmpleadoNombreChange();
        reindexarEmpleadosNuevo();
    }

    function reindexarEmpleadosNuevo() {
        document.querySelectorAll('#listaEmpleadosNuevo .empleado-item').forEach(function (item, index) {
            var numEl = item.querySelector('.empleado-num');
            if (!numEl) return;
            var nom = (item.querySelector('input[name="empleado_nombre_nuevo[]"]')?.value || '').trim();
            numEl.innerHTML = '<i class="fas fa-chevron-right empleado-toggle"></i> Empleado ' + (index + 1) + (nom ? ': ' + escapeHtmlAttr(nom.substring(0, 25)) + (nom.length > 25 ? '…' : '') : '');
        });
    }

    function bindEmpleadoNombreChange() {
        document.querySelectorAll('#listaEmpleadosNuevo input[name="empleado_nombre_nuevo[]"]').forEach(function (inp) {
            if (inp.dataset.nombreBound) return;
            inp.dataset.nombreBound = '1';
            inp.addEventListener('input', function () { reindexarEmpleadosNuevo(); });
        });
    }

    function expandirSoloEmpleado(item) {
        document.querySelectorAll('#listaEmpleadosNuevo .empleado-item').forEach(function (el) {
            el.classList.add('collapsed');
        });
        if (item) item.classList.remove('collapsed');
    }

    function bindEmpleadoAccordion() {
        document.querySelectorAll('#listaEmpleadosNuevo .empleado-item-header').forEach(function (header) {
            if (header.dataset.accordionBound) return;
            header.dataset.accordionBound = '1';
            header.addEventListener('click', function (e) {
                if (e.target.closest('.btn-remove-empleado')) return;
                var item = this.closest('.empleado-item');
                if (item && item.classList.contains('collapsed')) expandirSoloEmpleado(item);
            });
        });
        document.querySelectorAll('#listaEmpleadosNuevo .btn-remove-empleado').forEach(function (btn) {
            if (btn.dataset.stopBound) return;
            btn.dataset.stopBound = '1';
            btn.addEventListener('click', function (e) { e.stopPropagation(); });
        });
    }

    function bindRemoveEmpleadoNuevo() {
        document.querySelectorAll('#listaEmpleadosNuevo .btn-remove-empleado').forEach(function (btn) {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                const item = this.closest('.empleado-item');
                const lista = document.getElementById('listaEmpleadosNuevo');
                if (lista && lista.children.length > 1) {
                    item.remove();
                    reindexarEmpleadosNuevo();
                    var remaining = lista.querySelector('.empleado-item');
                    if (remaining) expandirSoloEmpleado(remaining);
                } else {
                    item.querySelectorAll('input').forEach(function (i) { i.value = ''; });
                    item.querySelectorAll('select').forEach(function (s) { s.value = ''; });
                    item.querySelectorAll('.empleado-nota-fila').forEach(function (f) {
                        if (lista.querySelectorAll('.empleado-nota-fila').length > 1) f.remove();
                        else f.querySelectorAll('input').forEach(function (i) { i.value = ''; });
                    });
                }
            });
        });
    }

    document.getElementById('btnAddEmpleadoNuevo')?.addEventListener('click', function () {
        const lista = document.getElementById('listaEmpleadosNuevo');
        if (!lista) return;
        const count = lista.querySelectorAll('.empleado-item').length;
        const wrapper = document.createElement('div');
        wrapper.className = 'empleado-item nuevo-empleado collapsed';
        wrapper.innerHTML = '<input type="hidden" name="empleado_id[]" value="">' + getEmpleadoItemHtml(count + 1);
        lista.appendChild(wrapper);
        bindRemoveEmpleadoNuevo();
        bindEmpleadoAccordion();
        bindEmpNotas();
        bindEmpleadoNombreChange();
        reindexarEmpleadosNuevo();
        expandirSoloEmpleado(wrapper);
    });

    // Ver detalle cliente en modal
    function formatoFecha(fechaStr) {
        if (!fechaStr) return '-';
        try {
            const d = new Date(fechaStr);
            if (isNaN(d.getTime())) return fechaStr;
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            return `\${y}-\${m}-\${day} \${hh}:\${mm}`;
        } catch {
            return fechaStr;
        }
    }

    function abrirDetalleCliente(id) {
        fetch('api_cliente_detalle.php?id=' + encodeURIComponent(id))
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(res => {
                if (!res.ok || !res.data.ok) {
                    showToastClientes(res.data && res.data.error ? res.data.error : 'Error al cargar el detalle.', 'error');
                    return;
                }
                const c = res.data.cliente;
                const empleados = res.data.empleados || [];

                document.getElementById('detalleNombrePrincipal').textContent = c.nombres || '(Sin nombre)';
                document.getElementById('detalleSubtitulo').textContent = c.tipo_cliente ? ('Tipo: ' + c.tipo_cliente) : '';
                document.getElementById('detalleNombres').textContent = c.nombres || '-';
                document.getElementById('detalleApellidos').textContent = c.apellidos || '-';
                document.getElementById('detalleCorreo').textContent = c.email || '-';
                document.getElementById('detalleTipoCliente').textContent = c.tipo_cliente || '-';
                document.getElementById('detalleTipoDocumento').textContent = (c.tipo_identificacion || '').trim() || '-';
                document.getElementById('detalleNumeroDocumento').textContent = (c.identificacion || '').trim() || '-';
                document.getElementById('detalleFechaRegistro').textContent = formatoFecha(c.creado_at);

                const estadoBadge = document.getElementById('detalleEstadoBadge');
                if (estadoBadge) {
                    const esAlDia = (c.estado_pago || '') === 'AL_DIA';
                    estadoBadge.className = 'badge ' + (esAlDia ? 'badge-success' : 'badge-pending');
                    estadoBadge.textContent = esAlDia ? 'Al Día' : 'Pendiente';
                }

                const notasWrapper = document.getElementById('detalleNotasWrapper');
                const notasBody = document.getElementById('detalleNotasBody');
                notasBody.innerHTML = '';
                const notas = c.notas || [];
                if (notas.length) {
                    notas.forEach(n => {
                        const tr = document.createElement('tr');
                        const td1 = document.createElement('td');
                        const td2 = document.createElement('td');
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

                const empleadosWrapper = document.getElementById('detalleEmpleadosWrapper');
                const empleadosBody = document.getElementById('detalleEmpleadosBody');
                const empleadosTitulo = document.getElementById('detalleEmpleadosTitulo');
                empleadosBody.innerHTML = '';
                if (empleados.length) {
                    empleadosTitulo.textContent = 'Empleados (' + empleados.length + ')';
                    empleados.forEach(e => {
                        const tr = document.createElement('tr');
                        const notasEmp = e.notas || [];
                        const notasTexto = notasEmp.length ? notasEmp.map(n => (n.titulo || '') + ': ' + (n.valor || '')).join('; ') : '-';
                        const celdas = [
                            e.nombres || '-',
                            e.apellidos || '-',
                            (e.tipo_documento || '').trim() || '-',
                            (e.numero_documento || '').trim() || '-',
                            e.email || '-',
                            e.cargo || '-',
                            notasTexto
                        ];
                        celdas.forEach(txt => {
                            const td = document.createElement('td');
                            td.textContent = txt;
                            tr.appendChild(td);
                        });
                        empleadosBody.appendChild(tr);
                    });
                    empleadosWrapper.style.display = '';
                } else {
                    empleadosWrapper.style.display = 'none';
                }

                const docs = res.data.documentos || [];
                const docsWrapper = document.getElementById('detalleDocumentosWrapper');
                const docsTitulo = document.getElementById('detalleDocumentosTitulo');
                const docsBody = document.getElementById('detalleDocumentosBody');
                docsBody.innerHTML = '';
                if (docs.length) {
                    docsTitulo.textContent = 'Documentos de respaldo (' + docs.length + ')';
                    docs.forEach(d => {
                        const tr = document.createElement('tr');
                        let fecha = d.creado_at ? d.creado_at.replace('T', ' ').substr(0, 16) : '-';
                        const fa = fecha.split(/[- :]/);
                        if (fa.length >= 5) fecha = fa[2] + '/' + fa[1] + '/' + fa[0] + ' ' + fa[3] + ':' + fa[4];
                        tr.innerHTML = '<td>' + (d.nombre_tipo || '-') + '</td><td>' + (d.nombre_original || '-') + '</td><td>' + fecha + '</td><td><a href="descargar_documento.php?id=' + d.documento_id + '" class="btn-icon btn-edit" title="Descargar" download target="_blank"><i class="fas fa-download"></i></a></td>';
                        docsBody.appendChild(tr);
                    });
                    docsWrapper.style.display = '';
                } else {
                    docsWrapper.style.display = 'none';
                }

                const editarBtn = document.getElementById('detalleEditarBtn');
                if (editarBtn) {
                    editarBtn.onclick = function () {
                        closeModal('modalClienteDetalle');
                        cargarClienteParaEditar(c.cliente_id);
                    };
                }

                openModal('modalClienteDetalle');
            })
            .catch(() => {
                showToastClientes('Error al cargar el detalle.', 'error');
            });
    }

    document.addEventListener('click', function (e) {
        const viewBtn = e.target.closest('.btn-view');
        if (viewBtn) {
            e.preventDefault();
            e.stopPropagation();
            const href = viewBtn.getAttribute('href') || '';
            const m = href.match(/id=(\d+)/);
            const id = m ? m[1] : viewBtn.dataset.id;
            if (id) abrirDetalleCliente(id);
        }
    });

    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const nombre = this.getAttribute('data-nombre');
            if (!confirm('¿Mover el cliente "' + (nombre || '') + '" a inactivos?')) return;
            btn.disabled = true;
            fetch('api_clientes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'eliminar', cliente_id: parseInt(id, 10) })
            }).then(function (r) { return r.json(); }).then(function (res) {
                if (res.ok) {
                    showToastClientes('Cliente movido a inactivos', 'success');
                    setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    showToastClientes(res.error || 'Error al eliminar', 'error');
                    btn.disabled = false;
                }
            }).catch(function () {
                showToastClientes('Error de conexión', 'error');
                btn.disabled = false;
            });
        });
    });

    document.querySelectorAll('.toggle-ss').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const btnEl = this;
            btnEl.disabled = true;
            fetch('api_clientes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'toggle_ss', cliente_id: parseInt(id, 10) })
            }).then(function (r) { return r.json(); }).then(function (res) {
                btnEl.disabled = false;
                if (res.ok) {
                    var nuevo = res.cobrar_seguridad_social_mensual ? 1 : 0;
                    btnEl.setAttribute('data-active', nuevo);
                    btnEl.title = 'Cobrar Seg. Social mensual: ' + (nuevo ? 'Activo' : 'Inactivo');
                    btnEl.classList.toggle('toggle-ss-on', nuevo === 1);
                    btnEl.classList.toggle('toggle-ss-off', nuevo === 0);
                    showToastClientes(nuevo ? 'Seguridad social activada' : 'Seguridad social desactivada', 'success');
                } else {
                    showToastClientes(res.error || 'Error al actualizar', 'error');
                }
            }).catch(function () {
                btnEl.disabled = false;
                showToastClientes('Error de conexión', 'error');
            });
        });
    });

    if (editarIdOnLoad > 0) {
        history.replaceState({}, '', 'clientes.php');
        cargarClienteParaEditar(editarIdOnLoad);
    }
})();
});
</script>
SCRIPT;
require_once __DIR__ . '/../includes/footer.php';
?>