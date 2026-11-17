# SEDEMA · Gestión integral para corralón

**SEDEMA 3.1 · Primera versión estable del proyecto.** Sistema web interno para un corralón de materiales, desarrollado en PHP con MySQL o MariaDB. Esta versión reúne la operación comercial y logística, y se desplegó en alwaysdata para demostración y pruebas.

[Instalación local](#instalación-local-desde-cero) · [Hosting](#instalación-en-hosting) · [Mercado Pago QR](#mercado-pago-qr) · [Pruebas](#pruebas-locales)

## Componentes y funciones

| Área | Funciones principales |
|---|---|
| Acceso y personal | Inicio de sesión, primer acceso, recuperación de contraseña, perfiles, legajos y liquidaciones. El Administrador gestiona los accesos y genera liquidaciones. |
| Ventas y clientes | Padrón de clientes, pedidos por depósito y modalidad de entrega, ajustes comerciales y guardado de pedidos pendientes. El rol Vendedor consulta clientes para cargar pedidos. |
| Pagos y cobranzas | Registro y verificación de cobros, cuenta corriente, QR de mostrador de Mercado Pago y emisión de remitos y facturas internas tras completar el pago. |
| Inventario y depósito | Catálogo, existencias por depósito, movimientos, transferencias y control del disponible con reservas de pedidos activos. |
| Proveedores y abastecimiento | Registro de proveedores, remitos de proveedor, recepciones parciales o totales y vista de impresión. |
| Despacho y logística | Programación, reprogramación y cancelación de despachos, control de salida y devolución de mercadería. |

El navegador accede a los puntos de entrada y recursos de `public/`. La lógica de acceso y de los módulos está en `src/`. El esquema, los scripts de migración y las herramientas de instalación están en `database/`. `storage/logs/` contiene registros de ejecución y `public/uploads/profiles/` aloja las fotos de perfil.

## Evolución de versiones

| Etapa | Cambios principales |
|---|---|
| Desarrollo inicial | Inicio de sesión, panel principal, alta de usuarios y permisos. Incorporación gradual de inventario, ventas, clientes, cobros y logística. |
| 2.2 | Estabilización de inventario, logística y proveedores. Ajustes a recepciones y despachos. |
| 3.0 integrada | Unificación de los módulos comerciales y logísticos y del esquema de datos. Incorporación de Mercado Pago QR, corrección de la lectura de `.env` y alineación del arranque en XAMPP con `sedema_integrada`. |
| 3.1 | Guardado y recuperación de pedidos pendientes, reserva por depósito, selector de tarjeta, mejoras en el alta de proveedores, ajustes de tipografía y restricción de la gestión de clientes para el rol Vendedor. |
| 3.1 estable | Instalación remota sobre un esquema asignado, configuración HTTPS y corrección de pedidos impagos con intentos de cobro cerrados, preservando su trazabilidad. Despliegue inicial en alwaysdata. |

## Requisitos

PHP 8.1 o superior con `pdo_mysql`, `mbstring` y `fileinfo`; cURL para las pasarelas y OpenSSL para SMTP. MySQL 8.0.16+ o MariaDB 10.4+. Apache con soporte para los archivos `.htaccess`. Escritura en `storage/logs` y `public/uploads/profiles`.

## Instalación local desde cero

### 1. Preparar el entorno

| Entorno | Carpeta del proyecto | Servicios | URL local |
|---|---|---|---|
| XAMPP | `C:\xampp\htdocs\sedema-main` | Iniciá Apache y MySQL desde XAMPP Control Panel. | `http://localhost/sedema-main/public/` |
| Laragon | `C:\laragon\www\sedema` | Elegí Apache y MySQL/MariaDB e iniciá ambos con **Start All**. | `http://localhost/sedema/public/` |
| Apache y MySQL/MariaDB instalados por separado | Usá el directorio configurado en Apache y PHP 8.1 o superior. | Iniciá Apache y el servidor de base de datos. | Usá la URL que apunte a `public/`. Si configurás un VirtualHost con `public/` como raíz, usá directamente su dominio. |

Si cambiás la carpeta o el puerto HTTP, ajustá la URL. Con XAMPP y Laragon en la misma máquina, usá un solo servidor de base de datos para esta instalación o asignales puertos distintos.

### 2. Crear la base limpia

Usá `database/install.sql`. El script crea y selecciona `sedema_integrada`, instala 43 tablas (41 operativas y dos de procedencia) y agrega catálogos iniciales. El administrador se crea en el paso 4. Si ya existe `sedema_integrada` con datos, respaldala y usá una instancia de prueba separada para la instalación limpia.

Elegí una de estas vías. Todas deben conectarse al mismo servidor que usarás en `.env`.

- **MySQL Workbench.** Creá o abrí una conexión **Standard TCP/IP** a `127.0.0.1`, puerto `3306` o el configurado en tu servidor, con un usuario que pueda crear esquemas y tablas. Abrí **File → Open SQL Script**, elegí `database/install.sql` y ejecutá el script completo desde el editor. Revisá **Action Output** y actualizá **Schemas** para ver `sedema_integrada`. [Manual del editor SQL](https://dev.mysql.com/doc/workbench/en/wb-sql-editor-main-menu.html)
- **phpMyAdmin.** En XAMPP podés abrirlo con **Admin** junto a MySQL. En la pantalla principal, entrá a **Importar**, seleccioná `database/install.sql` y ejecutá la importación en formato SQL. Si tu sesión carece de permiso para crear bases, pedí al administrador del servidor que cree `sedema_integrada` y te conceda los permisos del esquema, o que ejecute la instalación.
- **Laragon o consola de MySQL/MariaDB.** Abrí la terminal de Laragon y ejecutá el siguiente ejemplo desde CMD o una terminal compatible con su redirección. Usá `mariadb` si ese es el nombre de tu cliente. Si el ejecutable no está en el PATH, usá su ruta completa. Cambiá puerto y usuario según el servidor. Podés omitir `-p` cuando el servidor local tiene el usuario configurado sin contraseña.

```bat
cd /d C:\laragon\www\sedema
mysql --host=127.0.0.1 --port=3306 --user=root -p < database\install.sql
```

Verificá el resultado en el cliente que elegiste. Para una instalación limpia, el conteo esperado es **43**.

```sql
SELECT COUNT(*) AS tablas
FROM information_schema.tables
WHERE table_schema = 'sedema_integrada' AND table_type = 'BASE TABLE';
```

### 3. Configurar SEDEMA

Copiá `.env.example` a `.env` en la raíz del proyecto si todavía no existe. En una instalación previa, conservá el archivo y revisá sus valores.

```dotenv
APP_ENV=development
APP_URL=http://localhost/sedema-main/public
APP_TIMEZONE=America/Argentina/Buenos_Aires
APP_KEY=REEMPLAZAR_CON_UNA_CLAVE_ALEATORIA
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sedema_integrada
DB_USERNAME=root
DB_PASSWORD=
MAIL_TRANSPORT=log
```

El ejemplo usa la URL de XAMPP y una cuenta local sin contraseña. En Laragon usá `http://localhost/sedema/public` para la carpeta de la tabla anterior. Copiá los datos reales de la conexión que utilizaste para importar. Para generar la clave ejecutá `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. En XAMPP, usá `C:\xampp\php\php.exe` si PHP no figura en el PATH. Con `MAIL_TRANSPORT=log`, el correo de prueba queda en `storage/logs`. SMTP y Mercado Pago requieren configuración propia. Mantené `.env`, fotos y copias de bases operativas fuera del repositorio.

### 4. Crear el administrador e ingresar

El script de consola recibe **usuario, correo, contraseña, nombre y apellido**, en ese orden. Reemplazá el correo de ejemplo y la contraseña por los tuyos. Usá una contraseña de al menos 12 caracteres.

**XAMPP · PowerShell**

```powershell
Set-Location 'C:\xampp\htdocs\sedema-main'
& 'C:\xampp\php\php.exe' '.\database\create_admin.php' 'admin' 'admin@example.com' 'REEMPLAZAR_CON_CLAVE_PROPIA' 'Admin' 'SEDEMA'
```

**XAMPP · Símbolo del sistema (CMD)**

```bat
cd /d C:\xampp\htdocs\sedema-main
C:\xampp\php\php.exe database\create_admin.php admin admin@example.com "REEMPLAZAR_CON_CLAVE_PROPIA" Admin SEDEMA
```

**Laragon · Terminal en CMD**

```bat
cd /d C:\laragon\www\sedema
php database\create_admin.php admin admin@example.com "REEMPLAZAR_CON_CLAVE_PROPIA" Admin SEDEMA
```

Cuando la consola confirme el alta, abrí la URL del paso 1 e ingresá con el usuario o correo y la contraseña elegidos. El script crea un legajo con DNI provisional. Identificá el legajo por su usuario asociado y completá el DNI antes de usar la instalación en producción.

## Actualizar una instalación integrada 3.0

Respaldá la carpeta y la base, actualizá los archivos y conservá `.env`, los datos y las fotos de perfil. La 3.1 usa el mismo esquema integrado. Si querés repetir las pruebas locales, seguí la sección [Pruebas locales](#pruebas-locales).

## Actualizar una instalación v2.2

Respaldar carpeta y base, detener las escrituras y probar primero sobre una copia. Seleccionar la base de esa copia y ejecutar `database/update_020.sql`. Requiere la migración `013_dispatch_operations_20261022`; puede repetirse y no importa registros de otro ambiente. Mantener el `.env`, las fotos y los registros actuales al actualizar los archivos.

El instalador limpio no sirve para actualizar una base poblada. Ante un error SQL, detenerse y revisar; no usar `--force`. El DDL confirma cambios implícitamente.

## Conversión y fusión de bases

- `database/migrate_to_integrated.php`: convierte un único origen a un destino integrado nuevo; `--check` prevalida sin escribir.
- `database/merge_groups.php`: reproduce la fusión preparada para una instalación v2.2 sin ventas locales y un origen comercial. Sin `--apply` prepara el plan; con `--apply` copia a un destino nuevo y conserva la procedencia.
- `docs/INTEGRACION.md`: políticas, alcance y límites de estas herramientas.

Las copias operativas entregadas por separado no forman parte de Git. Antes de fusionar otras bases hay que comprobar identidades, claves y existencias; compartir un ID no demuestra que dos registros sean la misma entidad.

## Flujos integrados

- Ventas usa el catálogo, las unidades y los depósitos de Inventario. Un pedido nuevo usa el catálogo vigente. Se puede guardar como pendiente y retomar para editarlo o confirmarlo. El selector de tarjeta se usa cuando hay recargo por financiación.
- Las cantidades pendientes de pedidos activos reducen el disponible en su depósito de origen. Cancelar o eliminar un pedido sin operaciones asociadas libera ese disponible.
- El pago efectivo completo habilita remito y factura. La programación desde Pagos utiliza las validaciones y reservas de Logística.
- La salida a ruta descuenta stock y pendiente dentro de una transacción. Cancelar una programación libera su reserva; devolver un despacho repone las cantidades.
- Los formularios de cobro y las operaciones protegidas reconocen reenvíos mediante claves duraderas. Los despachos rechazan formularios desactualizados.
- Una imputación a cuenta corriente representa deuda. El cobro aprobado acredita la cuenta y una devolución QR verificada revierte el crédito correspondiente.
- Los pedidos impagos con intentos rechazados y cierre confirmado se pueden corregir o retirar. Al retirarlos se conservan los intentos de cobro y se libera la reserva. Los cobros abiertos, las acreditaciones y las demás operaciones asociadas impiden la corrección.
- El Administrador gestiona accesos y genera liquidaciones. Las fotos y el primer acceso mantienen sus flujos existentes.

## Instalación en hosting

En SEDEMA se usó alwaysdata para la demostración académica y para probar la aplicación en un servidor remoto. El servicio permite conservar PHP y MariaDB, publicar por HTTPS y administrar archivos y tareas de instalación por SFTP y SSH.

### Instalación remota desde cero

1. Creá una base vacía y un usuario autorizado sobre ese esquema en el panel del proveedor. Anotá el host, puerto, nombre de la base y usuario tal como aparecen en el panel.
2. Desde la raíz del proyecto generá el SQL de instalación remota. El comando funciona con Bash o CMD. En XAMPP reemplazá `php` por `C:\xampp\php\php.exe` si hace falta. En Windows PowerShell 5.1 usá CMD para esta redirección y así conservar UTF-8.

   ```sh
   php database/export_hosting_install.php > install-hosting.sql
   ```

3. Abrí phpMyAdmin, seleccioná la base vacía asignada e importá `install-hosting.sql`. También podés usar el cliente MySQL pasando el nombre de esa base como argumento. El instalador usa el esquema seleccionado y crea 43 tablas. Revisá los errores de importación antes de seguir. Si falla a mitad de camino, revisá la causa y repetí sobre otra base vacía.
4. Subí el proyecto por SFTP y configurá el sitio PHP 8.1 o superior con el directorio `sedema-main/public/` como raíz web. Asegurá escritura en `storage/logs` y `public/uploads/profiles`. Activá HTTPS con certificado válido y habilitá la redirección de HTTP a HTTPS.
5. Copiá `.env.hosting.example` a `.env` en la raíz del proyecto, un nivel por encima de `public/`. Usá la URL HTTPS del sitio, sin el sufijo `/public`. Cargá la conexión del paso 1 y generá `APP_KEY` con `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`. Mantené fuera de `public/` el SQL generado, el archivo de entorno y los logs.
6. Ingresá por SSH, ubicáte en la carpeta que contiene `database/` y creá el administrador.

   ```sh
   php database/create_admin.php admin admin@example.com 'CLAVE-PROPIA-DE-12-O-MAS-CARACTERES' Admin SEDEMA
   ```

   Sustituí correo y contraseña. Entrá por HTTPS y actualizá el DNI provisional del legajo antes de operar con datos reales.

### Diagnóstico de conexión

Si el inicio de sesión muestra un error genérico, buscá su código en `storage/logs/app.log`. Un error `[2002] Connection refused` requiere revisar el servidor y el puerto en el `.env` remoto. Copiá los datos de **Bases de datos → MySQL** del panel del proveedor. Si aparece `[1049] Unknown database`, verificá que el nombre completo del esquema coincida con el de la importación.

## Mercado Pago QR

En un entorno de prueba, usá datos ficticios y las credenciales de prueba de la aplicación. Creá una sucursal y una caja para el vendedor correspondiente, con modo `pdv`. Completá estas variables en el `.env` del servidor:

| Variable | Valor |
|---|---|
| `MP_ENVIRONMENT` | `test` para la prueba. |
| `MP_ACCESS_TOKEN` | Access Token de prueba de la aplicación. |
| `MP_SELLER_ID` | `user_id` numérico de la cuenta vendedora asociada a la caja. |
| `MP_POS_EXTERNAL_ID` | `external_id` alfanumérico de la caja, de hasta 40 caracteres. |
| `MP_WEBHOOK_SECRET` | Secreto de firma de Webhooks de la aplicación. |

Registrá la URL HTTPS `/payments/mp-webhook.php` del sitio para el evento **Órdenes** en el entorno de prueba. Generá un cobro en SEDEMA y escaneá el QR de la caja con una cuenta compradora de prueba. Después comprobá el estado del cobro en SEDEMA. Tarjeta y transferencia conservan el adaptador genérico y su modo `mock`.

### Corrección de pedidos con intentos de cobro

El operador puede verificar o cancelar un QR desde la ficha del cobro. El pedido se habilita para corrección cuando todos sus intentos están rechazados y, para QR, consta un cierre terminal verificado. Ante una verificación fallida, un QR sin identificación confirmada, un cobro acreditado o devuelto, o un movimiento asociado, el pedido sigue protegido. Para cheques pendientes, resolvé el cheque desde su ficha. Si la pasarela genérica mantiene un intento pendiente, resolvelo en la pasarela y verificá el estado en SEDEMA.

Al retirar un pedido con intentos cerrados, pasa a cancelado y sale de Cobros y Pedidos. Conserva sus artículos y registros de cobro para auditoría. Un pedido sin intentos ni dependencias puede eliminarse físicamente. Si una notificación posterior contradice el cierre confirmado, revisá la orden en Mercado Pago y conciliá el caso antes de continuar.

### Aceptación en el entorno remoto

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

## Pruebas locales

Iniciá Apache y MySQL o MariaDB, abrí la URL de tu instalación y recargá la página con **Ctrl+F5**.

- Creá un pedido con cliente, depósito y artículos. Usá «Guardar pendiente». Volvé desde Ventas y abrí «Retomar pedido». Comprobá los datos guardados, modificá las cantidades y guardá o confirmá el pedido.
- Usá un producto con 10 unidades físicas y sin pedidos previos. Guardá un pedido pendiente de 7. Otra venta debe ver 3 disponibles. Probá un egreso o transferencia de 4 desde ese depósito. Debe rechazarse.
- Abrí el primer pedido para editarlo. Podés usar sus propias 7 unidades al recalcular el disponible. Cambiá la cantidad a 5 y comprobá que el disponible para otro pedido pase a 5.
- Cancelá o eliminá el pedido sin operaciones asociadas. Volvé a consultar el disponible. Deben quedar 10 unidades. En un pedido separado verificá el egreso físico al salir a ruta y el reingreso al registrar devolución.
- Cargá un recargo y seleccioná la tarjeta. Guardá y verificá que el selector conserve la opción al retomar el pedido.
- Creá un proveedor con razón social y CUIT, con y sin guiones. Provocá un error de formato o duplicado y verificá que el formulario conserve los datos ingresados.
- Revisá la tipografía en los navegadores usados por el equipo.

La reserva se calcula por depósito de origen sobre las cantidades pendientes
de pedidos activos, incluidos los pedidos existentes. El stock físico se
actualiza con los movimientos habituales. Guardar pendiente exige un pedido
válido con artículos y stock suficiente.

## Validación y límites

Para la integración 3.0 se verificó la sintaxis PHP/JavaScript y el circuito de servicios con PHP 8.3.6 y MariaDB 10.11.14. La integración incluyó comprobaciones de conservación de datos, claves foráneas, acceso HTTP y reenvíos concurrentes. El parche del lector de entorno se comprobó con la plantilla completa, comentarios con paréntesis, BOM, CRLF, valores entre comillas y prioridad de las variables MP del proyecto. No se incorpora una suite automatizada al repositorio.

La 3.1 pasó la prueba local del equipo. En alwaysdata se verificaron el formulario de inicio por HTTPS, la conexión a MariaDB y el alta del administrador por SSH. Quedan pendientes la prueba de Mercado Pago QR de punta a punta, el envío SMTP en el hosting y las mediciones de rendimiento bajo carga. Los datos históricos incompletos se señalan sin inventar entregas. La agenda detecta coincidencias exactas de horario, sin duración de viajes; las devoluciones de despachos se registran por el despacho completo. Los comprobantes son internos: no se incorpora facturación fiscal electrónica.
