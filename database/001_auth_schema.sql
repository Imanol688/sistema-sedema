-- Esquema MySQL 8 para el módulo inicial de autenticación SEDEMA.
-- Ejecutar sobre una base nueva. Para una base ya existente, comparar la tabla usuario antes de aplicar ALTER.

CREATE DATABASE IF NOT EXISTS sedema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sedema;

CREATE TABLE IF NOT EXISTS empleado (
    idEmpleado BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(50) NULL,
    sueldoBase DECIMAL(12,2) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS usuario (
    idUsuario BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idEmpleado BIGINT UNSIGNED NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    passwordHash VARCHAR(255) NOT NULL,
    roles ENUM('ADMINISTRADOR','VENDEDOR','PROVEEDOR','DEPOSITO','LOGISTICA') NOT NULL,
    permisos JSON NULL,
    habilitado TINYINT(1) NOT NULL DEFAULT 1,
    failedAttempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    lockedUntil DATETIME NULL,
    ultimoAcceso DATETIME NULL,
    authVersion INT UNSIGNED NOT NULL DEFAULT 1,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_empleado FOREIGN KEY (idEmpleado) REFERENCES empleado(idEmpleado) ON DELETE SET NULL,
    INDEX idx_usuario_estado (habilitado, lockedUntil)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempt (
    idAttempt BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identityHash BINARY(32) NOT NULL,
    ipHash BINARY(32) NOT NULL,
    attemptedAt DATETIME NOT NULL,
    INDEX idx_attempt_identity_time (identityHash, attemptedAt),
    INDEX idx_attempt_ip_time (ipHash, attemptedAt)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_reset_token (
    idToken BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario BIGINT UNSIGNED NOT NULL,
    selector CHAR(18) NOT NULL UNIQUE,
    tokenHash BINARY(32) NOT NULL,
    expiresAt DATETIME NOT NULL,
    usedAt DATETIME NULL,
    createdAt DATETIME NOT NULL,
    CONSTRAINT fk_reset_usuario FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario) ON DELETE CASCADE,
    INDEX idx_reset_user_status (idUsuario, usedAt, expiresAt)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS auth_audit (
    idAudit BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario BIGINT UNSIGNED NULL,
    eventType ENUM('LOGIN_OK','LOGIN_FAIL','PASSWORD_RESET','LOGOUT') NOT NULL,
    ipHash BINARY(32) NULL,
    userAgent VARCHAR(255) NULL,
    createdAt DATETIME NOT NULL,
    CONSTRAINT fk_audit_usuario FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario) ON DELETE SET NULL,
    INDEX idx_audit_user_time (idUsuario, createdAt),
    INDEX idx_audit_event_time (eventType, createdAt)
) ENGINE=InnoDB;

-- Limpieza recomendada mediante evento o tarea programada diaria:
-- DELETE FROM login_attempt WHERE attemptedAt < DATE_SUB(NOW(), INTERVAL 24 HOUR);
-- DELETE FROM password_reset_token WHERE createdAt < DATE_SUB(NOW(), INTERVAL 7 DAY);

