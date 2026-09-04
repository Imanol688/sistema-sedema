-- SEDEMA S.R.L. - Módulo Personal y Haberes
-- Ejecutar una sola vez sobre la base sedema_db actual.

START TRANSACTION;

ALTER TABLE liquidacionsueldo
    ADD COLUMN IF NOT EXISTS sueldoBase DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER periodo,
    ADD COLUMN IF NOT EXISTS totalHaberes DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER sueldoBase,
    ADD COLUMN IF NOT EXISTS totalDescuentos DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER totalHaberes,
    ADD COLUMN IF NOT EXISTS numeroRecibo VARCHAR(50) NULL AFTER montoNeto;

CREATE TABLE IF NOT EXISTS personnel_payroll_item (
    idConcepto BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    idLiquidacion BIGINT(20) NOT NULL,
    tipo ENUM('HABER','DESCUENTO') NOT NULL,
    concepto VARCHAR(150) NOT NULL,
    importe DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (idConcepto),
    KEY idx_personnel_payroll_item_liquidacion (idLiquidacion),
    CONSTRAINT fk_personnel_payroll_item_liquidacion
        FOREIGN KEY (idLiquidacion) REFERENCES liquidacionsueldo (idLiquidacion)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evita liquidar dos veces al mismo empleado para el mismo período.
-- Si el índice ya existe, omití esta sentencia al volver a ejecutar la migración.
ALTER TABLE liquidacionsueldo
    ADD UNIQUE KEY uq_liquidacion_empleado_periodo (idEmpleado, periodo),
    ADD UNIQUE KEY uq_liquidacion_numero_recibo (numeroRecibo);

COMMIT;
