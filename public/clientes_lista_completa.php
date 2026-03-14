<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/clientes.php';
require_once __DIR__ . '/../controllers/empleados.php';
require_once __DIR__ . '/../controllers/info_adicional.php';

requireAuth();

$pageTitle = 'Lista completa - Clientes y Trabajadores';
$activePage = 'clientes';

$clientes = obtenerClientes(null, null, null);

$filas = [];
foreach ($clientes as $c) {
    $empleados = obtenerEmpleadosPorCliente($c['cliente_id']);
    $notasCliente = obtenerNotasCliente($c['cliente_id']);
    $notasEmpleados = [];
    foreach ($empleados as $e) {
        $notasEmpleados[$e['empleado_id']] = obtenerNotasEmpleado($e['empleado_id']);
    }

    $nombresCl = trim($c['nombre'] ?? '');
    $apellidosCl = trim($c['apellidos'] ?? '');
    if ($apellidosCl === '' && strpos($nombresCl, ' ') !== false) {
        $partes = preg_split('/\s+/', $nombresCl);
        if (count($partes) > 1) {
            $apellidosCl = array_pop($partes);
            $nombresCl = implode(' ', $partes);
        }
    }
    $notasClTexto = implode('; ', array_map(function ($n) {
        return ($n['titulo'] ?? $n['etiqueta'] ?? '') . ': ' . ($n['valor'] ?? '');
    }, $notasCliente));

    if (empty($empleados)) {
        $filas[] = [
            'cliente' => $nombresCl . ($apellidosCl ? ' ' . $apellidosCl : ''),
            'tipo_cliente' => $c['tipo_cliente'] ?? '',
            'doc_cliente' => trim(($c['tipo_identificacion'] ?? '') . ' ' . ($c['identificacion'] ?? '')) ?: '-',
            'email_cliente' => $c['email'] ?? '-',
            'estado' => $c['estado_pago'] ?? '',
            'info_cliente' => $notasClTexto,
            'empleado' => '',
            'doc_empleado' => '',
            'email_empleado' => '',
            'cargo' => '',
            'notas_empleado' => '',
        ];
    } else {
        foreach ($empleados as $e) {
            $nombresEmp = trim($e['nombre'] ?? '');
            $apellidosEmp = trim($e['apellidos'] ?? '');
            if ($apellidosEmp === '' && strpos($nombresEmp, ' ') !== false) {
                $partesEmp = preg_split('/\s+/', $nombresEmp);
                if (count($partesEmp) > 1) {
                    $apellidosEmp = array_pop($partesEmp);
                    $nombresEmp = implode(' ', $partesEmp);
                }
            }
            $notasEmp = $notasEmpleados[$e['empleado_id']] ?? [];
            $notasEmpTexto = implode('; ', array_map(function ($n) {
                return ($n['titulo'] ?? $n['etiqueta'] ?? '') . ': ' . ($n['valor'] ?? '');
            }, $notasEmp));

            $filas[] = [
                'cliente' => $nombresCl . ($apellidosCl ? ' ' . $apellidosCl : ''),
                'tipo_cliente' => $c['tipo_cliente'] ?? '',
                'doc_cliente' => trim(($c['tipo_identificacion'] ?? '') . ' ' . ($c['identificacion'] ?? '')) ?: '-',
                'email_cliente' => $c['email'] ?? '-',
                'estado' => $c['estado_pago'] ?? '',
                'info_cliente' => $notasClTexto,
                'empleado' => $nombresEmp . ($apellidosEmp ? ' ' . $apellidosEmp : ''),
                'doc_empleado' => trim(($e['tipo_documento'] ?? '') . ' ' . ($e['numero_documento'] ?? '')) ?: '-',
                'email_empleado' => $e['email'] ?? '-',
                'cargo' => $e['cargo'] ?? '-',
                'notas_empleado' => $notasEmpTexto,
            ];
        }
    }
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
            <h2 class="section-title">Lista completa de Clientes y Trabajadores</h2>
            <p class="section-subtitle">Todos tus clientes con sus empleados y datos</p>
        </div>
        <a href="clientes.php" class="btn-add-client">
            <i class="fas fa-arrow-left"></i>
            <span>Volver a Clientes</span>
        </a>
    </div>

    <div class="tabla-excel-wrapper">
        <table class="tabla-excel">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Doc. Cliente</th>
                    <th>Email Cliente</th>
                    <th>Estado Pago</th>
                    <th>Info adicional Cliente</th>
                    <th>Empleado / Trabajador</th>
                    <th>Doc. Empleado</th>
                    <th>Email Empleado</th>
                    <th>Cargo</th>
                    <th>Notas Empleado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas as $i => $f): ?>
                <tr class="<?= $i % 2 === 0 ? 'fila-par' : 'fila-impar' ?>">
                    <td title="<?= htmlspecialchars($f['cliente']) ?>"><?= htmlspecialchars($f['cliente']) ?></td>
                    <td><?= htmlspecialchars($f['tipo_cliente']) ?></td>
                    <td><?= htmlspecialchars($f['doc_cliente']) ?></td>
                    <td title="<?= htmlspecialchars($f['email_cliente']) ?>"><?= htmlspecialchars($f['email_cliente']) ?></td>
                    <td>
                        <?php if ($f['estado']): ?>
                        <span class="badge badge-<?= $f['estado'] === 'AL_DIA' ? 'success' : 'pending' ?>"><?= $f['estado'] === 'AL_DIA' ? 'Al Día' : 'Pendiente' ?></span>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td title="<?= htmlspecialchars($f['info_cliente']) ?>"><?= htmlspecialchars($f['info_cliente']) ?></td>
                    <td title="<?= htmlspecialchars($f['empleado']) ?>"><?= htmlspecialchars($f['empleado']) ?></td>
                    <td><?= htmlspecialchars($f['doc_empleado']) ?></td>
                    <td title="<?= htmlspecialchars($f['email_empleado']) ?>"><?= htmlspecialchars($f['email_empleado']) ?></td>
                    <td title="<?= htmlspecialchars($f['cargo']) ?>"><?= htmlspecialchars($f['cargo']) ?></td>
                    <td title="<?= htmlspecialchars($f['notas_empleado']) ?>"><?= htmlspecialchars($f['notas_empleado']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($filas)): ?>
        <p class="tabla-excel-vacio">No hay registros.</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
