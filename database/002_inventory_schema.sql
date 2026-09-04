-- Módulo de Inventario SEDEMA - MySQL 8.
-- Ejecutar después de 001_auth_schema.sql sobre la base configurada en .env.
-- No incluye USE para evitar acoplar la migración a un nombre de base específico.

CREATE TABLE IF NOT EXISTS inventory_category (
    idCategory BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_category_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_unit (
    idUnit BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL,
    name VARCHAR(80) NOT NULL,
    symbol VARCHAR(15) NOT NULL,
    allowsDecimals TINYINT(1) NOT NULL DEFAULT 1,
    active TINYINT(1) NOT NULL DEFAULT 1,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_unit_code (code),
    UNIQUE KEY uq_inventory_unit_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_warehouse (
    idWarehouse BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NULL,
    description VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory_warehouse_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_product (
    idProduct BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    idCategory BIGINT UNSIGNED NOT NULL,
    idUnit BIGINT UNSIGNED NOT NULL,
    salePrice DECIMAL(12,2) NOT NULL DEFAULT 0,
    customAttributes JSON NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_product_category FOREIGN KEY (idCategory)
        REFERENCES inventory_category(idCategory),
    CONSTRAINT fk_inventory_product_unit FOREIGN KEY (idUnit)
        REFERENCES inventory_unit(idUnit),
    UNIQUE KEY uq_inventory_product_code (code),
    INDEX idx_inventory_product_search (active, name),
    INDEX idx_inventory_product_category (idCategory, active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_stock (
    idProduct BIGINT UNSIGNED NOT NULL,
    idWarehouse BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(14,3) NOT NULL DEFAULT 0,
    minimumStock DECIMAL(14,3) NOT NULL DEFAULT 0,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idProduct, idWarehouse),
    CONSTRAINT fk_inventory_stock_product FOREIGN KEY (idProduct)
        REFERENCES inventory_product(idProduct),
    CONSTRAINT fk_inventory_stock_warehouse FOREIGN KEY (idWarehouse)
        REFERENCES inventory_warehouse(idWarehouse),
    CONSTRAINT chk_inventory_stock_quantity CHECK (quantity >= 0),
    CONSTRAINT chk_inventory_stock_minimum CHECK (minimumStock >= 0),
    INDEX idx_inventory_stock_alert (idWarehouse, minimumStock, quantity)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_movement (
    idMovement BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idProduct BIGINT UNSIGNED NOT NULL,
    idWarehouse BIGINT UNSIGNED NOT NULL,
    movementType ENUM(
        'INGRESO',
        'EGRESO',
        'AJUSTE_POSITIVO',
        'AJUSTE_NEGATIVO',
        'TRANSFERENCIA_ENTRADA',
        'TRANSFERENCIA_SALIDA'
    ) NOT NULL,
    quantity DECIMAL(14,3) NOT NULL,
    previousQuantity DECIMAL(14,3) NOT NULL,
    resultingQuantity DECIMAL(14,3) NOT NULL,
    observations VARCHAR(500) NOT NULL,
    actorUserId BIGINT UNSIGNED NULL COMMENT 'Referencia lógica a usuario; sin FK para mantener módulos desacoplados',
    sourceModule VARCHAR(40) NOT NULL DEFAULT 'INVENTARIO',
    sourceReference VARCHAR(100) NULL,
    correlationId CHAR(36) NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_movement_product FOREIGN KEY (idProduct)
        REFERENCES inventory_product(idProduct),
    CONSTRAINT fk_inventory_movement_warehouse FOREIGN KEY (idWarehouse)
        REFERENCES inventory_warehouse(idWarehouse),
    CONSTRAINT chk_inventory_movement_quantity CHECK (quantity > 0),
    INDEX idx_inventory_movement_product_time (idProduct, createdAt),
    INDEX idx_inventory_movement_warehouse_time (idWarehouse, createdAt),
    UNIQUE KEY uq_inventory_movement_source
        (sourceModule, sourceReference, movementType, idProduct, idWarehouse),
    INDEX idx_inventory_movement_correlation (correlationId)
) ENGINE=InnoDB;

INSERT INTO inventory_unit (code, name, symbol, allowsDecimals) VALUES
    ('UN', 'Unidad', 'un', 0),
    ('KG', 'Kilogramo', 'kg', 1),
    ('TN', 'Tonelada', 't', 1),
    ('M', 'Metro', 'm', 1),
    ('M2', 'Metro cuadrado', 'm²', 1),
    ('M3', 'Metro cúbico', 'm³', 1),
    ('BATEA', 'Batea', 'batea', 1)
ON DUPLICATE KEY UPDATE code = VALUES(code);

INSERT INTO inventory_warehouse (name, description)
VALUES ('Depósito principal', 'Ubicación inicial del inventario')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO inventory_category (name, description)
VALUES ('Sin categoría', 'Categoría inicial para productos pendientes de clasificación')
ON DUPLICATE KEY UPDATE name = VALUES(name);
