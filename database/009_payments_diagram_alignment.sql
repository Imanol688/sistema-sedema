-- SEDEMA v1.9.1 - Alineación del Módulo 3: Pagos y Cobranzas
-- Ejecutar una sola vez sobre sedema_db DESPUÉS de 008_payments_schema.sql.

ALTER TABLE pago
    ADD COLUMN IF NOT EXISTS tipoOperacion ENUM('PAGO','CUENTA_CORRIENTE') NOT NULL DEFAULT 'PAGO' AFTER idUsuario,
    ADD COLUMN IF NOT EXISTS importeBase DECIMAL(12,2) NULL AFTER importe,
    ADD COLUMN IF NOT EXISTS observaciones VARCHAR(255) NULL AFTER transaccionExternaID;

-- Se deja CONTROLADOR en el ENUM únicamente por compatibilidad con registros históricos.
-- Las operaciones nuevas usan exclusivamente EFECTIVO, TARJETA, CHEQUE o TRANSFERENCIA.
ALTER TABLE pago
    MODIFY medioPago ENUM('EFECTIVO','TARJETA','CHEQUE','TRANSFERENCIA','CONTROLADOR','CUENTA_CORRIENTE') NULL;

UPDATE pago
SET tipoOperacion='CUENTA_CORRIENTE', medioPago=NULL
WHERE medioPago='CUENTA_CORRIENTE';

CREATE TABLE IF NOT EXISTS payment_cash_detail (
    idPago BIGINT NOT NULL,
    importeRecibido DECIMAL(12,2) NOT NULL,
    vuelto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (idPago),
    CONSTRAINT fk_payment_cash_payment FOREIGN KEY (idPago) REFERENCES pago(idPago) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_check_detail (
    idPago BIGINT NOT NULL,
    banco VARCHAR(120) NOT NULL,
    numeroCheque VARCHAR(80) NOT NULL,
    titular VARCHAR(150) NOT NULL,
    fechaEmision DATE NOT NULL,
    fechaVencimiento DATE NOT NULL,
    observaciones VARCHAR(255) NULL,
    PRIMARY KEY (idPago),
    KEY idx_payment_check_number (numeroCheque),
    CONSTRAINT fk_payment_check_payment FOREIGN KEY (idPago) REFERENCES pago(idPago) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE payment_gateway_request
    ADD COLUMN IF NOT EXISTS paymentMethod ENUM('TARJETA','TRANSFERENCIA') NULL AFTER provider;
