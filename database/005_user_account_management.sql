-- SEDEMA S.R.L. - Usuarios y Accesos v1.5
-- Ejecutar una sola vez sobre sedema_db luego de 004_user_access_schema.sql.

START TRANSACTION;

ALTER TABLE usuario
    ADD COLUMN IF NOT EXISTS deletedAt DATETIME NULL AFTER emailValidated,
    ADD COLUMN IF NOT EXISTS deletedBy BIGINT(20) NULL AFTER deletedAt;

ALTER TABLE user_access_audit
    MODIFY COLUMN eventType ENUM(
        'ACCOUNT_CREATED','TEMP_CREDENTIAL_SENT','FIRST_ACCESS_COMPLETED',
        'ACCOUNT_ENABLED','ACCOUNT_DISABLED','PERMISSIONS_CHANGED',
        'EMAIL_CHANGED','ACCOUNT_DELETED'
    ) NOT NULL;

COMMIT;
