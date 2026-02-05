<?php

/**
 * Script para crear el usuario administrador inicial.
 * Ejecutar una vez después de importar la base de datos.
 * Eliminar o proteger este archivo en producción.
 */

require_once __DIR__ . '/../config/db.php';

$username = 'admin';
$password = 'admin123';
$nombre = 'Administrador';
$email = 'admin@siged.local';

// Insertar valor de afiliación por defecto si no existe
$stmt = $conn->query("SELECT COUNT(*) as n FROM configuracion");
if ((int) $stmt->fetch()['n'] === 0) {
    $conn->exec("INSERT INTO configuracion (valor_afiliacion, activo) VALUES (250000, 1)");
}

// Verificar si ya existe un admin
$stmt = $conn->query("SELECT COUNT(*) as n FROM usuario_admin");
if ((int) $stmt->fetch()['n'] > 0) {
    die('Ya existe al menos un usuario. No se creó el admin por defecto.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO usuario_admin (username, email, password_hash, nombre, activo) VALUES (?, ?, ?, ?, 1)");
$stmt->execute([$username, $email, $hash, $nombre]);

echo "Usuario administrador creado correctamente.\n";
echo "Usuario: $username\n";
echo "Contraseña: $password\n";
echo "\n¡IMPORTANTE! Cambie la contraseña desde Ajustes después del primer inicio de sesión.\n";
echo "Elimine o proteja este archivo (crear_admin.php) en producción.\n";
