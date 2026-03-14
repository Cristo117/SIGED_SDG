<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/ajustes.php';
require_once __DIR__ . '/../controllers/notificaciones.php';
require_once __DIR__ . '/../controllers/usuario.php';

requireAuth();

$esAdmin = esAdministrador();
$pageTitle = 'Ajustes';
$activePage = 'ajustes';

$usuario = getUsuarioActual();

$msg = '';
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $msgError = 'Sesión inválida. Recargue la página e intente de nuevo.';
    } else {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'perfil') {

        $nombre = trim($_POST['nombreCompleto'] ?? '');
        $email = trim($_POST['correo'] ?? '');

        if ($nombre && $email) {

            if (actualizarPerfilCtrl($conn, $_SESSION['usuario_id'], $nombre, $email)) {

                $_SESSION['nombre'] = $nombre;
                $_SESSION['email'] = $email;

                $msg = 'Perfil actualizado correctamente ✅';

            } else {
                $msgError = 'Error al actualizar perfil.';
            }
        }

    }

    if ($accion === 'seguridad') {

        $actual = $_POST['passwordActual'] ?? '';
        $nueva = $_POST['passwordNueva'] ?? '';
        $confirmar = $_POST['passwordConfirmar'] ?? '';

        if ($nueva !== $confirmar) {
            $msgError = 'Las contraseñas no coinciden.';
        } 
        elseif (strlen($nueva) < 6) {
            $msgError = 'Mínimo 6 caracteres.';
        } 
        elseif (cambiarPasswordCtrl($conn, $_SESSION['usuario_id'], $actual, $nueva)) {

            $msg = 'Contraseña cambiada correctamente 🔐';

        } else {
            $msgError = 'Contraseña actual incorrecta.';
        }
    }
    }
}

$notificacionesCount = obtenerNotificacionesNoLeidas($conn, $_SESSION['usuario_id']);

require_once __DIR__ . '/../includes/header.php';
?>

<section class="settings-section panel-section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Ajustes de cuenta</h2>
            <p class="section-subtitle">Gestiona tu perfil y seguridad de tu cuenta</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php elseif ($msgError): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($msgError) ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <div class="settings-tabs">
            <button type="button" class="settings-tab active" data-tab="perfil">
                <i class="fas fa-user-circle"></i>
                <span>Perfil</span>
            </button>
            <button type="button" class="settings-tab" data-tab="seguridad">
                <i class="fas fa-lock"></i>
                <span>Seguridad</span>
            </button>
            <?php if ($esAdmin): ?>
            <button type="button" class="settings-tab" data-tab="colaboradores">
                <i class="fas fa-users-cog"></i>
                <span>Colaboradores</span>
            </button>
            <?php endif; ?>
        </div>

        <div class="settings-panels">
            <div class="settings-panel active" id="panel-perfil">
                <h3 class="panel-subtitle">Información de perfil</h3>
                <p class="panel-desc">Estos datos se usan para personalizar el panel y las comunicaciones.</p>

                <div class="profile-user-block">
                    <div class="profile-avatar">
                        <?= htmlspecialchars(substr(($usuario['nombre'] ?? 'Admin'), 0, 1)) ?>
                    </div>
                    <div class="profile-user-info">
                        <span class="profile-name"><?= htmlspecialchars($usuario['nombre'] ?? 'Administrador') ?></span>
                        <span class="profile-role"><?= htmlspecialchars($usuario['email'] ?? '') ?></span>
                    </div>
                </div>

                <form method="POST" class="profile-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="perfil">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombreCompleto">Nombre completo</label>
                            <input type="text" id="nombreCompleto" name="nombreCompleto"
                                   value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="correo">Correo electrónico</label>
                            <input type="email" id="correo" name="correo"
                                   value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            <span>Guardar cambios</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="settings-panel" id="panel-seguridad">
                <h3 class="panel-subtitle">Seguridad y acceso</h3>
                <p class="panel-desc">Cambia tu contraseña de acceso. Te recomendamos usar una contraseña segura.</p>

                <form method="POST" class="security-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion" value="seguridad">
                    <div class="form-group">
                        <label for="passwordActual">Contraseña actual</label>
                        <input type="password" id="passwordActual" name="passwordActual" required>
                    </div>
                    <div class="form-group">
                        <label for="passwordNueva">Nueva contraseña</label>
                        <input type="password" id="passwordNueva" name="passwordNueva" required>
                        <small class="form-hint">Mínimo 6 caracteres, idealmente combina mayúsculas, minúsculas y números.</small>
                    </div>
                    <div class="form-group">
                        <label for="passwordConfirmar">Confirmar nueva contraseña</label>
                        <input type="password" id="passwordConfirmar" name="passwordConfirmar" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-lock"></i>
                            <span>Actualizar contraseña</span>
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($esAdmin): ?>
            <div class="settings-panel" id="panel-colaboradores">
                <h3 class="panel-subtitle">Gestión de colaboradores</h3>
                <p class="panel-desc">Agregue usuarios con rol Administrador o Colaborador. Los colaboradores no pueden eliminar registros.</p>

                <form class="colaborador-form" id="formColaborador" onsubmit="return false;">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="colab_email">Correo electrónico</label>
                            <input type="email" id="colab_email" name="email" required placeholder="correo@ejemplo.com">
                        </div>
                        <div class="form-group">
                            <label for="colab_password">Contraseña</label>
                            <input type="password" id="colab_password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="colab_nombre">Nombre completo</label>
                            <input type="text" id="colab_nombre" name="nombre" required placeholder="Nombre del colaborador">
                        </div>
                        <div class="form-group">
                            <label for="colab_rol">Rol</label>
                            <select id="colab_rol" name="rol">
                                <option value="COLABORADOR">Colaborador</option>
                                <option value="ADMINISTRADOR">Administrador</option>
                            </select>
                            <small class="form-hint">Colaborador: puede agregar y modificar. Administrador: todos los permisos.</small>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-save" id="btnCrearColaborador">
                            <i class="fas fa-user-plus"></i>
                            <span>Agregar colaborador</span>
                        </button>
                    </div>
                </form>

                <div class="colaboradores-list">
                    <h4 class="panel-subtitle">Usuarios registrados</h4>
                    <div id="listaColaboradores" class="table-card">
                        <table class="clients-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyColaboradores">
                                <tr><td colspan="5" class="text-muted">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="toast" class="toast"></div>
</section>

<?php
$extraScripts = <<<'PART1'
<script>
(function() {
    function showToast(message, isError) {
        var toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + (isError ? 'toast-error' : 'toast-success') + ' show';
        setTimeout(function() { toast.classList.remove('show'); }, 3000);
    }
    document.querySelectorAll('.settings-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var tabId = this.getAttribute('data-tab');
            document.querySelectorAll('.settings-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.settings-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            var panel = document.getElementById('panel-' + tabId);
            if (panel) panel.classList.add('active');
            if (tabId === 'colaboradores') loadColaboradores();
        });
    });

    var usuarioActualId = 
PART1 . (int)($_SESSION['usuario_id'] ?? 0) . <<<'PART2'
;

    function loadColaboradores() {
        var tbody = document.getElementById('tbodyColaboradores');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Cargando...</td></tr>';
        fetch('api_usuarios.php').then(function(r) { return r.json(); }).then(function(data) {
            if (!data.ok || !data.usuarios) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Error al cargar</td></tr>';
                return;
            }
            var usuarios = data.usuarios || [];
            if (usuarios.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">No hay usuarios registrados</td></tr>';
                return;
            }
            tbody.innerHTML = usuarios.map(function(u) {
                var estado = u.activo == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-pending">Inactivo</span>';
                var btnEliminar = '';
                if (u.usuario_id != usuarioActualId && u.activo == 1) {
                    btnEliminar = '<button type="button" class="btn-icon btn-delete" data-usuario-id="' + u.usuario_id + '" title="Eliminar"><i class="fas fa-trash-alt"></i></button>';
                }
                return '<tr><td>' + (u.nombre || '-') + '</td><td>' + (u.email || '-') + '</td><td>' + (u.rol || 'ADMINISTRADOR') + '</td><td>' + estado + '</td><td>' + btnEliminar + '</td></tr>';
            }).join('');
            tbody.querySelectorAll('.btn-icon.btn-delete[data-usuario-id]').forEach(function(btn) {
                btn.addEventListener('click', eliminarColaborador);
            });
        }).catch(function() {
            tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Error al cargar</td></tr>';
        });
    }

    function eliminarColaborador() {
        var id = this.getAttribute('data-usuario-id');
        if (!id) return;
        if (!confirm('¿Eliminar este colaborador? No podrá volver a iniciar sesión.')) return;
        var btn = this;
        btn.disabled = true;
        fetch('api_usuarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'eliminar', usuario_id: parseInt(id, 10) })
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.ok) {
                showToast('Colaborador eliminado correctamente.');
                loadColaboradores();
            } else {
                showToast(data.error || 'Error al eliminar', true);
                btn.disabled = false;
            }
        }).catch(function() {
            showToast('Error de conexión.', true);
            btn.disabled = false;
        });
    }

    var formColab = document.getElementById('formColaborador');
    if (formColab) {
        formColab.addEventListener('submit', function() {
            var btn = document.getElementById('btnCrearColaborador');
            var email = document.getElementById('colab_email').value.trim();
            var password = document.getElementById('colab_password').value;
            var nombre = document.getElementById('colab_nombre').value.trim();
            var rol = document.getElementById('colab_rol').value;
            if (!email || !password || !nombre) {
                showToast('Complete todos los campos.', true);
                return;
            }
            if (password.length < 6) {
                showToast('La contraseña debe tener al menos 6 caracteres.', true);
                return;
            }
            btn.disabled = true;
            fetch('api_usuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'crear', email: email, password: password, nombre: nombre, rol: rol })
            }).then(function(r) { return r.json(); }).then(function(data) {
                btn.disabled = false;
                if (data.ok) {
                    showToast('Colaborador agregado correctamente.');
                    document.getElementById('colab_email').value = '';
                    document.getElementById('colab_password').value = '';
                    document.getElementById('colab_nombre').value = '';
                    loadColaboradores();
                } else {
                    showToast(data.error || 'Error al agregar', true);
                }
            }).catch(function() {
                btn.disabled = false;
                showToast('Error de conexión.', true);
            });
        });
    }
})();
</script>
PART2;
require_once __DIR__ . '/../includes/footer.php';
?>
