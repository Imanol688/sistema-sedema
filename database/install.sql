-- SEDEMA 2.2 | 2026-10-22 | INSTALACION LIMPIA EXCLUSIVAMENTE.
-- Base: estructura de sedema_db(1).sql; sin usuarios ni operaciones de prueba.
-- Requiere una base sedema_db vacia. No ejecutar en una instalacion existente.
-- El DDL no es transaccional; ante error detenerse y revisar, no usar --force.
CREATE DATABASE IF NOT EXISTS sedema_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sedema_db;
SET NAMES utf8mb4;
SET SESSION sql_mode='STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
SET SESSION default_storage_engine=InnoDB;


CREATE TABLE `ajuste` (
  `idAjuste` bigint UNSIGNED NOT NULL,
  `idPedido` bigint UNSIGNED NOT NULL,
  `tipoAjuste` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DESCUENTO',
  `tipoTarjeta` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  `montoCalculado` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auth_audit` (
  `idAudit` bigint UNSIGNED NOT NULL,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `eventType` enum('LOGIN_OK','LOGIN_FAIL','PASSWORD_RESET','LOGOUT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ipHash` binary(32) DEFAULT NULL,
  `userAgent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cliente` (
  `idCliente` bigint UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuitDNI` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipoCliente` enum('PARTICULAR','ALBAÑIL','EMPRESA','MAYORISTA','CONTRATISTA') COLLATE utf8mb4_unicode_ci NOT NULL,
  `razonSocial` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `localidad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `current_account_movement` (
  `idMovement` bigint UNSIGNED NOT NULL,
  `idCliente` bigint UNSIGNED NOT NULL,
  `idPedido` bigint UNSIGNED DEFAULT NULL,
  `idPago` bigint UNSIGNED DEFAULT NULL,
  `movementType` enum('DEBITO','CREDITO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `despacho` (
  `idDespacho` bigint UNSIGNED NOT NULL,
  `idPedido` bigint UNSIGNED NOT NULL,
  `idWarehouse` bigint UNSIGNED NOT NULL,
  `idVehiculo` bigint UNSIGNED DEFAULT NULL,
  `transportista` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaHoraProgramada` datetime NOT NULL,
  `destino` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modalidad` enum('EN_CORRALON','ENTREGA_DOMICILIO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_ESPERA',
  `observacionesEntrega` text COLLATE utf8mb4_unicode_ci,
  `creadoPor` bigint UNSIGNED DEFAULT NULL,
  `creadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `despachadoEn` datetime DEFAULT NULL,
  `entregadoEn` datetime DEFAULT NULL,
  `devueltoEn` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `despacho_estado_historial` (
  `idHistorial` bigint UNSIGNED NOT NULL,
  `idDespacho` bigint UNSIGNED NOT NULL,
  `estadoAnterior` enum('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estadoNuevo` enum('EN_ESPERA','EN_PREPARACION','EN_RUTA','ENTREGADO','DEVUELTO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `observaciones` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `creadoEn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detallepedido` (
  `idDetalle` bigint UNSIGNED NOT NULL,
  `idPedido` bigint UNSIGNED NOT NULL,
  `idProducto` bigint UNSIGNED DEFAULT NULL,
  `idInventoryProduct` bigint UNSIGNED DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `cantidadSolicitada` decimal(14,3) NOT NULL,
  `cantidadPendiente` decimal(14,3) NOT NULL,
  `precioUnitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detallerecepcion` (
  `idDetalleRecepcion` bigint UNSIGNED NOT NULL,
  `idRecepcion` bigint UNSIGNED NOT NULL,
  `idProducto` bigint UNSIGNED NOT NULL,
  `cantidadRecibida` decimal(14,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `detalleremito` (
  `idDetalleRemito` bigint UNSIGNED NOT NULL,
  `idRemitoProveedor` bigint UNSIGNED NOT NULL,
  `idProducto` bigint UNSIGNED NOT NULL,
  `cantidadSolicitada` decimal(14,3) NOT NULL,
  `precioUnitario` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `empleado` (
  `idEmpleado` bigint UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dni` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sueldoBase` decimal(12,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `factura` (
  `idFactura` bigint UNSIGNED NOT NULL,
  `idPedido` bigint UNSIGNED NOT NULL,
  `nroComprobante` bigint UNSIGNED NOT NULL,
  `puntoVenta` int UNSIGNED NOT NULL,
  `fechaEmision` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipoFactura` enum('A','B','C') COLLATE utf8mb4_unicode_ci NOT NULL,
  `totalFacturado` decimal(12,2) NOT NULL,
  `descuentoAplicado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `recargoAplicado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `firmayAclaracion` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nroEnvio` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_category` (
  `idCategory` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_movement` (
  `idMovement` bigint UNSIGNED NOT NULL,
  `idProduct` bigint UNSIGNED NOT NULL,
  `idWarehouse` bigint UNSIGNED NOT NULL,
  `movementType` enum('INGRESO','EGRESO','AJUSTE_POSITIVO','AJUSTE_NEGATIVO','TRANSFERENCIA_ENTRADA','TRANSFERENCIA_SALIDA') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(14,3) NOT NULL,
  `previousQuantity` decimal(14,3) NOT NULL,
  `resultingQuantity` decimal(14,3) NOT NULL,
  `observations` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actorUserId` bigint UNSIGNED DEFAULT NULL,
  `sourceModule` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INVENTARIO',
  `sourceReference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correlationId` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_product` (
  `idProduct` bigint UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `idCategory` bigint UNSIGNED NOT NULL,
  `idUnit` bigint UNSIGNED NOT NULL,
  `salePrice` decimal(12,2) NOT NULL DEFAULT '0.00',
  `customAttributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_stock` (
  `idProduct` bigint UNSIGNED NOT NULL,
  `idWarehouse` bigint UNSIGNED NOT NULL,
  `quantity` decimal(14,3) NOT NULL DEFAULT '0.000',
  `minimumStock` decimal(14,3) NOT NULL DEFAULT '0.000',
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_unit` (
  `idUnit` bigint UNSIGNED NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allowsDecimals` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_warehouse` (
  `idWarehouse` bigint UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `itemdespacho` (
  `idItemDespacho` bigint UNSIGNED NOT NULL,
  `idDespacho` bigint UNSIGNED NOT NULL,
  `idDetallePedido` bigint UNSIGNED NOT NULL,
  `cantidadADespachar` decimal(14,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `liquidacionsueldo` (
  `idLiquidacion` bigint UNSIGNED NOT NULL,
  `idEmpleado` bigint UNSIGNED NOT NULL,
  `idAdminLiquidador` bigint UNSIGNED NOT NULL,
  `periodo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sueldoBase` decimal(12,2) NOT NULL DEFAULT '0.00',
  `totalHaberes` decimal(12,2) NOT NULL DEFAULT '0.00',
  `totalDescuentos` decimal(12,2) NOT NULL DEFAULT '0.00',
  `montoNeto` decimal(12,2) NOT NULL,
  `numeroRecibo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaLiquidacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `login_attempt` (
  `idAttempt` bigint UNSIGNED NOT NULL,
  `identityHash` binary(32) NOT NULL,
  `ipHash` binary(32) NOT NULL,
  `attemptedAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pago` (
  `idPago` bigint UNSIGNED NOT NULL,
  `idPedido` bigint UNSIGNED NOT NULL,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `tipoOperacion` enum('PAGO','CUENTA_CORRIENTE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAGO',
  `fechaPago` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `importe` decimal(12,2) NOT NULL,
  `importeBase` decimal(12,2) DEFAULT NULL,
  `medioPago` enum('EFECTIVO','TARJETA','CHEQUE','TRANSFERENCIA','CONTROLADOR','CUENTA_CORRIENTE') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estadoPago` enum('PENDIENTE','APROBADO','RECHAZADO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'APROBADO',
  `apiKeySol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `puntoVentaID` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaccionExternaID` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_token` (
  `idToken` bigint UNSIGNED NOT NULL,
  `idUsuario` bigint UNSIGNED NOT NULL,
  `selector` char(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenHash` binary(32) NOT NULL,
  `expiresAt` datetime NOT NULL,
  `usedAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_adjustment` (
  `idPaymentAdjustment` bigint UNSIGNED NOT NULL,
  `idPago` bigint UNSIGNED NOT NULL,
  `tipoAjuste` enum('DESCUENTO','RECARGO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT '0.00',
  `montoCalculado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `appliedAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_audit` (
  `idAudit` bigint UNSIGNED NOT NULL,
  `idPago` bigint UNSIGNED DEFAULT NULL,
  `idPedido` bigint UNSIGNED NOT NULL,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `eventType` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_cash_detail` (
  `idPago` bigint UNSIGNED NOT NULL,
  `importeRecibido` decimal(12,2) NOT NULL,
  `vuelto` decimal(12,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_check_detail` (
  `idPago` bigint UNSIGNED NOT NULL,
  `banco` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numeroCheque` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titular` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fechaEmision` date NOT NULL,
  `fechaVencimiento` date NOT NULL,
  `observaciones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payment_gateway_request` (
  `idGatewayRequest` bigint UNSIGNED NOT NULL,
  `idPago` bigint UNSIGNED NOT NULL,
  `provider` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paymentMethod` enum('TARJETA','TRANSFERENCIA') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `externalId` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `qrPayload` text COLLATE utf8mb4_unicode_ci,
  `qrImageUrl` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `responseJson` longtext COLLATE utf8mb4_unicode_ci,
  `lastCheckedAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pedido` (
  `idPedido` bigint UNSIGNED NOT NULL,
  `idCliente` bigint UNSIGNED NOT NULL,
  `idUsuario` bigint UNSIGNED NOT NULL,
  `idWarehouse` bigint UNSIGNED DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE',
  `porcentajeDescuento` decimal(5,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `totalAjustes` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `modalidadOperativa` enum('EN_CORRALON','ENTREGA_DOMICILIO','ACOPIO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `direccionEntrega` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fechaEntrega` datetime DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personnel_payroll_item` (
  `idConcepto` bigint UNSIGNED NOT NULL,
  `idLiquidacion` bigint UNSIGNED NOT NULL,
  `tipo` enum('HABER','DESCUENTO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `concepto` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `importe` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `proveedor` (
  `idProveedor` bigint UNSIGNED NOT NULL,
  `razonSocial` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cuit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcionProveedor` text COLLATE utf8mb4_unicode_ci,
  `contacto` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `recepcioncompra` (
  `idRecepcion` bigint UNSIGNED NOT NULL,
  `idRemitoProveedor` bigint UNSIGNED NOT NULL,
  `idWarehouse` bigint UNSIGNED NOT NULL,
  `fechaRecepcion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `remitodespacho` (
  `idRemito` bigint UNSIGNED NOT NULL,
  `idDespacho` bigint UNSIGNED NOT NULL,
  `numeroRemito` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fechaEmision` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sucursalOrigen` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hojaRuta` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esDevolucion` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `remitoproveedor` (
  `idRemitoProveedor` bigint UNSIGNED NOT NULL,
  `idProveedor` bigint UNSIGNED NOT NULL,
  `numeroRemito` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fechaEmision` date NOT NULL,
  `estado` enum('BORRADOR','PENDIENTE','EMITIDA','RECEPCION_PARCIAL','RECEPCION_TOTAL','CANCELADA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDIENTE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales_order_audit` (
  `idAudit` bigint UNSIGNED NOT NULL,
  `idPedido` bigint UNSIGNED NOT NULL,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `eventType` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `schema_migration` (
  `version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `appliedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_access_audit` (
  `idAccessAudit` bigint UNSIGNED NOT NULL,
  `idUsuario` bigint UNSIGNED DEFAULT NULL,
  `actorUserId` bigint UNSIGNED DEFAULT NULL,
  `eventType` enum('ACCOUNT_CREATED','TEMP_CREDENTIAL_SENT','FIRST_ACCESS_COMPLETED','ACCOUNT_ENABLED','ACCOUNT_DISABLED','PERMISSIONS_CHANGED','EMAIL_CHANGED','ACCOUNT_DELETED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuario` (
  `idUsuario` bigint UNSIGNED NOT NULL,
  `idEmpleado` bigint UNSIGNED DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `passwordHash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mustChangePassword` tinyint(1) NOT NULL DEFAULT '0',
  `initialSetupCompleted` tinyint(1) NOT NULL DEFAULT '1',
  `profilePhoto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credentialSentAt` datetime DEFAULT NULL,
  `emailValidated` tinyint(1) NOT NULL DEFAULT '1',
  `deletedAt` datetime DEFAULT NULL,
  `deletedBy` bigint UNSIGNED DEFAULT NULL,
  `roles` enum('ADMINISTRADOR','VENDEDOR','PROVEEDOR','DEPOSITO','LOGISTICA','CAJA','ALMACEN','FINANZAS','SISTEMAS') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sector` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permisos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `habilitado` tinyint(1) NOT NULL DEFAULT '1',
  `failedAttempts` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `lockedUntil` datetime DEFAULT NULL,
  `ultimoAcceso` datetime DEFAULT NULL,
  `authVersion` int UNSIGNED NOT NULL DEFAULT '1',
  `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vehiculo` (
  `idVehiculo` bigint UNSIGNED NOT NULL,
  `matricula` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidadCarga` decimal(14,3) NOT NULL,
  `estado` enum('DISPONIBLE','OCUPADO','EN_MANTENIMIENTO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DISPONIBLE',
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `ajuste`
  ADD PRIMARY KEY (`idAjuste`),
  ADD KEY `idx_ajuste_pedido` (`idPedido`);

ALTER TABLE `auth_audit`
  ADD PRIMARY KEY (`idAudit`),
  ADD KEY `idx_audit_user_time` (`idUsuario`,`createdAt`),
  ADD KEY `idx_audit_event_time` (`eventType`,`createdAt`);

ALTER TABLE `cliente`
  ADD PRIMARY KEY (`idCliente`),
  ADD UNIQUE KEY `uq_cliente_cuit_dni` (`cuitDNI`);

ALTER TABLE `current_account_movement`
  ADD PRIMARY KEY (`idMovement`),
  ADD KEY `idx_current_account_client_time` (`idCliente`,`createdAt`),
  ADD KEY `fk_current_account_order` (`idPedido`),
  ADD KEY `fk_current_account_payment` (`idPago`);

ALTER TABLE `despacho`
  ADD PRIMARY KEY (`idDespacho`),
  ADD KEY `idx_despacho_pedido` (`idPedido`),
  ADD KEY `idx_despacho_warehouse` (`idWarehouse`),
  ADD KEY `idx_despacho_vehiculo_fecha` (`idVehiculo`,`fechaHoraProgramada`),
  ADD KEY `idx_despacho_estado_fecha` (`estado`,`fechaHoraProgramada`),
  ADD KEY `idx_despacho_creado_por` (`creadoPor`);

ALTER TABLE `despacho_estado_historial`
  ADD PRIMARY KEY (`idHistorial`),
  ADD KEY `idx_despacho_historial` (`idDespacho`,`creadoEn`),
  ADD KEY `fk_despacho_historial_usuario` (`idUsuario`);

ALTER TABLE `detallepedido`
  ADD PRIMARY KEY (`idDetalle`),
  ADD KEY `idx_detallepedido_pedido` (`idPedido`),
  ADD KEY `idx_detallepedido_producto` (`idProducto`),
  ADD KEY `idx_detalle_inventory_product` (`idInventoryProduct`);

ALTER TABLE `detallerecepcion`
  ADD PRIMARY KEY (`idDetalleRecepcion`),
  ADD KEY `idx_detallerecepcion_recepcion` (`idRecepcion`),
  ADD KEY `idx_detallerecepcion_producto` (`idProducto`);

ALTER TABLE `detalleremito`
  ADD PRIMARY KEY (`idDetalleRemito`),
  ADD KEY `idx_detalleremito_remito` (`idRemitoProveedor`),
  ADD KEY `idx_detalleremito_producto` (`idProducto`);

ALTER TABLE `empleado`
  ADD PRIMARY KEY (`idEmpleado`),
  ADD UNIQUE KEY `uq_empleado_dni` (`dni`);

ALTER TABLE `factura`
  ADD PRIMARY KEY (`idFactura`),
  ADD UNIQUE KEY `uq_factura_pedido` (`idPedido`),
  ADD UNIQUE KEY `uq_factura_comprobante` (`puntoVenta`,`nroComprobante`,`tipoFactura`);

ALTER TABLE `inventory_category`
  ADD PRIMARY KEY (`idCategory`),
  ADD UNIQUE KEY `uq_inventory_category_name` (`name`);

ALTER TABLE `inventory_movement`
  ADD PRIMARY KEY (`idMovement`),
  ADD UNIQUE KEY `uq_inventory_movement_source` (`sourceModule`,`sourceReference`,`movementType`,`idProduct`,`idWarehouse`),
  ADD KEY `idx_inventory_movement_product_time` (`idProduct`,`createdAt`),
  ADD KEY `idx_inventory_movement_warehouse_time` (`idWarehouse`,`createdAt`),
  ADD KEY `idx_inventory_movement_correlation` (`correlationId`);

ALTER TABLE `inventory_product`
  ADD PRIMARY KEY (`idProduct`),
  ADD UNIQUE KEY `uq_inventory_product_code` (`code`),
  ADD KEY `idx_inventory_product_search` (`active`,`name`),
  ADD KEY `idx_inventory_product_category` (`idCategory`,`active`),
  ADD KEY `fk_inventory_product_unit` (`idUnit`);

ALTER TABLE `inventory_stock`
  ADD PRIMARY KEY (`idProduct`,`idWarehouse`),
  ADD KEY `idx_inventory_stock_alert` (`idWarehouse`,`minimumStock`,`quantity`);

ALTER TABLE `inventory_unit`
  ADD PRIMARY KEY (`idUnit`),
  ADD UNIQUE KEY `uq_inventory_unit_code` (`code`),
  ADD UNIQUE KEY `uq_inventory_unit_name` (`name`);

ALTER TABLE `inventory_warehouse`
  ADD PRIMARY KEY (`idWarehouse`),
  ADD UNIQUE KEY `uq_inventory_warehouse_name` (`name`);

ALTER TABLE `itemdespacho`
  ADD PRIMARY KEY (`idItemDespacho`),
  ADD UNIQUE KEY `uq_itemdespacho_detail` (`idDespacho`,`idDetallePedido`),
  ADD KEY `idx_itemdespacho_detalle` (`idDetallePedido`);

ALTER TABLE `liquidacionsueldo`
  ADD PRIMARY KEY (`idLiquidacion`),
  ADD UNIQUE KEY `uq_liquidacion_empleado_periodo` (`idEmpleado`,`periodo`),
  ADD UNIQUE KEY `uq_liquidacion_numero_recibo` (`numeroRecibo`),
  ADD KEY `idx_liquidacion_admin` (`idAdminLiquidador`);

ALTER TABLE `login_attempt`
  ADD PRIMARY KEY (`idAttempt`),
  ADD KEY `idx_attempt_identity_time` (`identityHash`,`attemptedAt`),
  ADD KEY `idx_attempt_ip_time` (`ipHash`,`attemptedAt`);

ALTER TABLE `pago`
  ADD PRIMARY KEY (`idPago`),
  ADD UNIQUE KEY `uq_pago_transaccion_externa` (`transaccionExternaID`),
  ADD KEY `idx_pago_pedido` (`idPedido`,`fechaPago`),
  ADD KEY `idx_pago_usuario` (`idUsuario`);

ALTER TABLE `password_reset_token`
  ADD PRIMARY KEY (`idToken`),
  ADD UNIQUE KEY `uq_password_reset_selector` (`selector`),
  ADD KEY `idx_reset_user_status` (`idUsuario`,`usedAt`,`expiresAt`);

ALTER TABLE `payment_adjustment`
  ADD PRIMARY KEY (`idPaymentAdjustment`),
  ADD UNIQUE KEY `uq_payment_adjustment_payment` (`idPago`);

ALTER TABLE `payment_audit`
  ADD PRIMARY KEY (`idAudit`),
  ADD KEY `idx_payment_audit_order_time` (`idPedido`,`createdAt`),
  ADD KEY `idx_payment_audit_payment` (`idPago`),
  ADD KEY `idx_payment_audit_user` (`idUsuario`);

ALTER TABLE `payment_cash_detail`
  ADD PRIMARY KEY (`idPago`);

ALTER TABLE `payment_check_detail`
  ADD PRIMARY KEY (`idPago`),
  ADD KEY `idx_payment_check_number` (`numeroCheque`);

ALTER TABLE `payment_gateway_request`
  ADD PRIMARY KEY (`idGatewayRequest`),
  ADD UNIQUE KEY `uq_gateway_payment` (`idPago`),
  ADD UNIQUE KEY `uq_gateway_external` (`provider`,`externalId`);

ALTER TABLE `pedido`
  ADD PRIMARY KEY (`idPedido`),
  ADD KEY `idx_pedido_cliente` (`idCliente`,`fecha`),
  ADD KEY `idx_pedido_usuario` (`idUsuario`,`fecha`),
  ADD KEY `idx_pedido_warehouse` (`idWarehouse`);

ALTER TABLE `personnel_payroll_item`
  ADD PRIMARY KEY (`idConcepto`),
  ADD KEY `idx_payroll_item_liquidacion` (`idLiquidacion`);

ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`idProveedor`),
  ADD UNIQUE KEY `uq_proveedor_cuit` (`cuit`);

ALTER TABLE `recepcioncompra`
  ADD PRIMARY KEY (`idRecepcion`),
  ADD KEY `idx_recepcion_remito` (`idRemitoProveedor`),
  ADD KEY `idx_recepcion_warehouse` (`idWarehouse`);

ALTER TABLE `remitodespacho`
  ADD PRIMARY KEY (`idRemito`),
  ADD UNIQUE KEY `uq_remitodespacho_despacho` (`idDespacho`),
  ADD UNIQUE KEY `uq_remitodespacho_numero` (`numeroRemito`);

ALTER TABLE `remitoproveedor`
  ADD PRIMARY KEY (`idRemitoProveedor`),
  ADD UNIQUE KEY `uq_remito_proveedor_numero` (`idProveedor`,`numeroRemito`);

ALTER TABLE `sales_order_audit`
  ADD PRIMARY KEY (`idAudit`),
  ADD KEY `idx_sales_audit_order_time` (`idPedido`,`createdAt`),
  ADD KEY `idx_sales_audit_user` (`idUsuario`);

ALTER TABLE `schema_migration`
  ADD PRIMARY KEY (`version`);

ALTER TABLE `user_access_audit`
  ADD PRIMARY KEY (`idAccessAudit`),
  ADD KEY `idx_access_audit_user_time` (`idUsuario`,`createdAt`),
  ADD KEY `idx_access_audit_actor_time` (`actorUserId`,`createdAt`);

ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `uq_usuario_username` (`username`),
  ADD UNIQUE KEY `uq_usuario_email` (`email`),
  ADD UNIQUE KEY `uq_usuario_empleado` (`idEmpleado`),
  ADD KEY `idx_usuario_estado` (`habilitado`,`lockedUntil`);

ALTER TABLE `vehiculo`
  ADD PRIMARY KEY (`idVehiculo`),
  ADD UNIQUE KEY `uq_vehiculo_matricula` (`matricula`);

ALTER TABLE `ajuste`
  MODIFY `idAjuste` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `auth_audit`
  MODIFY `idAudit` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `cliente`
  MODIFY `idCliente` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `current_account_movement`
  MODIFY `idMovement` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `despacho`
  MODIFY `idDespacho` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `despacho_estado_historial`
  MODIFY `idHistorial` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `detallepedido`
  MODIFY `idDetalle` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `detallerecepcion`
  MODIFY `idDetalleRecepcion` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `detalleremito`
  MODIFY `idDetalleRemito` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `empleado`
  MODIFY `idEmpleado` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `factura`
  MODIFY `idFactura` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `inventory_category`
  MODIFY `idCategory` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `inventory_movement`
  MODIFY `idMovement` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `inventory_product`
  MODIFY `idProduct` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `inventory_unit`
  MODIFY `idUnit` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `inventory_warehouse`
  MODIFY `idWarehouse` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `itemdespacho`
  MODIFY `idItemDespacho` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `liquidacionsueldo`
  MODIFY `idLiquidacion` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `login_attempt`
  MODIFY `idAttempt` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `pago`
  MODIFY `idPago` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `password_reset_token`
  MODIFY `idToken` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_adjustment`
  MODIFY `idPaymentAdjustment` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_audit`
  MODIFY `idAudit` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `payment_gateway_request`
  MODIFY `idGatewayRequest` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `pedido`
  MODIFY `idPedido` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `personnel_payroll_item`
  MODIFY `idConcepto` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `proveedor`
  MODIFY `idProveedor` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `recepcioncompra`
  MODIFY `idRecepcion` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `remitodespacho`
  MODIFY `idRemito` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `remitoproveedor`
  MODIFY `idRemitoProveedor` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `sales_order_audit`
  MODIFY `idAudit` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `user_access_audit`
  MODIFY `idAccessAudit` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuario`
  MODIFY `idUsuario` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `vehiculo`
  MODIFY `idVehiculo` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `ajuste`
  ADD CONSTRAINT `fk_ajuste_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `auth_audit`
  ADD CONSTRAINT `fk_audit_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL;

ALTER TABLE `current_account_movement`
  ADD CONSTRAINT `fk_current_account_client` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_current_account_order` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_current_account_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `despacho`
  ADD CONSTRAINT `fk_despacho_creado_por` FOREIGN KEY (`creadoPor`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despacho_inventory_warehouse` FOREIGN KEY (`idWarehouse`) REFERENCES `inventory_warehouse` (`idWarehouse`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despacho_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despacho_vehiculo` FOREIGN KEY (`idVehiculo`) REFERENCES `vehiculo` (`idVehiculo`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `despacho_estado_historial`
  ADD CONSTRAINT `fk_despacho_historial_despacho` FOREIGN KEY (`idDespacho`) REFERENCES `despacho` (`idDespacho`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_despacho_historial_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `detallepedido`
  ADD CONSTRAINT `fk_detalle_inventory_product` FOREIGN KEY (`idInventoryProduct`) REFERENCES `inventory_product` (`idProduct`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detallepedido_inventory_product` FOREIGN KEY (`idProducto`) REFERENCES `inventory_product` (`idProduct`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detallepedido_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `detallerecepcion`
  ADD CONSTRAINT `fk_detallerecepcion_inventory_product` FOREIGN KEY (`idProducto`) REFERENCES `inventory_product` (`idProduct`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detallerecepcion_recepcion` FOREIGN KEY (`idRecepcion`) REFERENCES `recepcioncompra` (`idRecepcion`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `detalleremito`
  ADD CONSTRAINT `fk_detalleremito_inventory_product` FOREIGN KEY (`idProducto`) REFERENCES `inventory_product` (`idProduct`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalleremito_remito` FOREIGN KEY (`idRemitoProveedor`) REFERENCES `remitoproveedor` (`idRemitoProveedor`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `factura`
  ADD CONSTRAINT `fk_factura_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON UPDATE CASCADE;

ALTER TABLE `inventory_movement`
  ADD CONSTRAINT `fk_inventory_movement_product` FOREIGN KEY (`idProduct`) REFERENCES `inventory_product` (`idProduct`),
  ADD CONSTRAINT `fk_inventory_movement_warehouse` FOREIGN KEY (`idWarehouse`) REFERENCES `inventory_warehouse` (`idWarehouse`);

ALTER TABLE `inventory_product`
  ADD CONSTRAINT `fk_inventory_product_category` FOREIGN KEY (`idCategory`) REFERENCES `inventory_category` (`idCategory`),
  ADD CONSTRAINT `fk_inventory_product_unit` FOREIGN KEY (`idUnit`) REFERENCES `inventory_unit` (`idUnit`);

ALTER TABLE `inventory_stock`
  ADD CONSTRAINT `fk_inventory_stock_product` FOREIGN KEY (`idProduct`) REFERENCES `inventory_product` (`idProduct`),
  ADD CONSTRAINT `fk_inventory_stock_warehouse` FOREIGN KEY (`idWarehouse`) REFERENCES `inventory_warehouse` (`idWarehouse`);

ALTER TABLE `itemdespacho`
  ADD CONSTRAINT `fk_itemdespacho_despacho` FOREIGN KEY (`idDespacho`) REFERENCES `despacho` (`idDespacho`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_itemdespacho_detallepedido` FOREIGN KEY (`idDetallePedido`) REFERENCES `detallepedido` (`idDetalle`) ON UPDATE CASCADE;

ALTER TABLE `liquidacionsueldo`
  ADD CONSTRAINT `fk_liquidacion_admin` FOREIGN KEY (`idAdminLiquidador`) REFERENCES `usuario` (`idUsuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_liquidacion_empleado` FOREIGN KEY (`idEmpleado`) REFERENCES `empleado` (`idEmpleado`) ON UPDATE CASCADE;

ALTER TABLE `pago`
  ADD CONSTRAINT `fk_pago_pedido` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pago_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `password_reset_token`
  ADD CONSTRAINT `fk_reset_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE;

ALTER TABLE `payment_adjustment`
  ADD CONSTRAINT `fk_payment_adjustment_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `payment_audit`
  ADD CONSTRAINT `fk_payment_audit_order` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_audit_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_payment_audit_user` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `payment_cash_detail`
  ADD CONSTRAINT `fk_payment_cash_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `payment_check_detail`
  ADD CONSTRAINT `fk_payment_check_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `payment_gateway_request`
  ADD CONSTRAINT `fk_gateway_payment` FOREIGN KEY (`idPago`) REFERENCES `pago` (`idPago`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `pedido`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`idCliente`) REFERENCES `cliente` (`idCliente`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedido_warehouse` FOREIGN KEY (`idWarehouse`) REFERENCES `inventory_warehouse` (`idWarehouse`) ON UPDATE CASCADE;

ALTER TABLE `personnel_payroll_item`
  ADD CONSTRAINT `fk_payroll_item_liquidacion` FOREIGN KEY (`idLiquidacion`) REFERENCES `liquidacionsueldo` (`idLiquidacion`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `recepcioncompra`
  ADD CONSTRAINT `fk_recepcion_inventory_warehouse` FOREIGN KEY (`idWarehouse`) REFERENCES `inventory_warehouse` (`idWarehouse`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_recepcion_remito` FOREIGN KEY (`idRemitoProveedor`) REFERENCES `remitoproveedor` (`idRemitoProveedor`) ON UPDATE CASCADE;

ALTER TABLE `remitodespacho`
  ADD CONSTRAINT `fk_remitodespacho_despacho` FOREIGN KEY (`idDespacho`) REFERENCES `despacho` (`idDespacho`) ON UPDATE CASCADE;

ALTER TABLE `remitoproveedor`
  ADD CONSTRAINT `fk_remitoprov_proveedor` FOREIGN KEY (`idProveedor`) REFERENCES `proveedor` (`idProveedor`) ON UPDATE CASCADE;

ALTER TABLE `sales_order_audit`
  ADD CONSTRAINT `fk_sales_audit_order` FOREIGN KEY (`idPedido`) REFERENCES `pedido` (`idPedido`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_audit_user` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `user_access_audit`
  ADD CONSTRAINT `fk_access_audit_actor` FOREIGN KEY (`actorUserId`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_access_audit_user` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_empleado` FOREIGN KEY (`idEmpleado`) REFERENCES `empleado` (`idEmpleado`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `inventory_stock` ADD CONSTRAINT `ck_012_stock` CHECK (quantity >= 0 AND minimumStock >= 0);

ALTER TABLE `inventory_product` ADD CONSTRAINT `ck_012_price` CHECK (salePrice >= 0);

ALTER TABLE `inventory_movement` ADD CONSTRAINT `ck_012_movement` CHECK (quantity > 0 AND previousQuantity >= 0 AND resultingQuantity >= 0 AND resultingQuantity = previousQuantity + CASE WHEN movementType IN ('EGRESO','AJUSTE_NEGATIVO','TRANSFERENCIA_SALIDA') THEN -quantity ELSE quantity END);

ALTER TABLE `detalleremito` ADD CONSTRAINT `ck_012_purchase_line` CHECK (cantidadSolicitada > 0 AND precioUnitario > 0);

ALTER TABLE `detallerecepcion` ADD CONSTRAINT `ck_012_receipt` CHECK (cantidadRecibida > 0);

ALTER TABLE `itemdespacho` ADD CONSTRAINT `ck_012_dispatch_item` CHECK (cantidadADespachar > 0);

ALTER TABLE `vehiculo` ADD CONSTRAINT `ck_012_capacity` CHECK (capacidadCarga > 0);

ALTER TABLE detalleremito ADD UNIQUE KEY uq_012_purchase_product (idRemitoProveedor,idProducto);

ALTER TABLE detallerecepcion ADD UNIQUE KEY uq_012_receipt_product (idRecepcion,idProducto);

INSERT INTO `inventory_category` (`idCategory`, `name`, `description`, `active`, `createdAt`, `updatedAt`) VALUES
(1, 'Sin categoría', 'Categoría inicial para productos pendientes de clasificación', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

INSERT INTO `inventory_unit` (`idUnit`, `code`, `name`, `symbol`, `allowsDecimals`, `active`, `createdAt`, `updatedAt`) VALUES
(1, 'UN', 'Unidad', 'un', 0, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(2, 'KG', 'Kilogramo', 'kg', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(3, 'TN', 'Tonelada', 't', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(4, 'M', 'Metro', 'm', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(5, 'M2', 'Metro cuadrado', 'm²', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(6, 'M3', 'Metro cúbico', 'm³', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
(7, 'BATEA', 'Batea', 'batea', 1, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

INSERT INTO `inventory_warehouse` (`idWarehouse`, `name`, `address`, `description`, `active`, `createdAt`, `updatedAt`) VALUES
(1, 'Depósito principal', NULL, 'Ubicación inicial del inventario', 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

INSERT INTO schema_migration(version,description) VALUES ('012_inventory_logistics_suppliers_20261022','SEDEMA 2.1: instalacion limpia y depuracion de Inventario, Logistica y Proveedores');

-- Complemento 2.2: operacion y cancelacion de despachos.
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
