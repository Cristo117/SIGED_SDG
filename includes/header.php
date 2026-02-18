<?php
// 1. Configuración de Títulos y Usuario
$pageTitle = $pageTitle ?? 'SIGED';
$activePage = $activePage ?? 'inicio';
$usuario = getUsuarioActual();

// 2. Lógica de Rutas Dinámicas
$archivo_actual = basename($_SERVER['PHP_SELF']);
$en_raiz = ($archivo_actual == 'index.php' || $archivo_actual == 'login.php' || $archivo_actual == 'logout.php');

$ruta_to_public = $en_raiz ? 'public/' : '';
$ruta_to_raiz = $en_raiz ? '' : '../';

// 3. Lógica de Iniciales
$nombre_usuario = $usuario['nombre'] ?? 'Admin';
$partes = explode(' ', trim($nombre_usuario));
$iniciales = count($partes) >= 2
    ? strtoupper(substr($partes[0], 0, 1) . substr($partes[1], 0, 1))
    : strtoupper(substr($nombre_usuario, 0, 2));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel SIGED - <?= htmlspecialchars($pageTitle) ?></title>
    
    <link rel="icon" type="image/svg+xml" href="<?= $ruta_to_raiz ?>siged.svg">
    <link rel="stylesheet" href="<?= $ruta_to_raiz ?>assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Estilos para el Logo SVG */
        .sidebar-header {
            display: flex;
            justify-content: center;
            padding: 20px 0;
        }

        .logo-svg {
            /* Aquí pones el color que quieras (Hexadecimal o RGB) */
            color: ##FFFFFF; 
            transition: color 0.3s ease;
            width: 120px;
            height: auto;
        }

        .logo-svg:hover {
            color: #3498db; /* Color al pasar el mouse */
        }

        /* Asegúrate de que el path del SVG use fill="currentColor" */
    </style>

    <?php if (!empty($loadChartJs)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <?php if (!empty($extraCss)): ?>
    <?= $extraCss ?>
    <?php endif; ?>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-header">
    <a href="<?= $ruta_to_raiz ?>index.php" class="logo-link" style="display: flex; justify-content: center; padding: 20px 0;">
        <img src="<?= $ruta_to_raiz ?>siged.svg" 
             alt="Logo SIGED" 
             style="width: 150px; height: auto; filter: brightness(0) invert(1);">
    </a>
</div>
            
            <nav class="sidebar-nav">
                <a href="<?= $ruta_to_raiz ?>index.php" class="nav-item <?= $activePage === 'inicio' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                
                <a href="<?= $ruta_to_public ?>clientes.php" class="nav-item <?= $activePage === 'clientes' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>
                
                <a href="<?= $ruta_to_public ?>reportes.php" class="nav-item <?= $activePage === 'reportes' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
                
                <a href="<?= $ruta_to_public ?>ajustes.php" class="nav-item <?= $activePage === 'ajustes' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Ajustes</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <p>&copy; <?= date('Y') ?> SIGED System</p>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                     <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
                
                <div class="header-right">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                        <?php if (!empty($notificacionesCount) && $notificacionesCount > 0): ?>
                        <span class="notification-badge"><?= (int)$notificacionesCount ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="user-profile">
                        <div class="user-avatar"><?= htmlspecialchars($iniciales) ?></div>
                        <span class="user-name"><?= htmlspecialchars($nombre_usuario) ?></span>
                    </div>
                    
                    <a href="<?= $ruta_to_raiz ?>logout.php" class="btn-logout" title="Cerrar sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>
            
            <div class="content-area">