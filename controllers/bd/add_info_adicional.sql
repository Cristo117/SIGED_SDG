-- Notas como pares título-valor usando tabla info_adicional
-- Ejecutar en phpMyAdmin o consola MySQL

-- 1. Ampliar valor en info_adicional (actualmente varchar 15)
ALTER TABLE `info_adicional` MODIFY COLUMN `valor` TEXT NOT NULL;

-- 2. Si ejecutaste la migración anterior que agregaba columnas, quítalas:
-- ALTER TABLE `cliente` DROP COLUMN IF EXISTS `info_adicional`;
-- ALTER TABLE `empleado` DROP COLUMN IF EXISTS `info_adicional`;
-- (En MySQL < 8.0 omitir las líneas anteriores si dan error)
