-- Script de compatibilidad para secmautos
-- Ejecutar en la base de datos secmautos_db

USE secmautos_db;

-- Verificar si existe la tabla usuarios
-- Si no existe, se asume que ya existe según el código

-- Agregar columna updated_at si no existe
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Agregar columna sistema_origen para compatibilidad
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS sistema_origen VARCHAR(50) DEFAULT 'secmautos';

-- Agregar columna primer_login si no existe
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS primer_login TINYINT(1) DEFAULT 1;

-- Agregar columna ultimo_login si no existe
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS ultimo_login DATETIME NULL;

-- Agregar columnas de bloqueo si no existen
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS intentos_fallidos INT DEFAULT 0;
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS bloqueado_hasta DATETIME NULL;

-- Agregar columna metadata para datos adicionales
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS metadata JSON NULL;

-- Agregar columna email si no existe (para compatibilidad)
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER username;

-- Agregar índices si no existen
ALTER TABLE usuarios ADD INDEX IF NOT EXISTS idx_activo (activo);
ALTER TABLE usuarios ADD INDEX IF NOT EXISTS idx_rol (rol);
ALTER TABLE usuarios ADD INDEX IF NOT EXISTS idx_email (email);

COMMIT;
