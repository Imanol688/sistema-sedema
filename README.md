# SEDEMA 3.1

Sistema interno de gestión para un corralón de materiales. Incluye personal y usuarios, ventas y clientes, pagos y cobranzas, inventario, proveedores y despacho.

## Requisitos

- PHP 8.1 o superior con `pdo_mysql`, `mbstring` y `fileinfo`. Habilitá cURL para las pasarelas y OpenSSL para SMTP.
- MySQL 8.0.16 o superior, o MariaDB 10.4 o superior. Para XAMPP o Laragon, usá Apache con soporte de `.htaccess`.
- El usuario que ejecuta PHP debe poder escribir en `storage/logs/` y `public/uploads/profiles/`.

## Instalación local en XAMPP o Laragon

1. Extraé la carpeta `sedema-main` en `C:\xampp\htdocs\` o `C:\laragon\www\`. Iniciá Apache y MySQL/MariaDB.
2. Creá una **base vacía** llamada `sedema_integrada`, con juego de caracteres `utf8mb4` y colación `utf8mb4_unicode_ci`. Seleccionala en phpMyAdmin e importá `database/install.sql`. En MySQL Workbench, seleccioná `sedema_integrada` como esquema predeterminado en la conexión al servidor local y ejecutá el archivo completo.
3. Copiá `.env.example` a `.env` en la raíz del proyecto. Completá la conexión y generá una clave propia con `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Pegala como valor de `APP_KEY`. Si tu carpeta o puerto web cambia, ajustá `APP_URL`.
4. Creá el administrador con el comando de abajo y entrá en `http://localhost/sedema-main/public/`.

## Instalación nueva en alwaysdata

1. Creá una **base vacía** desde el panel y asignale un usuario. Seleccionala en phpMyAdmin e importá el mismo `database/install.sql`. El script trabaja sobre la base seleccionada y crea 43 tablas con los catálogos iniciales.
2. Subí el proyecto por SFTP. En la configuración del sitio PHP, apuntá la raíz web a `sedema-main/public/` y activá HTTPS. Verificá los permisos de escritura de las dos carpetas indicadas arriba.
3. Copiá `.env.example` a `.env` en la raíz del proyecto. Usá `APP_ENV=production`, `APP_URL` con la URL HTTPS pública, y una `APP_KEY` propia generada como en la instalación local. Para `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` usá los datos de MySQL del panel. Configurá el transporte de correo antes de habilitar el alta de usuarios.
4. Desde SSH, entrá a la carpeta que contiene `database/` y creá el administrador. Después ingresá por la URL HTTPS del sitio.

Si ya hay una instalación con datos, conservá su base, su `.env` y las fotos de perfil. Este SQL se importa únicamente en una base vacía. Una instalación interrumpida se repite en una base nueva después de resolver el error.

## Crear el administrador

Desde la raíz del proyecto, reemplazá el correo y la contraseña de ejemplo por los propios. La contraseña debe tener al menos 12 caracteres.

```sh
php database/create_admin.php admin admin@example.com "REEMPLAZAR_CON_CLAVE_PROPIA" Admin SEDEMA
```

Si `php` no está en el PATH de Windows, en PowerShell usá `& 'C:\xampp\php\php.exe'` en su lugar. En Laragon podés usar su terminal. Ingresá con el usuario o correo y completá el DNI provisional en el legajo antes del uso operativo.

## Correo y Mercado Pago QR

- En desarrollo, `MAIL_TRANSPORT=log` escribe mensajes de prueba en `storage/logs/mail.log`. En hosting, `mail` requiere que `mail()` funcione y sirve tanto para credenciales como para recuperación. Con `smtp`, el envío directo configura las credenciales temporales mediante las variables `MAIL_SMTP_*`. La recuperación de contraseña requiere el transporte `mail`.
- Para probar Mercado Pago QR, usá `MP_ENVIRONMENT=test` y los datos de la aplicación, el vendedor y la caja de prueba. Completá `MP_ACCESS_TOKEN`, `MP_SELLER_ID`, `MP_POS_EXTERNAL_ID` y `MP_WEBHOOK_SECRET`. Configurá el evento Órdenes para la URL HTTPS del sitio seguida de `/payments/mp-webhook.php` y verificá el estado del cobro desde SEDEMA. El adaptador genérico `PAYMENT_GATEWAY_MODE=mock` es para pruebas locales de tarjeta y transferencia.

## Verificación inicial

Confirmá que se crean las 43 tablas, que el administrador puede iniciar sesión y que carga el panel. Si falla la conexión, consultá `storage/logs/app.log` y cotejá los datos de `.env` con el servidor. En el sitio público, comprobá que la URL raíz abre SEDEMA y que `.env`, `database/` y `storage/` quedan fuera de la raíz web.
