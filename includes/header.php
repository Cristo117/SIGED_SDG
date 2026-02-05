<?php
$pageTitle = $pageTitle ?? 'SIGED';
$activePage = $activePage ?? 'inicio';
$usuario = getUsuarioActual();
$partes = explode(' ', trim($usuario['nombre']));
$iniciales = count($partes) >= 2
    ? strtoupper(substr($partes[0], 0, 1) . substr($partes[1], 0, 1))
    : strtoupper(substr($usuario['nombre'], 0, 2));
$iniciales = substr($iniciales, 0, 2) ?: 'AD';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel SIGED - <?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <h1 class="logo">SIGED</h1>
                <p class="logo-subtitle">Sistema de Gestión</p>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?= $activePage === 'inicio' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
                <a href="clientes.php" class="nav-item <?= $activePage === 'clientes' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Clientes</span>
                </a>
                <a href="reportes.php" class="nav-item <?= $activePage === 'reportes' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
                <a href="ajustes.php" class="nav-item <?= $activePage === 'ajustes' ? 'active' : '' ?>">
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
                <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
                <div class="header-right">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                        <?php if (!empty($notificacionesCount) && $notificacionesCount > 0): ?>
                        <span class="notification-badge"><?= (int)$notificacionesCount ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="user-profile">
                        <div class="user-avatar"><?= htmlspecialchars($iniciales) ?></div>
                        <span class="user-name"><?= htmlspecialchars($usuario['nombre']) ?></span>
                    </div>
                    <a href="logout.php" class="btn-logout" title="Cerrar sesión">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>
            <div class="content-area">
