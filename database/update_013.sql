-- SEDEMA 2.2: ejecutar sobre la version 2.1/2.1.1 (012 aplicada).
-- Respaldar y detener Apache. Seleccionar la base antes de ejecutar.
-- No contiene borrados ni cambios de usuarios, ventas, pagos o cantidades.
SET NAMES utf8mb4;
DELIMITER $$
DROP PROCEDURE IF EXISTS sedema_update_013$$
CREATE PROCEDURE sedema_update_013()
BEGIN
    IF DATABASE() IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Seleccionar la base actual antes de actualizar.'; END IF;
    IF NOT EXISTS (SELECT 1 FROM schema_migration WHERE version='012_inventory_logistics_suppliers_20261022') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ejecutar primero update_012.sql sobre la base actual.';
    END IF;
    IF EXISTS (SELECT 1 FROM despacho WHERE estado NOT IN ('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO','CANCELADO'))
       OR EXISTS (SELECT 1 FROM despacho_estado_historial WHERE estadoNuevo NOT IN ('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO','CANCELADO') OR (estadoAnterior IS NOT NULL AND estadoAnterior NOT IN ('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO','CANCELADO'))) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Hay estados de despacho no reconocidos. Revisar antes de actualizar.';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='despacho' AND column_name='operationVersion') THEN
        ALTER TABLE despacho ADD COLUMN operationVersion INT UNSIGNED NOT NULL DEFAULT 0;
    END IF;
    ALTER TABLE despacho MODIFY COLUMN estado ENUM('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO','CANCELADO') NOT NULL DEFAULT 'EN_ESPERA';
    ALTER TABLE despacho_estado_historial MODIFY COLUMN estadoAnterior ENUM('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO','CANCELADO') DEFAULT NULL,
        MODIFY COLUMN estadoNuevo ENUM('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO','CANCELADO') NOT NULL;
    CREATE TABLE IF NOT EXISTS module_operation (
        operationKey CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        actorUserId BIGINT UNSIGNED NOT NULL,
        operationType VARCHAR(40) NOT NULL,
        payloadHash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        resultJson LONGTEXT DEFAULT NULL,
        createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completedAt DATETIME DEFAULT NULL,
        PRIMARY KEY(operationKey),
        KEY idx_module_operation_actor(actorUserId,createdAt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    INSERT INTO schema_migration(version,description) VALUES ('013_dispatch_operations_20261022','SEDEMA 2.2: cancelacion, reprogramacion e idempotencia de operaciones')
        ON DUPLICATE KEY UPDATE version=VALUES(version);
END$$
CALL sedema_update_013()$$
DROP PROCEDURE sedema_update_013$$
DELIMITER ;
