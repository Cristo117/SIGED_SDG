<?php

/**
 * Script para crear el usuario administrador inicial.
 * Ejecutar una vez después de importar la base de datos.
 * Eliminar o proteger este archivo en producción.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/configuracion_model.php';
require_once __DIR__ . '/../models/usuario_model.php';

$username = 'admin';
$password = 'admin123';
$nombre = 'Administrador';
$email = 'admin@siged.local';

// Insertar valor de afiliación por defecto si no existe
asegurarConfiguracionInicial($conn);

// Verificar si ya existe un admin
if (existeUsuarioAdmin($conn)) {
    die('Ya existe al menos un usuario. No se creó el admin por defecto.');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
crearUsuarioAdmin($conn, $username, $email, $hash, $nombre);

echo "Usuario administrador creado correctamente.\n";
echo "Usuario: $username\n";
echo "Contraseña: $password\n";
echo "\n¡IMPORTANTE! Cambie la contraseña desde Ajustes después del primer inicio de sesión.\n";
echo "Elimine o proteja este archivo (crear_admin.php) en producción.\n";
