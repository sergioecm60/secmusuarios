-- Migración: Crear tabla de auditoría
-- Ejecutar: mysql -u root -p usuarios < db/migrations/001_create_audit_log.sql

CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT NULL COMMENT 'ID del usuario que realiza la acción (NULL si es sistema o login fallido)',
    `username` VARCHAR(50) NULL COMMENT 'Username al momento de la acción',
    `action` VARCHAR(50) NOT NULL COMMENT 'Tipo de acción: login_success, login_failed, logout, user_create, user_update, user_delete, token_refresh, account_locked, account_unlocked',
    `target_type` VARCHAR(50) NULL COMMENT 'Tipo de entidad afectada: user, token, session',
    `target_id` INT NULL COMMENT 'ID de la entidad afectada',
    `old_values` JSON NULL COMMENT 'Valores anteriores (para updates)',
    `new_values` JSON NULL COMMENT 'Valores nuevos (para creates/updates)',
    `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP del cliente (soporta IPv6)',
    `user_agent` VARCHAR(500) NULL COMMENT 'User agent del navegador',
    `additional_data` JSON NULL COMMENT 'Datos adicionales específicos de la acción',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_audit_user_id` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_created_at` (`created_at`),
    INDEX `idx_audit_target` (`target_type`, `target_id`),
    INDEX `idx_audit_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
