<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/ajustes.php';
require_once __DIR__ . '/../controllers/notificaciones.php';

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

$notificacionesCount = obtenerNotificacionesNoLeidas($conn, $_SESSION['usuario_id']);

require_once __DIR__ . '/../includes/header.php';
?>

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
