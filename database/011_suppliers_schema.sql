-- SEDEMA · actualización 011: Proveedores y compras
-- Seleccionar previamente la base sedema_db. Ejecutar una vez luego del esquema estable.
-- Idempotente para los cinco objetos de esta fase: conserva datos existentes.
-- Requiere las tablas inventory_product e inventory_warehouse.
-- No importar sedema_er.sql sobre una instalación existente.

CREATE TABLE IF NOT EXISTS `proveedor` (
  `idProveedor` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `razonSocial` VARCHAR(150) NOT NULL,
  `cuit` VARCHAR(20) NOT NULL,
  `descripcionProveedor` TEXT DEFAULT NULL,
  `contacto` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`idProveedor`),
  UNIQUE KEY `uq_proveedor_cuit` (`cuit`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `remitoproveedor` (
  `idRemitoProveedor` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idProveedor` BIGINT UNSIGNED NOT NULL,
  `numeroRemito` VARCHAR(50) NOT NULL,
  `fechaEmision` DATE NOT NULL,
  `estado` ENUM('BORRADOR','PENDIENTE','EMITIDA','RECEPCION_PARCIAL','RECEPCION_TOTAL','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
  PRIMARY KEY (`idRemitoProveedor`),
  UNIQUE KEY `uq_remito_proveedor_numero` (`idProveedor`,`numeroRemito`),
  CONSTRAINT `fk_remitoprov_proveedor` FOREIGN KEY (`idProveedor`) REFERENCES `proveedor` (`idProveedor`) ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `detalleremito` (
  `idDetalleRemito` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idRemitoProveedor` BIGINT UNSIGNED NOT NULL,
  `idProducto` BIGINT UNSIGNED NOT NULL,
  `cantidadSolicitada` DECIMAL(14,3) NOT NULL,
  `precioUnitario` DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`idDetalleRemito`),
  KEY `idx_detalleremito_remito` (`idRemitoProveedor`),
  KEY `idx_detalleremito_producto` (`idProducto`),
  CONSTRAINT `fk_detalleremito_inventory_product` FOREIGN KEY (`idProducto`) REFERENCES `inventory_product` (`idProduct`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalleremito_remito` FOREIGN KEY (`idRemitoProveedor`) REFERENCES `remitoproveedor` (`idRemitoProveedor`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `recepcioncompra` (
  `idRecepcion` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idRemitoProveedor` BIGINT UNSIGNED NOT NULL,
  `idWarehouse` BIGINT UNSIGNED NOT NULL,
  `fechaRecepcion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` TEXT DEFAULT NULL,
  PRIMARY KEY (`idRecepcion`),
  KEY `idx_recepcion_remito` (`idRemitoProveedor`),
  KEY `idx_recepcion_warehouse` (`idWarehouse`),
  CONSTRAINT `fk_recepcion_inventory_warehouse` FOREIGN KEY (`idWarehouse`) REFERENCES `inventory_warehouse` (`idWarehouse`) ON UPDATE CASCADE,
  CONSTRAINT `fk_recepcion_remito` FOREIGN KEY (`idRemitoProveedor`) REFERENCES `remitoproveedor` (`idRemitoProveedor`) ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `detallerecepcion` (
  `idDetalleRecepcion` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `idRecepcion` BIGINT UNSIGNED NOT NULL,
  `idProducto` BIGINT UNSIGNED NOT NULL,
  `cantidadRecibida` DECIMAL(14,3) NOT NULL,
  PRIMARY KEY (`idDetalleRecepcion`),
  KEY `idx_detallerecepcion_recepcion` (`idRecepcion`),
  KEY `idx_detallerecepcion_producto` (`idProducto`),
  CONSTRAINT `fk_detallerecepcion_inventory_product` FOREIGN KEY (`idProducto`) REFERENCES `inventory_product` (`idProduct`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detallerecepcion_recepcion` FOREIGN KEY (`idRecepcion`) REFERENCES `recepcioncompra` (`idRecepcion`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
