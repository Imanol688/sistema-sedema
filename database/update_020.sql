-- Solo para una instalacion v2.2 validada. Seleccionar la base actual.
-- No usar sobre el SQL del otro grupo: para ese origen usar migrate_to_integrated.php.
-- Respaldar y detener Apache. El DDL no se revierte integralmente.
SET NAMES utf8mb4;
DELIMITER $$
DROP PROCEDURE IF EXISTS sedema_update_020$$
CREATE PROCEDURE sedema_update_020()
BEGIN
 IF NOT EXISTS(SELECT 1 FROM schema_migration WHERE version='013_dispatch_operations_20261022') THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Requiere v2.2 validada. Para el otro origen usar migrate_to_integrated.php.';
 END IF;
 IF EXISTS(SELECT idPedido FROM factura GROUP BY idPedido HAVING COUNT(*)>1)
 OR EXISTS(SELECT nroComprobante FROM factura GROUP BY nroComprobante HAVING COUNT(*)>1) THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Facturas duplicadas. Revisar antes de integrar.';
 END IF;
 ALTER TABLE pago MODIFY medioPago ENUM('EFECTIVO','TARJETA','CHEQUE','TRANSFERENCIA','CONTROLADOR','CUENTA_CORRIENTE','MERCADO_PAGO_QR') NULL;
CREATE TABLE IF NOT EXISTS mp_qr_order (
    idPago BIGINT UNSIGNED NOT NULL PRIMARY KEY,
    externalReference VARCHAR(64) NOT NULL,
    idempotencyKey VARCHAR(36) NOT NULL,
    externalOrderId VARCHAR(80) NULL,
    posExternalId VARCHAR(40) NOT NULL,
    sellerId VARCHAR(40) NOT NULL,
    environment VARCHAR(12) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'LOCAL_PENDING',
    statusDetail VARCHAR(80) NULL,
    requestJson LONGTEXT NOT NULL,
    responseJson LONGTEXT NULL,
    lastError VARCHAR(255) NULL,
    checkedAt DATETIME NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mp_reference (externalReference),
    UNIQUE KEY uq_mp_idempotency (idempotencyKey),
    UNIQUE KEY uq_mp_external_order (externalOrderId),
    CONSTRAINT fk_mp_qr_payment FOREIGN KEY (idPago) REFERENCES pago(idPago) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mp_qr_pos_lock (
    posExternalId VARCHAR(100) NOT NULL PRIMARY KEY,
    activePaymentId BIGINT UNSIGNED NULL,
    CONSTRAINT fk_mp_qr_pos_payment FOREIGN KEY (activePaymentId) REFERENCES pago(idPago) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

 IF NOT EXISTS(SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='factura' AND NON_UNIQUE=0 GROUP BY INDEX_NAME HAVING COUNT(*)=1 AND MAX(COLUMN_NAME)='idPedido') THEN
  ALTER TABLE factura ADD UNIQUE KEY uq_factura_sale(idPedido);
 END IF;
 IF NOT EXISTS(SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='factura' AND NON_UNIQUE=0 GROUP BY INDEX_NAME HAVING COUNT(*)=1 AND MAX(COLUMN_NAME)='nroComprobante') THEN
  ALTER TABLE factura ADD UNIQUE KEY uq_factura_random_number(nroComprobante);
 END IF;
 IF EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='sales_order_audit' AND CONSTRAINT_NAME='fk_sales_audit_order' AND CONSTRAINT_TYPE='FOREIGN KEY') THEN
  ALTER TABLE sales_order_audit DROP FOREIGN KEY fk_sales_audit_order;
 END IF;
 INSERT INTO schema_migration(version,description) VALUES('020_integration_20261023','Integracion de modulos con Ventas/Clientes/Usuarios/Pagos QR') ON DUPLICATE KEY UPDATE version=VALUES(version);
END$$
CALL sedema_update_020()$$
DROP PROCEDURE sedema_update_020$$
DELIMITER ;
