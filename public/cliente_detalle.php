<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/info_adicional.php';

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

// Separar nombres y apellidos del cliente
$clienteNombres = trim($cliente['nombre'] ?? '');
$clienteApellidos = trim($cliente['apellidos'] ?? '');

// Fallback para datos antiguos: partir nombre completo si no hay apellidos
if ($clienteApellidos === '' && strpos($clienteNombres, ' ') !== false) {
    $clientePartesNombre = preg_split('/\s+/', $clienteNombres);
    if (is_array($clientePartesNombre) && count($clientePartesNombre) > 1) {
        $clienteApellidos = array_pop($clientePartesNombre);
        $clienteNombres = implode(' ', $clientePartesNombre);
    }
}

// Obtener empleados y notas
require_once __DIR__ . '/../controllers/empleados.php';
$empleados = obtenerEmpleadosPorCliente($id);
$notasCliente = obtenerNotasCliente($id);
$notasEmpleados = [];
foreach ($empleados as $e) {
    $notasEmpleados[$e['empleado_id']] = obtenerNotasEmpleado($e['empleado_id']);
}

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../controllers/documento.php';
$documentosCliente = listarDocumentosClienteCtrl($conn, $id);
require_once __DIR__ . '/../includes/header.php';
?>

<section class="clients-section">
    <div class="section-header">
        <div>
            <h2 class="section-title"><?= htmlspecialchars($cliente['nombre']) ?></h2>
            <p class="section-subtitle">Información del cliente</p>
        </div>
        <div class="section-header-actions">
            <a href="reportes.php?ver_procesos=<?= $id ?>" class="btn-add-client btn-ver-procesos">
                <i class="fas fa-list"></i> Procesos y Estado
            </a>
            <a href="clientes.php?editar=<?= $id ?>" class="btn-add-client">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
    </div>

    <div class="profile-card">
        <h3 class="panel-subtitle">Datos del Cliente</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Nombres</label>
                <p><?= htmlspecialchars($clienteNombres ?: $cliente['nombre']) ?></p>
            </div>
            <div class="form-group">
                <label>Apellidos</label>
                <p><?= htmlspecialchars($clienteApellidos ?: '-') ?></p>
            </div>
        </div>
        <div class="form-row">
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
                <label>Tipo documento</label>
                <p><?= htmlspecialchars(trim($cliente['tipo_identificacion'] ?? '') ?: '-') ?></p>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Número documento</label>
                <p><?= htmlspecialchars(trim($cliente['identificacion'] ?? '') ?: '-') ?></p>
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
        <?php if (!empty($notasCliente)): ?>
        <div class="form-row">
            <div class="form-group form-group-full">
                <label>Información adicional</label>
                <table class="notas-table">
                    <thead><tr><th>Título</th><th>Valor</th></tr></thead>
                    <tbody>
                        <?php foreach ($notasCliente as $n): ?>
                        <tr>
                            <td><?= htmlspecialchars($n['titulo']) ?></td>
                            <td><?= htmlspecialchars($n['valor']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <h3 class="section-title section-title-spaced" id="tituloDocumentos">Documentos de respaldo (<?= count($documentosCliente) ?>)</h3>
    <div class="profile-card documentos-seccion documentos-compact">
        <form id="formDocumento" class="documento-upload-form documento-upload-compact">
            <input type="hidden" name="cliente_id" value="<?= (int)$id ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div class="documento-row-compact">
                <input type="text" id="nombre_tipo" name="nombre_tipo" required placeholder="Nombre (ej: Cédula, RUT)" maxlength="100" class="doc-input-nombre">
                <label class="doc-file-wrap"><span class="doc-file-label">Elegir archivo</span><input type="file" id="archivo" name="archivo" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required></label>
                <button type="submit" class="btn-doc-subir" id="btnSubirDoc"><i class="fas fa-plus"></i> Agregar</button>
            </div>
        </form>
        <div id="listaDocumentos" class="documentos-lista documentos-lista-compact">
            <?php if (empty($documentosCliente)): ?>
            <p class="text-muted">Sin documentos.</p>
            <?php else: ?>
            <table class="documentos-table">
                <thead><tr><th>Nombre</th><th>Archivo</th><th>Fecha</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php foreach ($documentosCliente as $doc): ?>
                <tr>
                    <td><?= htmlspecialchars($doc['nombre_tipo']) ?></td>
                    <td><?= htmlspecialchars($doc['nombre_original']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($doc['creado_at'])) ?></td>
                    <td>
                        <a href="descargar_documento.php?id=<?= (int)$doc['documento_id'] ?>" class="btn-icon btn-edit" title="Descargar" download><i class="fas fa-download"></i></a>
                        <button type="button" class="btn-icon btn-delete btn-eliminar-doc" data-id="<?= (int)$doc['documento_id'] ?>" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($empleados)): ?>
    <h3 class="section-title section-title-spaced">Empleados (<?= count($empleados) ?>)</h3>
    <div class="table-card">
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
            <tbody>
                <?php foreach ($empleados as $e): 
                    $nombresEmp = trim($e['nombre'] ?? '');
                    $apellidosEmp = trim($e['apellidos'] ?? '');
                    if ($apellidosEmp === '' && strpos($nombresEmp, ' ') !== false) {
                        $partesEmp = preg_split('/\s+/', $nombresEmp);
                        if (is_array($partesEmp) && count($partesEmp) > 1) {
                            $apellidosEmp = array_pop($partesEmp);
                            $nombresEmp = implode(' ', $partesEmp);
                        }
                    }
                    $notasEmp = $notasEmpleados[$e['empleado_id']] ?? [];
                ?>
                <tr>
                    <td><?= htmlspecialchars($nombresEmp ?: $e['nombre']) ?></td>
                    <td><?= htmlspecialchars($apellidosEmp ?: '-') ?></td>
                    <td><?= htmlspecialchars(trim($e['tipo_documento'] ?? '') ?: '-') ?></td>
                    <td><?= htmlspecialchars(trim($e['numero_documento'] ?? '') ?: '-') ?></td>
                    <td><?= htmlspecialchars($e['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($e['cargo'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($notasEmp)): ?>
                        <table class="notas-table-inline">
                            <?php foreach ($notasEmp as $n): ?>
                            <tr><td><?= htmlspecialchars($n['titulo']) ?></td><td><?= htmlspecialchars($n['valor']) ?></td></tr>
                            <?php endforeach; ?>
                        </table>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php
$extraScripts = <<<'SCR'
<script>
(function() {
    var clienteId = parseInt(document.querySelector('input[name="cliente_id"]').value, 10);
    var lista = document.getElementById('listaDocumentos');
    function actualizarTituloDocumentos(n) {
        var tit = document.getElementById('tituloDocumentos');
        if (tit) tit.textContent = 'Documentos de respaldo (' + n + ')';
    }
    function cargarDocumentos() {
        fetch('api_documentos_cliente.php?cliente_id=' + clienteId).then(function(r) { return r.json(); }).then(function(data) {
            if (!data.ok || !data.documentos) {
                lista.innerHTML = '<p class="text-muted">Error al cargar</p>';
                actualizarTituloDocumentos(0);
                return;
            }
            var docs = data.documentos;
            actualizarTituloDocumentos(docs.length);
            if (docs.length === 0) {
                lista.innerHTML = '<p class="text-muted">Sin documentos.</p>';
                return;
            }
            lista.innerHTML = '<table class="documentos-table"><thead><tr><th>Nombre</th><th>Archivo</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>' +
                docs.map(function(d) {
                    var fecha = d.creado_at ? d.creado_at.replace('T', ' ').substr(0, 16) : '-';
                    var fa = fecha.split(/[- :]/);
                    if (fa.length >= 5) fecha = fa[2] + '/' + fa[1] + '/' + fa[0] + ' ' + fa[3] + ':' + fa[4];
                    return '<tr><td>' + escapeHtml(d.nombre_tipo) + '</td><td>' + escapeHtml(d.nombre_original) + '</td><td>' + fecha + '</td><td>' +
                        '<a href="descargar_documento.php?id=' + d.documento_id + '" class="btn-icon btn-edit" title="Descargar" download><i class="fas fa-download"></i></a> ' +
                        '<button type="button" class="btn-icon btn-delete btn-eliminar-doc" data-id="' + d.documento_id + '" title="Eliminar"><i class="fas fa-trash-alt"></i></button>' +
                        '</td></tr>';
                }).join('') + '</tbody></table>';
            lista.querySelectorAll('.btn-eliminar-doc').forEach(function(btn) {
                btn.addEventListener('click', eliminarDoc);
            });
        }).catch(function() { lista.innerHTML = '<p class="text-muted">Error al cargar</p>'; });
    }
    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    function eliminarDoc() {
        var id = this.getAttribute('data-id');
        if (!confirm('¿Eliminar este documento?')) return;
        var btn = this;
        btn.disabled = true;
        fetch('api_documentos_cliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.SIGED_CSRF || '' },
            body: JSON.stringify({ action: 'eliminar', documento_id: parseInt(id, 10) })
        }).then(function(r) { return r.json(); }).then(function(res) {
            if (res.ok) cargarDocumentos(); else { alert(res.error || 'Error'); btn.disabled = false; }
        }).catch(function() { alert('Error de conexión'); btn.disabled = false; });
    }
    document.getElementById('formDocumento').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = document.getElementById('btnSubirDoc');
        var nombreTipo = document.getElementById('nombre_tipo').value.trim();
        var archivo = document.getElementById('archivo').files[0];
        if (!nombreTipo || !archivo) { alert('Complete el nombre y seleccione un archivo.'); return; }
        var fd = new FormData(form);
        btn.disabled = true;
        fetch('api_documentos_cliente.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btn.disabled = false;
                if (res.ok) {
                    document.getElementById('nombre_tipo').value = '';
                    document.getElementById('archivo').value = '';
                    cargarDocumentos();
                } else alert(res.error || 'Error al subir');
            })
            .catch(function() { btn.disabled = false; alert('Error de conexión'); });
    });
})();
</script>
SCR;
require_once __DIR__ . '/../includes/footer.php';
?>
