-- SEDEMA S.R.L. - Gestión de Usuarios y Accesos
-- Ejecutar una sola vez sobre sedema_db luego de 003_personnel_schema.sql.

START TRANSACTION;

ALTER TABLE usuario
    MODIFY COLUMN roles ENUM('ADMINISTRADOR','VENDEDOR','PROVEEDOR','DEPOSITO','LOGISTICA','CAJA','ALMACEN','FINANZAS','SISTEMAS') NOT NULL,
    ADD COLUMN IF NOT EXISTS sector VARCHAR(80) NULL AFTER roles,
    ADD COLUMN IF NOT EXISTS mustChangePassword TINYINT(1) NOT NULL DEFAULT 0 AFTER passwordHash,
    ADD COLUMN IF NOT EXISTS initialSetupCompleted TINYINT(1) NOT NULL DEFAULT 1 AFTER mustChangePassword,
    ADD COLUMN IF NOT EXISTS profilePhoto VARCHAR(255) NULL AFTER initialSetupCompleted,
    ADD COLUMN IF NOT EXISTS credentialSentAt DATETIME NULL AFTER profilePhoto,
    ADD COLUMN IF NOT EXISTS emailValidated TINYINT(1) NOT NULL DEFAULT 1 AFTER credentialSentAt;

CREATE TABLE IF NOT EXISTS user_access_audit (
    idAccessAudit BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    idUsuario BIGINT(20) NULL,
    actorUserId BIGINT(20) NULL,
    eventType ENUM('ACCOUNT_CREATED','TEMP_CREDENTIAL_SENT','FIRST_ACCESS_COMPLETED','ACCOUNT_ENABLED','ACCOUNT_DISABLED','PERMISSIONS_CHANGED') NOT NULL,
    detail VARCHAR(500) NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idAccessAudit),
    KEY idx_access_audit_user_time (idUsuario, createdAt),
    KEY idx_access_audit_actor_time (actorUserId, createdAt),
    CONSTRAINT fk_access_audit_user FOREIGN KEY (idUsuario) REFERENCES usuario(idUsuario) ON DELETE SET NULL,
    CONSTRAINT fk_access_audit_actor FOREIGN KEY (actorUserId) REFERENCES usuario(idUsuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
