-- Script de compatibilidad para secmalquileres
-- Ejecutar en la base de datos gestion_alquileres

USE gestion_alquileres;

-- Verificar si existe la tabla users
-- Si no existe, se asume que ya existe según el código

-- Agregar columna updated_at si no existe
ALTER TABLE users
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Agregar columna created_at si no existe
ALTER TABLE users
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- Agregar columna sistema_origen para compatibilidad
ALTER TABLE users
ADD COLUMN IF NOT EXISTS sistema_origen VARCHAR(50) DEFAULT 'secmalquileres';

-- Agregar columna primer_login si no existe
ALTER TABLE users
ADD COLUMN IF NOT EXISTS primer_login TINYINT(1) DEFAULT 1;

-- Agregar columna ultimo_login si no existe
ALTER TABLE users
ADD COLUMN IF NOT EXISTS ultimo_login DATETIME NULL;

-- Agregar columnas de bloqueo si no existen
ALTER TABLE users
ADD COLUMN IF NOT EXISTS intentos_fallidos INT DEFAULT 0;
ALTER TABLE users
ADD COLUMN IF NOT EXISTS bloqueado_hasta DATETIME NULL;

-- Agregar columna metadata para datos adicionales
ALTER TABLE users
ADD COLUMN IF NOT EXISTS metadata JSON NULL;

-- Agregar índices si no existen
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_activo (activo);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_rol (rol);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_fecha_registro (fecha_registro);

COMMIT;
