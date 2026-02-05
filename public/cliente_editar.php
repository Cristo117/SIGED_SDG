<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/empleados.php';
require_once __DIR__ . '/../controllers/info_adicional.php';

requireAuth();

$pageTitle = 'Agregar Cliente';
$activePage = 'clientes';
$cliente = null;
$empleados = [];
$notasCliente = [];
$notasEmpleados = []; // [empleado_id => [notas]]
$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    $cliente = obtenerClientePorId($id);
    if (!$cliente) {
        header('Location: clientes.php');
        exit;
    }
    $empleados = obtenerEmpleadosPorCliente($id);
    $notasCliente = obtenerNotasCliente($id);
    foreach ($empleados as $e) {
        $notasEmpleados[$e['empleado_id']] = obtenerNotasEmpleado($e['empleado_id']);
    }
    $pageTitle = 'Editar Cliente';
}

$msg = $_SESSION['cliente_msg'] ?? null;
$msgType = $_SESSION['cliente_msg_type'] ?? 'success';
unset($_SESSION['cliente_msg'], $_SESSION['cliente_msg_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'email' => trim($_POST['email'] ?? '') ?: null,
        'tipo_identificacion' => trim($_POST['tipo_identificacion'] ?? '') ?: null,
        'identificacion' => trim($_POST['identificacion'] ?? '') ?: null,
        'tipo_cliente' => $_POST['tipo_cliente'] ?? 'INDEPENDIENTE',
        'estado_pago' => $_POST['estado_pago'] ?? 'AL_DIA'
    ];
    if (!empty($datos['nombre'])) {
        $clienteId = guardarCliente($datos, $id ?: null);
        $tipoCliente = $datos['tipo_cliente'];

        // Notas del cliente (título, valor)
        $titulos = $_POST['nota_titulo'] ?? [];
        $valores = $_POST['nota_valor'] ?? [];
        if (!is_array($titulos)) $titulos = [$titulos];
        if (!is_array($valores)) $valores = [$valores];
        $paresCliente = [];
        for ($i = 0; $i < max(count($titulos), count($valores)); $i++) {
            $paresCliente[] = ['titulo' => $titulos[$i] ?? '', 'valor' => $valores[$i] ?? ''];
        }
        guardarNotasCliente($clienteId, $paresCliente);

        // Si es empleador, procesar empleados
        if ($tipoCliente === 'EMPLEADOR') {
            $eliminarIds = $_POST['empleado_eliminar'] ?? [];
            if (!is_array($eliminarIds)) $eliminarIds = [$eliminarIds];
            foreach ($eliminarIds as $eid) {
                if ((int)$eid > 0) {
                    eliminarEmpleado((int)$eid, $clienteId);
                }
            }

            $nombres = $_POST['empleado_nombre'] ?? [];
            $emails = $_POST['empleado_email'] ?? [];
            $tiposDoc = $_POST['empleado_tipo_documento'] ?? [];
            $numsDoc = $_POST['empleado_numero_documento'] ?? [];
            $cargos = $_POST['empleado_cargo'] ?? [];
            $empleadoIds = $_POST['empleado_id'] ?? [];

            if (!is_array($nombres)) $nombres = [$nombres];
            $n = count($nombres);
            for ($i = 0; $i < $n; $i++) {
                $nombre = trim($nombres[$i] ?? '');
                if (empty($nombre)) continue;
                $empId = isset($empleadoIds[$i]) && (int)$empleadoIds[$i] > 0 ? (int)$empleadoIds[$i] : null;
                $nuevoEmpId = guardarEmpleado([
                    'cliente_id' => $clienteId,
                    'nombre' => $nombre,
                    'email' => trim($emails[$i] ?? '') ?: null,
                    'tipo_documento' => trim($tiposDoc[$i] ?? '') ?: null,
                    'numero_documento' => trim($numsDoc[$i] ?? '') ?: null,
                    'cargo' => trim($cargos[$i] ?? '') ?: null
                ], $empId);
                // Notas del empleado
                $empTitulos = $_POST['empleado_'.$i.'_nota_titulo'] ?? [];
                $empValores = $_POST['empleado_'.$i.'_nota_valor'] ?? [];
                if (!is_array($empTitulos)) $empTitulos = [$empTitulos];
                if (!is_array($empValores)) $empValores = [$empValores];
                $paresEmp = [];
                for ($j = 0; $j < max(count($empTitulos), count($empValores)); $j++) {
                    $paresEmp[] = ['titulo' => $empTitulos[$j] ?? '', 'valor' => $empValores[$j] ?? ''];
                }
                guardarNotasEmpleado($nuevoEmpId, $paresEmp);
            }
        } else {
            // Independiente: eliminar empleados si cambió de empleador a independiente
            foreach ($empleados as $e) {
                eliminarEmpleado($e['empleado_id'], $clienteId);
            }
        }

        $_SESSION['cliente_msg'] = $id ? 'Cliente actualizado' : 'Cliente creado correctamente';
        header('Location: clientes.php');
        exit;
    }
}

$tipoCliente = $cliente['tipo_cliente'] ?? $_POST['tipo_cliente'] ?? 'INDEPENDIENTE';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="clients-section">
    <div class="section-header">
        <div>
            <h2 class="section-title"><?= $id ? 'Editar' : 'Agregar' ?> Cliente</h2>
            <p class="section-subtitle">Complete los datos del cliente</p>
        </div>
        <a href="clientes.php" class="btn-add-client" style="background:#6c757d;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msgType === 'error' ? 'danger' : 'success' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST" class="profile-card" id="formCliente" style="max-width: 700px;">
        <div class="form-row">
            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required 
                       value="<?= htmlspecialchars($cliente['nombre'] ?? $_POST['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" 
                       value="<?= htmlspecialchars($cliente['email'] ?? $_POST['email'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="tipo_identificacion">Tipo Documento</label>
                <select id="tipo_identificacion" name="tipo_identificacion">
                    <option value="">Seleccione</option>
                    <option value="CC" <?= ($cliente['tipo_identificacion'] ?? $_POST['tipo_identificacion'] ?? '') === 'CC' ? 'selected' : '' ?>>Cédula</option>
                    <option value="NIT" <?= ($cliente['tipo_identificacion'] ?? $_POST['tipo_identificacion'] ?? '') === 'NIT' ? 'selected' : '' ?>>NIT</option>
                    <option value="Pasaporte" <?= ($cliente['tipo_identificacion'] ?? $_POST['tipo_identificacion'] ?? '') === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                </select>
            </div>
            <div class="form-group">
                <label for="identificacion">Número Documento</label>
                <input type="text" id="identificacion" name="identificacion" 
                       value="<?= htmlspecialchars($cliente['identificacion'] ?? $_POST['identificacion'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="tipo_cliente">Tipo de Cliente</label>
                <select id="tipo_cliente" name="tipo_cliente">
                    <option value="INDEPENDIENTE" <?= $tipoCliente === 'INDEPENDIENTE' ? 'selected' : '' ?>>Independiente</option>
                    <option value="EMPLEADOR" <?= $tipoCliente === 'EMPLEADOR' ? 'selected' : '' ?>>Empleador</option>
                </select>
                <small class="form-hint" id="hintTipo">Los independientes no tienen empleados.</small>
            </div>
            <div class="form-group">
                <label for="estado_pago">Estado de Pago</label>
                <select id="estado_pago" name="estado_pago">
                    <option value="AL_DIA" <?= ($cliente['estado_pago'] ?? $_POST['estado_pago'] ?? '') === 'AL_DIA' ? 'selected' : '' ?>>Al Día</option>
                    <option value="PENDIENTE" <?= ($cliente['estado_pago'] ?? $_POST['estado_pago'] ?? '') === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                </select>
            </div>
        </div>

        <!-- Notas (título, valor): visible para ambos tipos -->
        <div class="form-group form-group-full notas-section" id="sectionNotasCliente">
            <label>Información adicional</label>
            <p class="form-hint">Agregue notas con título y valor. Ej: Observaciones / Pendiente revisión</p>
            <div class="notas-lista" id="listaNotasCliente">
                <?php 
                $notas = $notasCliente;
                if (empty($notas)) $notas = [['titulo' => '', 'valor' => '']];
                foreach ($notas as $n): ?>
                <div class="nota-fila">
                    <input type="text" name="nota_titulo[]" placeholder="Título" value="<?= htmlspecialchars($n['titulo'] ?? '') ?>">
                    <input type="text" name="nota_valor[]" placeholder="Valor" value="<?= htmlspecialchars($n['valor'] ?? '') ?>">
                    <button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn-add-nota" data-target="listaNotasCliente" data-titulo="nota_titulo" data-valor="nota_valor">
                <i class="fas fa-plus"></i> Agregar nota
            </button>
        </div>

        <!-- Sección empleados: solo para empleador -->
        <div id="sectionEmpleados" class="empleados-section" style="display: <?= $tipoCliente === 'EMPLEADOR' ? 'block' : 'none' ?>;">
            <h3 class="panel-subtitle">
                <i class="fas fa-users"></i> Empleados
            </h3>
            <p class="panel-desc">Agregue los empleados del cliente. Cada uno puede tener su propia información adicional.</p>
            <div id="listaEmpleados">
                <?php if (empty($empleados)): ?>
                <div class="empleado-item" data-index="0">
                    <div class="empleado-item-header">
                        <span class="empleado-num">Empleado 1</span>
                        <button type="button" class="btn-remove-empleado" title="Quitar empleado"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="empleado-fields">
                        <input type="hidden" name="empleado_id[]" value="">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre *</label>
                                <input type="text" name="empleado_nombre[]" placeholder="Nombre completo">
                            </div>
                            <div class="form-group">
                                <label>Correo</label>
                                <input type="email" name="empleado_email[]" placeholder="correo@ejemplo.com">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tipo Documento</label>
                                <select name="empleado_tipo_documento[]">
                                    <option value="">Seleccione</option>
                                    <option value="CC">Cédula</option>
                                    <option value="NIT">NIT</option>
                                    <option value="Pasaporte">Pasaporte</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Número Documento</label>
                                <input type="text" name="empleado_numero_documento[]" placeholder="Número">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cargo</label>
                                <input type="text" name="empleado_cargo[]" placeholder="Cargo o puesto">
                            </div>
                        </div>
                        <div class="form-group form-group-full notas-subsection">
                            <label>Información adicional</label>
                            <div class="notas-lista" data-empleado-index="0">
                                <div class="nota-fila">
                                    <input type="text" name="empleado_0_nota_titulo[]" placeholder="Título" value="">
                                    <input type="text" name="empleado_0_nota_valor[]" placeholder="Valor" value="">
                                    <button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                            <button type="button" class="btn-add-nota-empleado" data-index="0"><i class="fas fa-plus"></i> Agregar nota</button>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($empleados as $idx => $emp): ?>
                <div class="empleado-item" data-index="<?= $idx ?>">
                    <div class="empleado-item-header">
                        <span class="empleado-num">Empleado <?= $idx + 1 ?></span>
                        <button type="button" class="btn-remove-empleado" title="Quitar empleado"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="empleado-fields">
                        <input type="hidden" name="empleado_id[]" value="<?= (int)$emp['empleado_id'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nombre *</label>
                                <input type="text" name="empleado_nombre[]" value="<?= htmlspecialchars($emp['nombre']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Correo</label>
                                <input type="email" name="empleado_email[]" value="<?= htmlspecialchars($emp['email'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tipo Documento</label>
                                <select name="empleado_tipo_documento[]">
                                    <option value="">Seleccione</option>
                                    <option value="CC" <?= ($emp['tipo_documento'] ?? '') === 'CC' ? 'selected' : '' ?>>Cédula</option>
                                    <option value="NIT" <?= ($emp['tipo_documento'] ?? '') === 'NIT' ? 'selected' : '' ?>>NIT</option>
                                    <option value="Pasaporte" <?= ($emp['tipo_documento'] ?? '') === 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Número Documento</label>
                                <input type="text" name="empleado_numero_documento[]" value="<?= htmlspecialchars($emp['numero_documento'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Cargo</label>
                                <input type="text" name="empleado_cargo[]" value="<?= htmlspecialchars($emp['cargo'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group form-group-full notas-subsection">
                            <label>Información adicional</label>
                            <?php 
                            $notasEmp = $notasEmpleados[$emp['empleado_id']] ?? [];
                            if (empty($notasEmp)) $notasEmp = [['titulo' => '', 'valor' => '']];
                            ?>
                            <div class="notas-lista" data-empleado-index="<?= $idx ?>">
                                <?php foreach ($notasEmp as $n): ?>
                                <div class="nota-fila">
                                    <input type="text" name="empleado_<?= $idx ?>_nota_titulo[]" placeholder="Título" value="<?= htmlspecialchars($n['titulo'] ?? '') ?>">
                                    <input type="text" name="empleado_<?= $idx ?>_nota_valor[]" placeholder="Valor" value="<?= htmlspecialchars($n['valor'] ?? '') ?>">
                                    <button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn-add-nota-empleado" data-index="<?= $idx ?>"><i class="fas fa-plus"></i> Agregar nota</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="btn-add-empleado" id="btnAddEmpleado">
                <i class="fas fa-plus"></i> Agregar empleado
            </button>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </form>
</section>

<?php 
$empleadoTemplate = htmlspecialchars(<<<'HTML'
<div class="empleado-item" data-index="__INDEX__">
    <div class="empleado-item-header">
        <span class="empleado-num">Empleado __NUM__</span>
        <button type="button" class="btn-remove-empleado" title="Quitar empleado"><i class="fas fa-times"></i></button>
    </div>
    <div class="empleado-fields">
        <input type="hidden" name="empleado_id[]" value="">
        <div class="form-row">
            <div class="form-group">
                <label>Nombre *</label>
                <input type="text" name="empleado_nombre[]" placeholder="Nombre completo">
            </div>
            <div class="form-group">
                <label>Correo</label>
                <input type="email" name="empleado_email[]" placeholder="correo@ejemplo.com">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Tipo Documento</label>
                <select name="empleado_tipo_documento[]">
                    <option value="">Seleccione</option>
                    <option value="CC">Cédula</option>
                    <option value="NIT">NIT</option>
                    <option value="Pasaporte">Pasaporte</option>
                </select>
            </div>
            <div class="form-group">
                <label>Número Documento</label>
                <input type="text" name="empleado_numero_documento[]" placeholder="Número">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Cargo</label>
                <input type="text" name="empleado_cargo[]" placeholder="Cargo o puesto">
            </div>
        </div>
        <div class="form-group form-group-full notas-subsection">
            <label>Información adicional</label>
            <div class="notas-lista" data-empleado-index="__INDEX__">
                <div class="nota-fila">
                    <input type="text" name="empleado___INDEX__nota_titulo[]" placeholder="Título" value="">
                    <input type="text" name="empleado___INDEX__nota_valor[]" placeholder="Valor" value="">
                    <button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <button type="button" class="btn-add-nota-empleado" data-index="__INDEX__"><i class="fas fa-plus"></i> Agregar nota</button>
        </div>
    </div>
</div>
HTML
);

$extraScripts = <<<SCRIPT
<script>
(function() {
    const tipoCliente = document.getElementById('tipo_cliente');
    const sectionEmpleados = document.getElementById('sectionEmpleados');
    const hintTipo = document.getElementById('hintTipo');
    const listaEmpleados = document.getElementById('listaEmpleados');
    const btnAddEmpleado = document.getElementById('btnAddEmpleado');
    const empleadoTemplate = `$empleadoTemplate`;

    function actualizarVisibilidad() {
        const esEmpleador = tipoCliente.value === 'EMPLEADOR';
        sectionEmpleados.style.display = esEmpleador ? 'block' : 'none';
        hintTipo.textContent = esEmpleador ? 'Los empleadores pueden registrar empleados.' : 'Los independientes no tienen empleados.';
        // Los empleados son opcionales; el backend ignora filas vacías
    }

    tipoCliente.addEventListener('change', actualizarVisibilidad);

    function reindexarEmpleados() {
        const items = listaEmpleados.querySelectorAll('.empleado-item');
        items.forEach((item, i) => {
            item.setAttribute('data-index', i);
            item.querySelector('.empleado-num').textContent = 'Empleado ' + (i + 1);
            item.querySelector('.btn-add-nota-empleado').setAttribute('data-index', i);
            item.querySelector('.notas-lista').setAttribute('data-empleado-index', i);
            item.querySelectorAll('.nota-fila input').forEach((inp) => {
                if (inp.name.includes('nota_titulo')) inp.name = 'empleado_'+i+'_nota_titulo[]';
                else if (inp.name.includes('nota_valor')) inp.name = 'empleado_'+i+'_nota_valor[]';
            });
        });
    }

    btnAddEmpleado.addEventListener('click', function() {
        const count = listaEmpleados.querySelectorAll('.empleado-item').length;
        const html = empleadoTemplate.replace(/__INDEX__/g, count).replace(/__NUM__/g, count + 1);
        listaEmpleados.insertAdjacentHTML('beforeend', html);
        const nuevo = listaEmpleados.lastElementChild;
        nuevo.querySelector('.btn-remove-empleado').addEventListener('click', quitarEmpleado);
        nuevo.querySelector('.notas-lista').setAttribute('data-empleado-index', count);
        nuevo.querySelector('.btn-add-nota-empleado').setAttribute('data-index', count);
        bindNotaButtons(nuevo);
        reindexarEmpleados();
    });

    function quitarEmpleado(e) {
        const item = e.target.closest('.empleado-item');
        const idInput = item.querySelector('input[name="empleado_id[]"]');
        const empleadoId = idInput && idInput.value ? idInput.value : null;
        if (empleadoId && parseInt(empleadoId) > 0) {
            const eliminarDiv = document.getElementById('empleadoEliminarHidden') || (function() {
                const div = document.createElement('div');
                div.id = 'empleadoEliminarHidden';
                document.getElementById('formCliente').appendChild(div);
                return div;
            })();
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'empleado_eliminar[]';
            inp.value = empleadoId;
            eliminarDiv.appendChild(inp);
        }
        item.remove();
        reindexarEmpleados();
    }

    document.querySelectorAll('.btn-remove-empleado').forEach(btn => {
        btn.addEventListener('click', quitarEmpleado);
    });

    function addNotaFila(container, tituloName, valorName) {
        const fila = document.createElement('div');
        fila.className = 'nota-fila';
        fila.innerHTML = '<input type="text" name="'+tituloName+'[]" placeholder="Título" value="">' +
            '<input type="text" name="'+valorName+'[]" placeholder="Valor" value="">' +
            '<button type="button" class="btn-remove-nota" title="Quitar"><i class="fas fa-times"></i></button>';
        container.appendChild(fila);
        fila.querySelector('.btn-remove-nota').addEventListener('click', () => fila.remove());
    }

    function bindNotaButtons(scope) {
        (scope || document).querySelectorAll('.btn-remove-nota').forEach(btn => {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function() { this.closest('.nota-fila').remove(); });
        });
    }

    document.getElementById('listaNotasCliente')?.closest('.notas-section')?.querySelector('.btn-add-nota')?.addEventListener('click', function() {
        const lista = document.getElementById('listaNotasCliente');
        addNotaFila(lista, 'nota_titulo', 'nota_valor');
    });

    document.querySelectorAll('.btn-add-nota-empleado').forEach(btn => {
        btn.addEventListener('click', function() {
            const idx = this.getAttribute('data-index');
            const lista = this.previousElementSibling;
            addNotaFila(lista, 'empleado_'+idx+'_nota_titulo', 'empleado_'+idx+'_nota_valor');
        });
    });

    bindNotaButtons();

    actualizarVisibilidad();
})();
</script>
SCRIPT;
require_once __DIR__ . '/../includes/footer.php'; 
?>
