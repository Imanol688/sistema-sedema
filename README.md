# SEDEMA — módulo de autenticación PHP/MySQL

Sistema de gestión SEDEMA con autenticación interna, panel principal e inventario operativo por múltiples depósitos.

## Requisitos

- PHP 8.1 o superior con extensiones `pdo_mysql` y `mbstring`.
- MySQL 8.0 o superior.
- Apache (XAMPP/WAMP/Laragon) o el servidor local de PHP para desarrollo.

## Instalación en Windows con XAMPP

1. Copiar la carpeta `sedema-auth` dentro de `C:\xampp\htdocs\`.
2. Duplicar `.env.example` con el nombre `.env` y completar la conexión MySQL. Generar un `APP_KEY` aleatorio largo.
3. En MySQL Workbench, abrir y ejecutar `database/001_auth_schema.sql`.
4. Seleccionar la base configurada en `.env` y ejecutar `database/002_inventory_schema.sql`.
5. Crear el primer administrador desde la consola:

   ```bash
   C:\xampp\php\php.exe database\create_admin.php admin admin@sedema.com "Una clave segura de 12+" Nombre Apellido
   ```

6. Abrir `http://localhost/sedema-auth/public/`.

En desarrollo, los enlaces de recuperación se guardan en `storage/logs/mail.log`. En producción, cambiar `MAIL_TRANSPORT=mail`, configurar correo real, servir únicamente la carpeta `public`, activar HTTPS y denegar acceso web a `.env`, `src`, `database` y `storage`.

## Estructura

- `public/`: páginas y recursos expuestos por el servidor web.
- `src/`: conexión, sesiones, autenticación, CSRF y recuperación.
- `database/`: esquema MySQL y alta inicial del administrador.
- `docs/ANALISIS.md`: relación entre la implementación y los requerimientos.
- `docs/IDENTIDAD_VISUAL.md`: paleta, tipografía y reglas visuales para todos los módulos.
- `docs/INVENTARIO.md`: alcance, permisos y contrato de integración del inventario.
- `storage/logs/`: registros técnicos no públicos.

## Pruebas mínimas recomendadas

- Usuario válido, contraseña incorrecta y cuenta deshabilitada producen el mismo mensaje.
- Al quinto intento fallido, el acceso queda temporalmente limitado.
- Un token de recuperación vence, solo funciona una vez y no revela si el correo existe.
- Tras cambiar la contraseña, una sesión anterior queda invalidada.
- Los formularios rechazan CSRF ausente o incorrecto.
- Las rutas protegidas redirigen al inicio cuando no hay sesión.
