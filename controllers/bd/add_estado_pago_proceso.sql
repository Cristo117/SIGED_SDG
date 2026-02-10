-- Agregar estado_pago a proceso_cliente (cada proceso puede estar PENDIENTE o AL_DIA)
ALTER TABLE proceso_cliente ADD COLUMN estado_pago VARCHAR(30) DEFAULT 'PENDIENTE';

-- Actualizar registros existentes a PENDIENTE si la columna ya existe con valor NULL
UPDATE proceso_cliente SET estado_pago = 'PENDIENTE' WHERE estado_pago IS NULL;
