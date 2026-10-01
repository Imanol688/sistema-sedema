-- SEDEMA v2.0 - Integración Clientes/Usuarios + Despacho/Logística
-- Ejecutar una sola vez sobre sedema_db, después de 009_payments_diagram_alignment.sql.
-- Compatible con MariaDB 10.4 (XAMPP) y MySQL 8.

USE sedema_db;

CREATE TABLE IF NOT EXISTS schema_migration (
    version VARCHAR(50) NOT NULL PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    appliedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
DROP PROCEDURE IF EXISTS migrate_010_integration_merge$$
CREATE PROCEDURE migrate_010_integration_merge()
migration: BEGIN
    IF EXISTS (SELECT 1 FROM schema_migration WHERE version = '010_integration_merge') THEN
        LEAVE migration;
    END IF;

    ALTER TABLE vehiculo
        ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1 AFTER estado;

    ALTER TABLE despacho
        ADD COLUMN IF NOT EXISTS idWarehouse BIGINT UNSIGNED NULL AFTER idPedido,
        ADD COLUMN IF NOT EXISTS transportista VARCHAR(150) NULL AFTER idVehiculo,
        ADD COLUMN IF NOT EXISTS creadoPor BIGINT NULL AFTER observacionesEntrega,
        ADD COLUMN IF NOT EXISTS creadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER creadoPor,
        ADD COLUMN IF NOT EXISTS actualizadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER creadoEn,
        ADD COLUMN IF NOT EXISTS despachadoEn DATETIME NULL AFTER actualizadoEn,
        ADD COLUMN IF NOT EXISTS entregadoEn DATETIME NULL AFTER despachadoEn,
        ADD COLUMN IF NOT EXISTS devueltoEn DATETIME NULL AFTER entregadoEn;

    ALTER TABLE detallepedido
        ADD COLUMN IF NOT EXISTS idInventoryProduct BIGINT UNSIGNED NULL AFTER idProducto,
        MODIFY COLUMN cantidadSolicitada DECIMAL(14,3) NOT NULL,
        MODIFY COLUMN cantidadPendiente DECIMAL(14,3) NOT NULL;

    ALTER TABLE itemdespacho
        MODIFY COLUMN cantidadADespachar DECIMAL(14,3) NOT NULL;

    ALTER TABLE vehiculo
        MODIFY COLUMN capacidadCarga DECIMAL(14,3) NOT NULL;

    -- Unifica pedidos históricos y pedidos creados por el módulo nuevo.
    UPDATE detallepedido dp
    INNER JOIN inventory_product ip ON ip.idProduct = dp.idProducto
    SET dp.idInventoryProduct = ip.idProduct
    WHERE dp.idInventoryProduct IS NULL;

    -- Evita que una referencia inválida bloquee la clave foránea canónica.
    UPDATE detallepedido dp
    LEFT JOIN inventory_product ip ON ip.idProduct = dp.idInventoryProduct
    SET dp.idInventoryProduct = NULL
    WHERE dp.idInventoryProduct IS NOT NULL AND ip.idProduct IS NULL;

    UPDATE despacho d
    INNER JOIN pedido p ON p.idPedido = d.idPedido
    SET d.idWarehouse = p.idWarehouse
    WHERE d.idWarehouse IS NULL AND p.idWarehouse IS NOT NULL;

    UPDATE despacho d
    SET d.idWarehouse = (SELECT MIN(w.idWarehouse) FROM inventory_warehouse w WHERE w.active = 1)
    WHERE d.idWarehouse IS NULL;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'despacho' AND INDEX_NAME = 'idx_despacho_warehouse'
    ) THEN
        ALTER TABLE despacho ADD KEY idx_despacho_warehouse (idWarehouse);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'despacho' AND INDEX_NAME = 'idx_despacho_creado_por'
    ) THEN
        ALTER TABLE despacho ADD KEY idx_despacho_creado_por (creadoPor);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'detallepedido' AND INDEX_NAME = 'idx_detalle_inventory_product'
    ) THEN
        ALTER TABLE detallepedido ADD KEY idx_detalle_inventory_product (idInventoryProduct);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'despacho' AND CONSTRAINT_NAME = 'fk_despacho_warehouse'
    ) THEN
        ALTER TABLE despacho ADD CONSTRAINT fk_despacho_warehouse
            FOREIGN KEY (idWarehouse) REFERENCES inventory_warehouse(idWarehouse)
            ON DELETE RESTRICT ON UPDATE CASCADE;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'despacho' AND CONSTRAINT_NAME = 'fk_despacho_creado_por'
    ) THEN
        ALTER TABLE despacho ADD CONSTRAINT fk_despacho_creado_por
            FOREIGN KEY (creadoPor) REFERENCES usuario(idUsuario)
            ON DELETE SET NULL ON UPDATE CASCADE;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'detallepedido' AND CONSTRAINT_NAME = 'fk_detalle_inventory_product'
    ) THEN
        ALTER TABLE detallepedido ADD CONSTRAINT fk_detalle_inventory_product
            FOREIGN KEY (idInventoryProduct) REFERENCES inventory_product(idProduct)
            ON DELETE RESTRICT ON UPDATE CASCADE;
    END IF;

    CREATE TABLE IF NOT EXISTS despacho_estado_historial (
        idHistorial BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        idDespacho BIGINT NOT NULL,
        estadoAnterior ENUM('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO') NULL,
        estadoNuevo ENUM('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO') NOT NULL,
        observaciones VARCHAR(500) NULL,
        idUsuario BIGINT NULL,
        creadoEn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (idHistorial),
        KEY idx_despacho_historial (idDespacho, creadoEn),
        CONSTRAINT fk_despacho_historial_despacho FOREIGN KEY (idDespacho)
            REFERENCES despacho(idDespacho) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_despacho_historial_usuario FOREIGN KEY (idUsuario)
            REFERENCES usuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Los remitos creados por la rama Clientes no incluían renglones de despacho.
    -- Se completa únicamente un despacho que todavía no tenga ítems propios.
    INSERT INTO itemdespacho (idDespacho, idDetallePedido, cantidadADespachar)
    SELECT d.idDespacho, dp.idDetalle, dp.cantidadPendiente
    FROM despacho d
    INNER JOIN detallepedido dp ON dp.idPedido = d.idPedido
    WHERE dp.cantidadPendiente > 0
      AND NOT EXISTS (
          SELECT 1 FROM itemdespacho ix WHERE ix.idDespacho = d.idDespacho
      );

    INSERT INTO despacho_estado_historial (idDespacho, estadoAnterior, estadoNuevo, observaciones, idUsuario, creadoEn)
    SELECT d.idDespacho, NULL, d.estado, 'Estado incorporado durante la integración.', d.creadoPor, d.creadoEn
    FROM despacho d
    WHERE NOT EXISTS (
        SELECT 1 FROM despacho_estado_historial h WHERE h.idDespacho = d.idDespacho
    );

    INSERT INTO schema_migration (version, description)
    VALUES ('010_integration_merge', 'Compatibilidad entre Clientes/Usuarios y Despacho/Logística');
END$$
CALL migrate_010_integration_merge()$$
DROP PROCEDURE migrate_010_integration_merge$$
DELIMITER ;
