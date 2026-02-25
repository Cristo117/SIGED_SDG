<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/usuario_model.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está autenticado, redirigir al panel
if (!empty($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Ingrese usuario y contraseña';
    } else {
        $usuario = obtenerUsuarioPorUsername($conn, $username);

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            $_SESSION['usuario_id'] = $usuario['usuario_id'];
            $_SESSION['username'] = $usuario['username'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['Olvido_contrasena'] = $usuario['olvido_contrasena'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGED - Iniciar sesión</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Ajuste específico para el logo en la tarjeta de login */
        .login-header img {
            width: 120px; /* Tamaño del logo */
            height: auto;
            margin-bottom: 20px;
        }
        .login-card {
            text-align: center;
        }
    </style>
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="siged.svg" alt="Logo SIGED">
                <p class="logo-subtitle">Sistema de Gestión Documental</p>
            </div>
            
            <form method="POST" class="login-form">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required autofocus
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <a href="recuperar_contrasena.php" class="forgot-password-link">¿Olvido su contraseña?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                </button>
            </form>
        </div>
    </div>
</body>

</html>