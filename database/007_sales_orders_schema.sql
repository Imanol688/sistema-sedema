-- SEDEMA v1.7 - Módulo 2: Clientes, Pedidos y Comprobantes
-- Ejecutar una sola vez sobre sedema_db. Conserva tablas y datos existentes.

ALTER TABLE pedido
    ADD COLUMN IF NOT EXISTS idWarehouse BIGINT UNSIGNED NULL AFTER idUsuario,
    ADD COLUMN IF NOT EXISTS subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER porcentajeDescuento,
    ADD COLUMN IF NOT EXISTS totalAjustes DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER subtotal,
    ADD COLUMN IF NOT EXISTS direccionEntrega VARCHAR(255) NULL AFTER modalidadOperativa,
    ADD COLUMN IF NOT EXISTS fechaEntrega DATETIME NULL AFTER direccionEntrega,
    ADD COLUMN IF NOT EXISTS observaciones TEXT NULL AFTER fechaEntrega,
    ADD COLUMN IF NOT EXISTS updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Compatibilidad con el modelo histórico: idProducto puede quedar NULL para pedidos nuevos,
-- que referencian el catálogo canónico de inventario mediante idInventoryProduct.
ALTER TABLE detallepedido
    MODIFY COLUMN idProducto BIGINT NULL,
    ADD COLUMN IF NOT EXISTS idInventoryProduct BIGINT UNSIGNED NULL AFTER idProducto;

ALTER TABLE ajuste
    ADD COLUMN IF NOT EXISTS tipoAjuste VARCHAR(40) NOT NULL DEFAULT 'DESCUENTO' AFTER idPedido,
    ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) NULL AFTER tipoTarjeta;

ALTER TABLE despacho
    ADD COLUMN IF NOT EXISTS transportista VARCHAR(150) NULL AFTER idVehiculo;

ALTER TABLE factura
    ADD COLUMN IF NOT EXISTS observaciones VARCHAR(255) NULL AFTER nroEnvio;

CREATE TABLE IF NOT EXISTS sales_order_audit (
    idAudit BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    idPedido BIGINT NOT NULL,
    idUsuario BIGINT NULL,
    eventType VARCHAR(40) NOT NULL,
    detail VARCHAR(500) NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idAudit),
    KEY idx_sales_audit_order_time (idPedido, createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
