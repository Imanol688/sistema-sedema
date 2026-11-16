# SEDEMA en alwaysdata

## Instalación limpia

1. Creá una base vacía y un usuario autorizado sobre ese esquema en el panel del proveedor. Anotá el host, puerto, nombre de la base y usuario tal como aparecen en el panel.
2. Desde la raíz del proyecto generá el SQL de instalación remota. El comando funciona con Bash o CMD. En XAMPP reemplazá `php` por `C:\xampp\php\php.exe` si hace falta. En Windows PowerShell 5.1 usá CMD para esta redirección y así conservar UTF-8.

   ```sh
   php database/export_hosting_install.php > install-hosting.sql
   ```

3. Abrí phpMyAdmin, seleccioná la base vacía asignada e importá `install-hosting.sql`. También podés usar el cliente MySQL pasando el nombre de esa base como argumento. El instalador usa el esquema seleccionado y crea 43 tablas. Revisá los errores de importación antes de seguir. Si falla a mitad de camino, revisá la causa y repetí sobre otra base vacía.
4. Subí el proyecto por SFTP y configurá el sitio PHP 8.1 o superior con el directorio `sedema/public` como raíz web. Asegurá escritura en `storage/logs` y `public/uploads/profiles`. Activá HTTPS con certificado válido y habilitá la redirección de HTTP a HTTPS.
5. Copiá `.env.hosting.example` a `.env` en la raíz del proyecto, un nivel por encima de `public/`. Usá la URL HTTPS del sitio, sin el sufijo `/public`. Cargá la conexión del paso 1 y generá `APP_KEY` con `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Mantené fuera de `public/` el SQL generado, el archivo de entorno y los logs.
6. Ingresá por SSH, ubicáte en la carpeta que contiene `database/` y creá el administrador.

   ```sh
   php database/create_admin.php admin admin@example.com 'CLAVE-PROPIA-DE-12-O-MAS-CARACTERES' Admin SEDEMA
   ```

   Sustituí correo y contraseña. Entrá por HTTPS y actualizá el DNI provisional del legajo antes de operar con datos reales.

## QR y pedidos impagos

Registrá en Mercado Pago la URL HTTPS `/payments/mp-webhook.php` del sitio y el evento de órdenes. Configurá las variables `MP_` de `.env` con datos del mismo entorno, cuenta y caja. Usá una base separada con datos ficticios para pruebas. Configurá el secreto de firma de notificaciones de esa aplicación.

El operador puede verificar o cancelar un QR desde la ficha del cobro. El pedido se habilita para corrección cuando todos sus intentos están rechazados y, para QR, consta un cierre terminal verificado. Ante una verificación fallida, un QR sin identificación confirmada, un cobro acreditado o devuelto, o un movimiento asociado, el pedido sigue protegido. Para cheques pendientes, resolvé el cheque desde su ficha. Si la pasarela genérica mantiene un intento pendiente, resolvelo en la pasarela y verificá el estado en SEDEMA.

Al retirar un pedido con intentos cerrados, pasa a cancelado y sale de Cobros y Pedidos. Conserva sus artículos y registros de cobro para auditoría. Un pedido sin intentos ni dependencias puede eliminarse físicamente. Si una notificación posterior contradice el cierre confirmado, revisá la orden en Mercado Pago y conciliá el caso antes de continuar.

## Aceptación en el entorno remoto

| Caso | Resultado esperado |
|---|---|
| Instalación y acceso | 43 tablas, alta por SSH y acceso HTTPS con los roles esperados. |
| Directorio público | La URL raíz abre SEDEMA. Las rutas a `.env`, `database/install.sql` y `storage/logs/app.log` devuelven 403 o 404 y no muestran su contenido. |
| Pedido sin intentos | Se edita o elimina. La eliminación libera el disponible del depósito. |
| QR pendiente | El pedido sigue protegido. La ficha permite verificar o cancelar el QR. |
| QR cancelado o vencido | Tras confirmar el cierre, se puede editar o retirar el pedido. El retiro conserva intento, orden externa y auditoría, y libera la reserva. |
| QR acreditado | Mercado Pago notifica por HTTPS, SEDEMA verifica la orden y acredita una sola vez. Repetí la notificación y compará el total pagado y los movimientos de cuenta. |
| Recepción y despacho | La recepción parcial actualiza el stock. El pedido cobrado permite emitir comprobantes y despachar. La devolución repone el stock. |
| Respaldo | Exportá la base, importala en un esquema de prueba separado y verificá pedidos, cobros y existencias contra el original. |
