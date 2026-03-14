<?php

require_once __DIR__ . '/env.php';
load_env();

if (getenv('APP_ENV') !== 'development') {
    @ini_set('display_errors', 0);
    @ini_set('display_startup_errors', 0);
    @error_reporting(0);
}

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'siged_sdg';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("SIGED DB Error: " . $e->getMessage());
    die("Error de conexión a la base de datos. Contacte al administrador.");
}

// Migración: columna activo en cliente (soft delete)
try {
    $conn->query("SELECT activo FROM cliente LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'activo') !== false) {
        $conn->exec("ALTER TABLE cliente ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");
    }
}

// Migración: columna rol en usuario_admin
try {
    $conn->query("SELECT rol FROM usuario_admin LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'rol') !== false) {
        $conn->exec("ALTER TABLE usuario_admin ADD COLUMN rol VARCHAR(20) NOT NULL DEFAULT 'ADMINISTRADOR'");
    }
}

// Migración: fecha_vencimiento_pago en proceso_cliente
try {
    $conn->query("SELECT fecha_vencimiento_pago FROM proceso_cliente LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'fecha_vencimiento_pago') !== false) {
        $conn->exec("ALTER TABLE proceso_cliente ADD COLUMN fecha_vencimiento_pago DATE DEFAULT NULL");
        $conn->exec("UPDATE proceso_cliente SET fecha_vencimiento_pago = LAST_DAY(fecha_asignacion) WHERE fecha_vencimiento_pago IS NULL");
    }
}

// Migración: cobrar_seguridad_social_mensual en cliente
try {
    $conn->query("SELECT cobrar_seguridad_social_mensual FROM cliente LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'cobrar_seguridad_social_mensual') !== false) {
        $conn->exec("ALTER TABLE cliente ADD COLUMN cobrar_seguridad_social_mensual TINYINT(1) NOT NULL DEFAULT 1");
    }
}

// Migración: fecha_pago en proceso_cliente (para historial de pagos)
try {
    $conn->query("SELECT fecha_pago FROM proceso_cliente LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'fecha_pago') !== false) {
        $conn->exec("ALTER TABLE proceso_cliente ADD COLUMN fecha_pago DATETIME DEFAULT NULL");
        $conn->exec("UPDATE proceso_cliente SET fecha_pago = creado_at WHERE estado_pago = 'AL_DIA' AND fecha_pago IS NULL");
    }
}

// Migración: tabla cliente_documento
try {
    $conn->query("SELECT 1 FROM cliente_documento LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("CREATE TABLE cliente_documento (
        documento_id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        nombre_tipo VARCHAR(100) NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        nombre_original VARCHAR(255) NOT NULL,
        extension VARCHAR(20) NOT NULL,
        creado_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cliente (cliente_id)
    )");
}

// Migración: tabla historial_actividad
try {
    $conn->query("SELECT 1 FROM historial_actividad LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("CREATE TABLE historial_actividad (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        usuario_nombre VARCHAR(150) NOT NULL,
        usuario_rol VARCHAR(20) NOT NULL DEFAULT 'COLABORADOR',
        accion VARCHAR(50) NOT NULL,
        descripcion VARCHAR(500) NOT NULL,
        detalle TEXT,
        creado_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_creado (creado_at),
        INDEX idx_usuario (usuario_id),
        INDEX idx_rol (usuario_rol)
    )");
}
