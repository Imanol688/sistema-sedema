-- SEDEMA v1.6 - Módulo Clientes
-- Ejecutar sobre sedema_db. La migración conserva datos existentes.

CREATE TABLE IF NOT EXISTS cliente (
    idCliente BIGINT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cuitDNI VARCHAR(20) NOT NULL,
    tipoCliente ENUM('PARTICULAR','ALBAÑIL','EMPRESA','MAYORISTA','CONTRATISTA') NOT NULL,
    razonSocial VARCHAR(150) NULL,
    telefono VARCHAR(50) NULL,
    direccion VARCHAR(255) NOT NULL,
    localidad VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idCliente),
    UNIQUE KEY uq_cliente_cuitdni (cuitDNI),
    KEY idx_cliente_busqueda (activo, apellido, nombre),
    KEY idx_cliente_tipo (tipoCliente, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE cliente
    ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
