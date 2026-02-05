# SIGED - Sistema de Gestión Documental

# HEAD
El proyecto SIGED es un Sistema de Gestion Documental diseñado para centralizar y administrar información de forma eficiente.
Su objetivo es facilitar el registro, consulta, actualización y control de datos mediante una plataforma clara y accesible, optimizando procesos, reduciendo errores y mejorando la productividad del usuario.

===============================
Sistema de gestión documental integrado con base de datos MySQL/MariaDB.

## Requisitos

- PHP 7.4+ con extensiones: PDO, pdo_mysql, session
- MySQL 5.7+ o MariaDB 10.4+
- Servidor web (Apache, Nginx) o PHP built-in server

## Instalación

1. **Configurar la base de datos**
   - Crear la base de datos `siged_sdg`
   - Importar el archivo `controllers/bd/siged_sdg (1).sql` en phpMyAdmin o línea de comandos

2. **Configurar la conexión**
   - Copiar `config/db.example.php` a `config/db.php` (o editar `config/db.php` si ya existe)
   - Ajustar host, usuario y contraseña según tu entorno

3. **Crear usuario administrador**
   - Ejecutar en el navegador: `http://localhost/SIGED_SDG/public/crear_admin.php`
   - Usuario: `admin` / Contraseña: `admin123`
   - **Importante:** Cambiar la contraseña desde Ajustes y eliminar `crear_admin.php` en producción

4. **Acceder al sistema**
   - URL: `http://localhost/SIGED_SDG/public/` (o la ruta según tu configuración)
   - Iniciar sesión con las credenciales del paso 3

## Estructura del proyecto

- `public/` - Páginas PHP accesibles (index, clientes, reportes, ajustes, login)
- `includes/` - Header, footer, autenticación
- `controllers/` - Lógica de negocio y consultas a BD
- `config/` - Configuración de base de datos
- `assets/` - CSS y JavaScript

## Páginas integradas

| Página | Descripción |
|-------|-------------|
| Inicio | Panel general con métricas y últimos clientes |
| Clientes | Gestión de clientes (CRUD, filtros, búsqueda) |
| Reportes | Contabilidad, gráficos, cuentas por cliente |
| Ajustes | Perfil, notificaciones, cambio de contraseña |

## Nota

La carpeta `assets/sistema_front/` contenía los archivos HTML provisionales. Ya están integrados en los archivos PHP correspondientes y pueden eliminarse cuando se confirme que todo funciona correctamente.
8b1b4cd (Cambios locales antes de sincronizar con GitHub)
