-- SEDEMA v1.9 - Módulo 3: Pagos y Cobranzas
-- Ejecutar una sola vez sobre sedema_db.

ALTER TABLE pago
    ADD COLUMN IF NOT EXISTS idUsuario BIGINT NULL AFTER idPedido;

CREATE TABLE IF NOT EXISTS payment_adjustment (
    idPaymentAdjustment BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    idPago BIGINT NOT NULL,
    tipoAjuste ENUM('DESCUENTO','RECARGO') NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    montoCalculado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    appliedAt DATETIME NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idPaymentAdjustment),
    UNIQUE KEY uq_payment_adjustment_payment (idPago),
    CONSTRAINT fk_payment_adjustment_payment FOREIGN KEY (idPago) REFERENCES pago(idPago) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_gateway_request (
    idGatewayRequest BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    idPago BIGINT NOT NULL,
    provider VARCHAR(60) NOT NULL,
    externalId VARCHAR(150) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'PENDING',
    qrPayload TEXT NULL,
    qrImageUrl VARCHAR(500) NULL,
    responseJson LONGTEXT NULL,
    lastCheckedAt DATETIME NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (idGatewayRequest),
    UNIQUE KEY uq_gateway_payment (idPago),
    UNIQUE KEY uq_gateway_external (provider,externalId),
    CONSTRAINT fk_gateway_payment FOREIGN KEY (idPago) REFERENCES pago(idPago) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS current_account_movement (
    idMovement BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    idCliente BIGINT NOT NULL,
    idPedido BIGINT NULL,
    idPago BIGINT NULL,
    movementType ENUM('DEBITO','CREDITO') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description VARCHAR(255) NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idMovement),
    KEY idx_current_account_client_time (idCliente,createdAt),
    CONSTRAINT fk_current_account_client FOREIGN KEY (idCliente) REFERENCES cliente(idCliente),
    CONSTRAINT fk_current_account_order FOREIGN KEY (idPedido) REFERENCES pedido(idPedido) ON DELETE SET NULL,
    CONSTRAINT fk_current_account_payment FOREIGN KEY (idPago) REFERENCES pago(idPago) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_audit (
    idAudit BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    idPago BIGINT NULL,
    idPedido BIGINT NOT NULL,
    idUsuario BIGINT NULL,
    eventType VARCHAR(40) NOT NULL,
    detail VARCHAR(500) NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idAudit),
    KEY idx_payment_audit_order_time (idPedido,createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
