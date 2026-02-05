<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/ajustes.php';

requireAuth();

$pageTitle = 'Ajustes';
$activePage = 'ajustes';
$usuario = getUsuarioActual();

$msg = '';
$msgError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    
    if ($accion === 'perfil') {
        $nombre = trim($_POST['nombreCompleto'] ?? '');
        $email = trim($_POST['correo'] ?? '');
        if ($nombre && $email) {
            if (actualizarPerfil($_SESSION['usuario_id'], $nombre, $email)) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['email'] = $email;
                $msg = 'Perfil guardado correctamente.';
            } else {
                $msgError = 'No se pudo actualizar el perfil.';
            }
        }
    } elseif ($accion === 'seguridad') {
        $actual = $_POST['passwordActual'] ?? '';
        $nueva = $_POST['passwordNueva'] ?? '';
        $confirmar = $_POST['passwordConfirmar'] ?? '';
        if ($nueva !== $confirmar) {
            $msgError = 'La nueva contraseña y la confirmación no coinciden.';
        } elseif (strlen($nueva) < 6) {
            $msgError = 'La contraseña debe tener al menos 6 caracteres.';
        } elseif (cambiarPassword($_SESSION['usuario_id'], $actual, $nueva)) {
            $msg = 'Contraseña actualizada correctamente.';
        } else {
            $msgError = 'Contraseña actual incorrecta.';
        }
    }
}

$notificacionesCount = 0;
if (!empty($_SESSION['usuario_id'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM notificacion WHERE usuario_id = ? AND leida = 0");
    $stmt->execute([$_SESSION['usuario_id']]);
    $notificacionesCount = (int) $stmt->fetch()['total'];
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="settings-section">
    <h2 class="section-title">Configuración del Sistema</h2>
    <p class="section-subtitle">Gestiona tu perfil y las preferencias del sistema</p>

    <?php if ($msg): ?>
    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($msgError): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($msgError) ?></div>
    <?php endif; ?>

    <div class="settings-tabs">
        <button type="button" class="settings-tab active" data-tab="perfil">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </button>
        <button type="button" class="settings-tab" data-tab="notificaciones">
            <i class="fas fa-bell"></i>
            <span>Notificaciones</span>
        </button>
        <button type="button" class="settings-tab" data-tab="seguridad">
            <i class="fas fa-shield-alt"></i>
            <span>Seguridad</span>
        </button>
    </div>

    <!-- Pestaña Perfil -->
    <div class="settings-panel active" id="panel-perfil">
        <div class="profile-card">
            <h3 class="panel-subtitle">Información del Perfil</h3>
            <p class="panel-desc">Actualiza tu información personal</p>
            <div class="profile-user-block">
                <div class="profile-avatar" id="profileAvatar"><?= htmlspecialchars(strtoupper(substr($usuario['nombre'], 0, 2))) ?></div>
                <div class="profile-user-info">
                    <span class="profile-name"><?= htmlspecialchars($usuario['nombre']) ?></span>
                    <span class="profile-role">Administrador</span>
                </div>
            </div>
            <form method="POST" id="formPerfil" class="profile-form">
                <input type="hidden" name="accion" value="perfil">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombreCompleto">Nombre Completo</label>
                        <input type="text" id="nombreCompleto" name="nombreCompleto" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="correo">Correo Electrónico</label>
                        <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="rol">Rol</label>
                        <input type="text" id="rol" name="rol" value="Administrador" readonly>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i>
                        <span>Guardar Cambios</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pestaña Notificaciones -->
    <div class="settings-panel" id="panel-notificaciones">
        <div class="profile-card">
            <h3 class="panel-subtitle">Preferencias de Notificaciones</h3>
            <p class="panel-desc">Configura cómo y cuándo recibir alertas</p>
            <form id="formNotificaciones" class="notifications-form">
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="emailAlertas" id="emailAlertas" checked>
                        <span>Recibir alertas por correo electrónico</span>
                    </label>
                </div>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="notifPagos" id="notifPagos" checked>
                        <span>Notificaciones de pagos pendientes</span>
                    </label>
                </div>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="notifClientes" id="notifClientes" checked>
                        <span>Nuevos clientes registrados</span>
                    </label>
                </div>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="notifDocumentos" id="notifDocumentos">
                        <span>Documentos aprobados o rechazados</span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-save" id="btnGuardarNotif">
                        <i class="fas fa-save"></i>
                        <span>Guardar Preferencias</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pestaña Seguridad -->
    <div class="settings-panel" id="panel-seguridad">
        <div class="profile-card">
            <h3 class="panel-subtitle">Cambiar Contraseña</h3>
            <p class="panel-desc">Actualiza tu contraseña de acceso</p>
            <form method="POST" id="formSeguridad" class="security-form">
                <input type="hidden" name="accion" value="seguridad">
                <div class="form-group">
                    <label for="passwordActual">Contraseña actual</label>
                    <input type="password" id="passwordActual" name="passwordActual" placeholder="Introduce tu contraseña actual" required>
                </div>
                <div class="form-group">
                    <label for="passwordNueva">Nueva contraseña</label>
                    <input type="password" id="passwordNueva" name="passwordNueva" placeholder="Nueva contraseña" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="passwordConfirmar">Confirmar nueva contraseña</label>
                    <input type="password" id="passwordConfirmar" name="passwordConfirmar" placeholder="Repite la nueva contraseña" required minlength="6">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-key"></i>
                        <span>Cambiar Contraseña</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<?php
$extraScripts = <<<'SCRIPT'
<script>
(function() {
    var STORAGE_NOTIF = 'siged_notificaciones';
    document.querySelectorAll('.settings-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            var tabId = this.getAttribute('data-tab');
            document.querySelectorAll('.settings-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.settings-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            var panel = document.getElementById('panel-' + tabId);
            if (panel) panel.classList.add('active');
        });
    });
    function loadNotificaciones() {
        try {
            var data = JSON.parse(localStorage.getItem(STORAGE_NOTIF));
            if (data) {
                document.getElementById('emailAlertas').checked = data.emailAlertas !== false;
                document.getElementById('notifPagos').checked = data.notifPagos !== false;
                document.getElementById('notifClientes').checked = data.notifClientes !== false;
                document.getElementById('notifDocumentos').checked = data.notifDocumentos === true;
            }
        } catch (e) {}
    }
    function showToast(message, isError) {
        var toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + (isError ? 'toast-error' : 'toast-success') + ' show';
        setTimeout(function() { toast.classList.remove('show'); }, 3000);
    }
    document.getElementById('btnGuardarNotif').addEventListener('click', function() {
        var data = {
            emailAlertas: document.getElementById('emailAlertas').checked,
            notifPagos: document.getElementById('notifPagos').checked,
            notifClientes: document.getElementById('notifClientes').checked,
            notifDocumentos: document.getElementById('notifDocumentos').checked
        };
        localStorage.setItem(STORAGE_NOTIF, JSON.stringify(data));
        showToast('Preferencias de notificaciones guardadas.');
    });
    loadNotificaciones();
})();
</script>
SCRIPT;
require_once __DIR__ . '/../includes/footer.php';
?>
