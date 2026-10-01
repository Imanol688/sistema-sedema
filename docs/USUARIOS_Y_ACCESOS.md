# Usuarios y accesos — SEDEMA

## Flujo
1. El Administrador crea la cuenta con nombre, apellido, DNI, correo, sector/rol y permisos.
2. El sistema genera una contraseña temporal alfanumérica de 8 caracteres, almacena solo su hash y la envía por correo.
3. El empleado inicia sesión con correo o DNI y la contraseña temporal.
4. Mientras `mustChangePassword=1`, el sistema bloquea el dashboard y obliga a completar `first-access.php`.
5. El empleado define usuario definitivo, nueva contraseña y opcionalmente una foto.
6. Al finalizar, la contraseña temporal deja de funcionar y se habilita el acceso normal según rol/permisos.

## Gmail SMTP
En desarrollo puede usarse `MAIL_TRANSPORT=log`, que escribe el mensaje en `storage/logs/mail.log`.
Para enviar realmente mediante Gmail:

```ini
MAIL_TRANSPORT=smtp
MAIL_FROM=cuenta-emisora@gmail.com
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=465
MAIL_SMTP_USERNAME=cuenta-emisora@gmail.com
MAIL_SMTP_PASSWORD=CONTRASENA_DE_APLICACION
```

La cuenta emisora de Gmail debe tener verificación en dos pasos y una contraseña de aplicación válida.
