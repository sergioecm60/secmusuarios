-- Migracion: Crear tabla de refresh tokens
-- Ejecutar: mysql -u root -p usuarios < db/migrations/002_create_refresh_tokens.sql

CREATE TABLE IF NOT EXISTS `refresh_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL COMMENT 'SHA256 del token',
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `revoked_at` DATETIME NULL COMMENT 'Cuando fue revocado (logout)',
    `user_agent` VARCHAR(500) NULL,
    `ip_address` VARCHAR(45) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_token_hash` (`token_hash`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`),
    INDEX `idx_revoked_at` (`revoked_at`),
    CONSTRAINT `fk_refresh_tokens_user` FOREIGN KEY (`user_id`)
        REFERENCES `users_master`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
