# SEDEMA — versión 2.2

Fecha de entrega: 22 de octubre de 2026.

Sistema de gestión para un corralón de materiales. Esta entrega depura exclusivamente el código de **Inventario, Logística y Proveedores**, tomando como referencia la base actual `sedema_db(1).sql`. Incorpora una instalación limpia, una actualización conservadora y documentación unificada.

## 1. Alcance y compatibilidad

- El código de Autenticación, Usuarios/Accesos, Personal, Clientes, Ventas, Pagos, Perfil, Dashboard y los recursos compartidos conserva sus bytes originales.
- La base completa mantiene las tablas que necesitan todos los módulos y las dos referencias de producto de `detallepedido`. No se cambia el flujo de cobros, facturas ni pedidos de esos módulos.
- No se entrega una integración nueva con Mercado Pago: la pasarela del ZIP original continúa siendo simulador/adaptador genérico.
- No se cambia el estilo visual. La guía no redundante se conserva en `docs/IDENTIDAD_VISUAL.md`.
- En actualizaciones **conservar el `.env`, las fotos de perfiles y los registros locales**. No se incluye un `.env` real ni credenciales del ambiente anterior.
- No se incorpora una suite de pruebas automatizadas al sistema.

La base adjunta actual tenía 41 tablas. La instalación de esta versión tiene **39 tablas**: incorpora `module_operation` para impedir duplicaciones y se excluyen `almacen`, `producto` y `movimientostock`, vacías y sin consultas directas en el código PHP entregado. El actualizador solo las elimina si están vacías y sin referencias externas declaradas. Las bases de otros ambientes requieren auditoría propia.

### Estado validado antes de la integración

El usuario confirmó el recorrido funcional de Inventario, Logística y Proveedores en su instalación local de **Laragon**, incluyendo los cambios de esta versión. Se conserva esta versión como punto de partida previo a incorporar los módulos del otro grupo.

Ruta local actual: `C:\laragon\www\sedema`. Desde la terminal de Laragon, ejecutar cada comando por separado:

```bat
cd /d C:\laragon\www\sedema
```

```bat
php database\create_admin.php admin admin@empresa.com "TU-CLAVE-SEGURA" Administrador SEDEMA
```

Sustituir correo y contraseña por datos propios; la contraseña debe tener al menos 12 caracteres. El administrador se crea después de una instalación limpia y su DNI provisional debe completarse. No registrar contraseñas reales en Git.

Para servir el proyecto desde `www` sin configurar un host dedicado, usar `http://localhost/sedema/public/` y el mismo valor en `APP_URL`. Si se configura un host virtual de Laragon, servir `public/` como raíz y ajustar `APP_URL` a ese host. Verificar la conexión MySQL real en `.env`; las rutas de XAMPP que siguen son una alternativa de instalación, no la ubicación local actual.

Para actualizar una instalación 2.1/2.1.1, ejecutar `database/update_013.sql` con la aplicación detenida antes de reemplazar el código. En instalaciones limpias, `database/install.sql` ya incorpora las migraciones 012 y 013; no importar nuevamente el instalador sobre datos existentes.

El commit incluye únicamente fuentes, recursos de aplicación, SQL de instalación/actualización y documentación. `.gitignore` excluye configuraciones privadas, logs y fotos de perfil. La limpieza de los archivos ya versionados forma parte de los cambios documentados de esta entrega y no elimina los archivos locales del cliente.

## 2. Requisitos

- Apache con permisos para usar los `.htaccess` incluidos (`AllowOverride` apropiado).
- PHP 8.1 o superior; extensiones `pdo_mysql`, `mbstring`, `fileinfo`; `openssl` para SMTP seguro y `curl` si se utiliza el adaptador API de pagos.
- MySQL 8.0.16 o superior, o MariaDB 10.4 o superior. Los `CHECK` deben estar habilitados. La base recibida fue exportada desde MySQL 8.4.3.
- phpMyAdmin o MySQL Workbench. Workbench debe conectarse al mismo servidor de XAMPP configurado en `.env`.
- Escritura en `storage/logs/` y `public/uploads/profiles/`.

Para XAMPP local, habitualmente: host `127.0.0.1`, puerto `3306`, base `sedema_db`, usuario `root`. Confirmar la configuración real; no asumir que todas las instalaciones usan el mismo puerto o contraseña.

## 3. Instalación nueva para un cliente

Este procedimiento se realiza **sobre una base vacía**. No usar `install.sql` para actualizar una base con datos.

1. Extraer la carpeta `sedema` del ZIP y copiar su contenido a:

   ```text
   C:\xampp\htdocs\sedema-auth\
   ```

2. Iniciar Apache y MySQL desde XAMPP.
3. Copiar `.env.example` como `.env`. No sobrescribir un `.env` existente.
4. Configurar la conexión y URL:

   ```dotenv
   APP_ENV=development
   APP_URL=http://localhost/sedema-auth/public
   APP_TIMEZONE=America/Argentina/Buenos_Aires
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sedema_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Generar una clave nueva desde CMD:

   ```bat
   C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   Copiar el resultado en `APP_KEY`. Para un servidor publicado, configurar `APP_ENV=production`, HTTPS y las credenciales propias del cliente.

6. En Workbench abrir `database/install.sql` y ejecutar el archivo completo. En phpMyAdmin usar **Importar** con ese archivo. El script crea y selecciona `sedema_db`, con InnoDB y `utf8mb4_unicode_ci`. Si el cliente necesita otro nombre, ajustar `CREATE DATABASE`, `USE` y `DB_DATABASE` de manera consistente.
7. El instalador crea las 39 tablas, claves, restricciones, siete unidades, la categoría «Sin categoría», el «Depósito principal» y los registros de versión 012 y 013. No crea usuarios, productos, ventas, pagos ni registros personales de prueba.
8. Crear al administrador desde CMD:

   ```bat
   cd /d C:\xampp\htdocs\sedema-auth
   C:\xampp\php\php.exe database\create_admin.php admin admin@empresa.com "REEMPLAZAR-POR-CLAVE-PROPIA" Nombre Apellido
   ```

   Usar correo propio y contraseña de al menos 12 caracteres. El script original crea un legajo con DNI provisional; completar ese DNI antes del uso real. La contraseña no se guarda en texto plano en la base.

9. La plantilla `.env.example` conserva su configuración original de correo SMTP. Para una prueba local sin envío cambiar `MAIL_TRANSPORT=log`; para uso real configurar los valores `MAIL_SMTP_*` del cliente. El modo log deja mensajes de prueba en `storage/logs/mail.log`.
10. Abrir `http://localhost/sedema-auth/public/`, ingresar con el administrador y verificar el acceso. Registrar productos y luego sus existencias mediante **Inventario → Movimientos → Ingreso**. Cada apertura queda así documentada con usuario y observación.

El script de instalación no borra bases existentes. Si se ejecuta sobre tablas ya creadas, fallará; detenerse y revisar. El DDL tiene confirmaciones implícitas: un error no garantiza que se revierta toda la instalación. No continuar ignorando errores ni utilizar `--force`.

## 4. Actualizar la instalación actual conservando datos

**Si ya instalaste 2.1 o 2.1.1 y verificaste sus cambios, esta entrega requiere únicamente `database/update_013.sql` antes de reemplazar el código.** No ejecutar `install.sql` sobre una base con datos.

1. Exportar un respaldo completo de la base y copiar la carpeta de aplicación fuera del sitio servido por Apache.
2. Detener Apache para impedir operaciones durante el cambio; mantener MySQL iniciado. Hacer primero el procedimiento sobre una copia.
3. En Workbench seleccionar la base correcta con doble clic o ejecutar `USE sedema_db;`.
4. Si el punto de partida es la base original `sedema_db(1).sql` y aún no se aplicó la versión 2.1, ejecutar primero `database/update_012.sql`. Si ya figura `012_inventory_logistics_suppliers_20261022` en `schema_migration`, omitir ese paso.
5. Ejecutar `database/update_013.sql` completo. Requiere permisos de creación de rutinas y modificación de tablas. El script comprueba la versión 012, incorpora `CANCELADO` al despacho y su historial, añade `operationVersion` y crea `module_operation`. Registra `013_dispatch_operations_20261022`. Puede repetirse sin reiniciar versiones de despacho ni eliminar operaciones registradas.
6. Fusionar los archivos del paquete con la aplicación. Conservar `.env`, `public/uploads/` y `storage/logs/` actuales. Los archivos nuevos son necesarios: no basta copiar los archivos PHP modificados. La sección 15 detalla los cambios respecto de 2.1.1.
7. Si se actualiza desde el ZIP original anterior a 2.1, retirar los documentos/SQL sustituidos detallados en la sección 9. Copiar sobre una carpeta no elimina automáticamente archivos antiguos.
8. Reiniciar Apache, actualizar con Ctrl+F5 y **recargar los formularios que estuvieran abiertos**. Los nuevos movimientos necesitan su clave de operación; los despachos también necesitan su versión actual.
9. Verificar cancelación, reprogramación y movimientos en una copia antes de usar la base de trabajo.

Desde CMD, seleccionando la carpeta de aplicación:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -p sedema_db < database\update_013.sql
```

Para `root` local sin contraseña, omitir `-p`. No usar `--force`. El DDL confirma cambios implícitamente y no se revierte íntegramente si ocurre un error; detenerse, revisar el mensaje, resolver y repetir con la aplicación detenida.

Los actualizadores no recalculan stock ni modifican los registros de usuarios, clientes, ventas, pagos o facturas. La 012 elimina las tres tablas heredadas solo si están vacías y sin referencias externas declaradas; si contienen datos se detiene para revisión. La 013 conserva los despachos y su historial existentes y no elimina información.

## 5. Inventario: funcionamiento y correcciones

### Operaciones

- Productos, categorías, unidades y depósitos; stock y mínimo por depósito.
- Alta y modificación, atributos personalizados `Nombre=Valor` y baja lógica.
- Ingresos, egresos y ajustes con usuario, fecha, observación y existencias anterior/resultante.
- Transferencias con dos movimientos vinculados mediante `correlationId`, dentro de una única transacción.
- Alertas por mínimo y consulta de movimientos.

### Cambios de esta versión

- Se valida que categoría y unidad existan y estén activas antes de guardar un producto.
- El precio admite hasta dos decimales y el máximo de `DECIMAL(12,2)`; se evita el redondeo silencioso de un tercer decimal.
- Cantidades y mínimos se limitan al rango de `DECIMAL(14,3)`; se controla también el resultado de ingresos y transferencias.
- Se impide cambiar la unidad de un producto que tenga stock, movimientos, pedidos o compras. Esto evita reinterpretar unidades históricas.
- La baja se bloquea cuando existen pedidos con pendiente, despachos abiertos o compras sin completar. Se conserva el historial mediante baja lógica.
- El contador de productos sin stock trata las posiciones inexistentes como cero, igual que el listado.
- Al crear un depósito se toma el identificador insertado directamente, evitando buscarlo de nuevo por nombre.
- Dirección y descripción de catálogos tienen límite de 255 caracteres; referencia del movimiento, 100.

Una posición de stock se crea al guardar el producto o dar de alta un depósito. Las cantidades iniciales se cargan como ingresos, no editando la existencia directamente. Las transferencias requieren posiciones en ambos depósitos y bloquean los depósitos en orden para reducir interbloqueos.

Permisos originales conservados: `inventory.view`, `inventory.manage`, `inventory.adjust`, `inventory.catalogs`.

## 6. Logística: funcionamiento y correcciones

| Operación | Efecto |
|---|---|
| Programar | Reserva cantidades pendientes; no descuenta stock. |
| En espera → En preparación | Verifica producto activo y stock suficiente. |
| En preparación → En ruta | Descuenta stock y pendiente; registra movimientos y ocupa el vehículo. |
| En ruta → Entregado | Libera el vehículo si no está asignado a otra ruta activa. |
| En espera/En preparación → Cancelado | Libera la reserva del pedido; no modifica stock ni cantidades pendientes. |
| En ruta/Entregado → Devuelto | Devuelve íntegramente los ítems del despacho al stock y al pendiente; conserva auditoría. |

Cambios:

- Fechas imposibles se rechazan comparando el valor parseado con la entrada original.
- Cantidades inválidas dejan de interpretarse silenciosamente como cero y se controla el máximo permitido.
- La modalidad coincide con el pedido; `ACOPIO` permite los dos modos operativos de despacho existentes.
- Los pedidos cancelados se excluyen del selector y se vuelven a comprobar antes de preparar o iniciar ruta.
- Productos inactivos se rechazan al programar y validar stock; el depósito debe seguir activo para iniciar el proceso. Se permite devolver material de productos ya inactivos para recuperar existencias.
- Se vuelve a comprobar que el vehículo esté activo al salir a ruta.
- El guardado y la baja de vehículos usan bloqueo transaccional. No se permite poner en mantenimiento o dar de baja un vehículo con despachos abiertos.
- Repetir una activación/baja con el mismo valor ya no produce un falso error por cero filas modificadas.
- Una devolución de un despacho entregado no libera el vehículo si ya está en otra ruta. La liberación respeta además el estado de mantenimiento.
- Se controla el máximo de stock tras la devolución. Si reponer el pendiente superaría lo solicitado, la operación se rechaza y revierte, en lugar de ocultar la diferencia con `LEAST`.
- Se ordenan los ítems por producto para reducir conflictos de bloqueo.

Permisos originales conservados: `logistics.view`, `logistics.manage`, `logistics.dispatch`.

La devolución sigue siendo total por despacho; no se incorporan devoluciones parciales. La agenda solo detecta coincidencias de fecha/hora exactas: no calcula duración de viajes. La capacidad de carga no se valida contra materiales porque el catálogo no dispone de peso/volumen normalizado. Las reservas son cantidades de pedido, no una reserva física global del stock entre todos los pedidos.

## 7. Proveedores: funcionamiento y correcciones

- Padrón por razón social y CUIT; remitos con fecha, número, productos, cantidades y precios.
- Recepciones parciales/totales por depósito; stock y movimiento se confirman en la misma transacción que la recepción.
- Estados: `BORRADOR`, `PENDIENTE`, `EMITIDA`, `RECEPCION_PARCIAL`, `RECEPCION_TOTAL`, `CANCELADA`.
- Solo se cancela un remito sin recepciones. Las recepciones confirmadas no se editan ni se borran; una corrección requiere ajuste de inventario identificado.

Cambios:

- Se utiliza el esquema actual: productos de `inventory_product`, depósito en `recepcioncompra.idWarehouse`, identificadores `UNSIGNED` y cantidades con tres decimales.
- Se corrige el límite numérico para admitir todo el rango válido de cantidades y mantener el rango del precio de compra.
- Se rechazan remitos sin renglones al recibir, además de cantidades superiores al pendiente.
- El texto del movimiento se limita a 500 caracteres incluyendo el prefijo y número de remito: una observación larga ya no desborda la columna ni revierte una recepción válida.
- Los renglones se procesan por producto para reducir conflictos entre recepciones simultáneas.
- La confirmación de cancelación deja de usar `onsubmit` en línea (bloqueado por la política CSP). Utiliza `data-confirm` y el JavaScript externo existente, cargado solo en las páginas de Proveedores.
- La base evita productos repetidos dentro de un mismo remito o recepción.

Permisos originales conservados: `suppliers.view`, `suppliers.manage`, `suppliers.receive`. No se agregan órdenes de compra ni cuentas por pagar.

## 8. Registro de archivos de código modificados

| Archivo | Cambio |
|---|---|
| `src/Inventory/InventoryService.php` | Validaciones, rangos, unidad histórica, baja y creación de depósito. |
| `src/Inventory/InventoryRepository.php` | Bloqueo de producto, consultas de actividad/pendientes, catálogos y contador sin stock. |
| `src/Logistics/LogisticsService.php` | Programación, cantidades/fechas, transiciones, vehículos y devoluciones. |
| `src/Logistics/LogisticsRepository.php` | Selector, orden de ítems, disponibilidad de flota y reposición de pendiente. |
| `src/Suppliers/SupplierService.php` | Rangos, recepción vacía y longitud de observaciones. |
| `src/Suppliers/SupplierRepository.php` | Orden de procesamiento de los renglones. |
| `src/Suppliers/SupplierPage.php` | Carga del JavaScript externo de confirmación existente. |
| `public/suppliers/detail.php` | Confirmación de cancelación compatible con CSP. |

Archivos creados: `database/install.sql` y `database/update_012.sql`. Archivo reescrito: este `README.md`. `database/create_admin.php` conserva su implementación original.

## 9. Depuración de archivos y documentación

Los originales entregados por el usuario no se modificaron. La depuración se aplica a esta copia de distribución. No se entrega el volcado operativo como instalador.

| Archivo retirado del paquete | Motivo |
|---|---|
| `database/006_clients_schema.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/003_personnel_schema.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/002_inventory_schema.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/004_user_access_schema.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/005_user_account_management.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/001_auth_schema.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/007_sales_orders_schema.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/010_integration_merge.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/sedema_db_unificada.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/011_suppliers_schema.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/009_payments_diagram_alignment.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `database/008_payments_schema.sql` | Sustituido por install.sql / update_012.sql; instalación antigua o migración histórica incompatible con esta entrega. |
| `README v2.0.md` | Duplicado de README.md o de .env.example. |
| `.env.txt` | Duplicado de README.md o de .env.example. |
| `docs/USUARIOS_Y_ACCESOS.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/PROVEEDORES.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/VENTAS_PEDIDOS_COMPROBANTES.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/LOGISTICA.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/PAGOS_COBRANZAS.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/INVENTARIO.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/CLIENTES.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/PERSONAL.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/BASE_DE_DATOS.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/ANALISIS.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `docs/INTEGRACION.md` | Información funcional consolidada y corregida en README.md; instrucciones históricas reemplazadas. |
| `public/uploads/profiles/ca5f64959426daeedfec000a2c705711.png` | Foto de un ambiente anterior; excluida del paquete limpio. Conservar uploads del cliente al actualizar. |
| `public/uploads/profiles/a4d6fbe7012bb2b21f80b2a1f6b9a3ca.jpg` | Foto de un ambiente anterior; excluida del paquete limpio. Conservar uploads del cliente al actualizar. |
| `public/uploads/profiles/user_1_e2d5c309f2d7.png` | Foto de un ambiente anterior; excluida del paquete limpio. Conservar uploads del cliente al actualizar. |
| `public/uploads/profiles/db583abd3ae86cbfc449cdc4d69b0f03.jpg` | Foto de un ambiente anterior; excluida del paquete limpio. Conservar uploads del cliente al actualizar. |

Los SQL 001–011 y el volcado antiguo se retiran de esta distribución porque el punto de partida ya es la base actual unificada. No deben ejecutarse en cadena con los scripts nuevos. Para recuperar una instalación histórica, utilizar el respaldo del ZIP original y auditar esa versión por separado.

Se conserva `.env.example` como plantilla única y `docs/IDENTIDAD_VISUAL.md` porque cumple una función independiente. Las fotos excluidas solo se eliminan de este ZIP limpio; no se deben borrar las fotos del cliente al actualizar.

## 10. Verificación de entrega

Verificación de la base 2.1 realizada en un entorno temporal con **PHP 8.3.6 y MariaDB 10.11.14**:

- Comprobación sintáctica con `php -l` de los 100 archivos PHP: sin errores.
- Ejecución completa de `install.sql`: 38 tablas y catálogos mínimos creados.
- Importación del volcado actual y ejecución de `update_012.sql` dos veces: correctas; 38 tablas finales y versión sin duplicación.
- Comparación de usuarios, pedidos, pagos y stock antes/después de actualizar: sin diferencias.
- Rechazo de stock negativo por la base y rechazo de limpieza de una tabla antigua con datos: correctos; registro conservado.
- 30 comprobaciones de servicios: validación de mínimos/precios/categorías/cantidades; ingreso, rechazo sin saldo, transferencia; cambio de unidad y baja con pendientes; recepción parcial/total con observación de 500 caracteres y rechazo de exceso/repetición; fecha imposible, baja de vehículo con despacho abierto, egreso y devolución; rechazo de transiciones repetidas, conservación del vehículo ocupado por otra ruta, pendiente correcto, liberación e idempotencia; listados e historiales; login y creación CLI de administrador.
- Comparación de archivos: ocho archivos de código modificados, todos en los tres módulos autorizados. El código de los demás módulos conserva sus bytes originales.

La verificación de 2.1 no incluyó MySQL 8.4, XAMPP de Windows, navegador ni concurrencia. Las comprobaciones nuevas de procesos simultáneos en 2.2 se detallan en la sección 15. Las comprobaciones temporales no se incorporan como suite de pruebas al paquete.

### Recorrido de aceptación en el XAMPP del cliente

1. Instalar en una base vacía y crear un administrador; confirmar acceso al panel y existencia de siete unidades, una categoría y un depósito.
2. Crear un producto por unidad y otro fraccionable; rechazar un precio con tres decimales y cantidades fuera de rango.
3. Registrar ingreso, egreso y ajuste; comprobar que un egreso sin existencias se rechace y conserve el saldo anterior.
4. Crear un segundo depósito, transferir materiales y comprobar los dos movimientos correlacionados.
5. Registrar un proveedor y remito de 50 unidades; recibir 10 y luego 40; confirmar estados parcial/total y dos ingresos de stock. Rechazar una recepción superior al pendiente.
6. Registrar una recepción con observación de 500 caracteres; confirmar que se guarda la recepción y que el movimiento respeta su límite.
7. Desde un pedido existente, programar entregas parciales; preparar, iniciar ruta, entregar y devolver. Comprobar cantidades pendientes, movimientos e historial.
8. Confirmar que una repetición de transición no descuenta/reintegra stock nuevamente. Rechazar fechas imposibles y vehículos inactivos.
9. Mantener otro despacho del mismo vehículo en ruta y devolver uno anterior: el vehículo debe seguir ocupado.
10. Intentar bajar productos con operaciones pendientes o cambiar la unidad con historia; verificar el rechazo.
11. Actualizar una copia de la base actual; comparar usuarios, pedidos y pagos antes/después, repetir el actualizador y confirmar que no se duplican restricciones ni registros de versión.
12. Recorrer las pantallas de los otros módulos para comprobar compatibilidad del esquema, sin cambiar sus flujos.

La validación del servidor local del cliente sigue siendo necesaria para su configuración de Apache, extensiones PHP, permisos, correo y datos reales.

## 11. Referencia funcional de los demás módulos (sin cambios)

- **Acceso y usuarios:** sistema cerrado; inicio por usuario/correo/DNI y contraseña con hash; creación de cuentas por administrador; primer acceso obligatorio, envío/reenvío de credenciales temporales y gestión de roles/permisos. La recuperación y el bloqueo por intentos permanecen como estaban.
- **Personal:** legajos, altas/bajas lógicas, liquidaciones mensuales, haberes/descuentos y recibos. No se alteran cálculos ni permisos.
- **Clientes:** padrón, búsqueda y clasificación por tipo de cliente. Los empleados con permisos de gestión conservan la creación de clientes.
- **Ventas/Pedidos:** selección de artículos, modalidades `EN_CORRALON`, `ENTREGA_DOMICILIO`, `ACOPIO`, descuentos/recargos, cancelación y comprobantes según la implementación original.
- **Pagos:** efectivo, cheque, tarjeta, transferencia y débito a cuenta corriente. La pasarela original usa `PAYMENT_GATEWAY_MODE=mock` para pruebas y un adaptador genérico al configurar API. La cuenta corriente no incorpora un nuevo circuito de cancelación de deuda en esta entrega.
- **Perfil:** datos y fotos; las fotos nuevas se guardan en `public/uploads/profiles/`.

### Límites heredados fuera del alcance

Esta versión no traslada facturas/remitos de Ventas a Pagos, no modifica la cancelación/edición de pedidos ni resuelve concurrencia de cobros. No convierte el adaptador de pagos en integración con Mercado Pago. La protección entre módulos no puede garantizarse frente a un módulo externo que no respete los mismos bloqueos; se mantienen los contratos existentes de Ventas y Pagos. El campo `inventory_movement.actorUserId` sigue siendo una referencia lógica sin clave foránea para conservar la auditoría independiente del ciclo de usuarios.

Se mantienen `detallepedido.idProducto` e `idInventoryProduct` para compatibilidad con Ventas. Deben referirse al mismo producto cuando ambos estén informados; Logística prioriza `idInventoryProduct`, como en la versión original. Su consolidación futura debe coordinarse con Ventas.

## 12. Historial consolidado

- v1.2–v1.5: usuarios/accesos, primer ingreso, gestión de cuentas y perfil.
- v1.6: clientes.
- v1.7–v1.8: pedidos, ajustes y comprobantes.
- v1.9–v1.9.1: pagos, detalles por medio y cuenta corriente.
- v2.0: integración de las ramas de clientes/usuarios y logística; incorporación de proveedores.
- **v2.1 — 22/10/2026:** depuración limitada a Inventario, Logística y Proveedores; base limpia de 38 tablas; actualización protegida de la base actual; consolidación documental y retiro de archivos sustituidos. Las ocho modificaciones de código, dos SQL nuevos, README y 29 exclusiones se detallan arriba.

- **v2.1.1 — 22/10/2026:** vista previa e impresión de remitos de proveedor; sin cambio de esquema.
- **v2.2 — 22/10/2026:** cancelación/reprogramación de despachos pendientes, claves de operación, control de revisión y verificación de concurrencia; migración 013 y base de 39 tablas.

## 13. Diagnóstico y recuperación

- **Servicio de acceso no disponible:** comprobar que MySQL esté iniciado y que host, puerto, usuario y base de `.env` coincidan con el servidor. No reemplazar `.env` para intentar reparar el acceso.
- **Tabla/columna inexistente:** comprobar que se utilizó el instalador de esta versión o que terminó el actualizador. No ejecutar migraciones antiguas indiscriminadamente.
- **SQLSTATE 45000 en la actualización:** leer el mensaje específico; el procedimiento encontró un esquema/dato que requiere revisión. No se deben desactivar controles ni borrar datos para continuar.
- **No existe posición de stock:** guardar el producto desde Inventario para crear posiciones en los depósitos activos. No insertar saldos manuales; usar movimientos para cargar cantidades.
- **Código de incidente:** consultar `storage/logs/app.log` y conservar el código junto con la operación realizada.
- **Revertir la entrega:** detener Apache y restaurar juntos la carpeta y la base del respaldo tomado antes del cambio. No restaurar solo los archivos si se necesita volver también al esquema anterior.


## 14. Actualización 2.1.1 — Vista previa e impresión de remitos de proveedor

Desde **Proveedores → Remitos de compra → Ver detalle → Previsualizar / imprimir remito**, se abre una pestaña con el documento. El botón **Imprimir / guardar PDF** abre el diálogo de impresión del navegador. Allí se elige impresora o «Guardar como PDF»; «Volver al detalle» permite regresar al registro.

La vista muestra número original del remito del proveedor, fecha de emisión, número interno SEDEMA, estado, razón social, CUIT, contacto, destinatario SEDEMA, código y nombre del producto, cantidad del remito, unidad, precio, subtotal y total. Incluye espacios para firma/aclaración y fecha de recepción. En borrador o cancelado se imprime una marca explícita de estado. Las cantidades reflejan el remito registrado; las recepciones parciales/totales permanecen en su circuito existente.

Se mantiene el permiso `suppliers.view` y la obligación de completar el primer ingreso. La consulta no cambia remitos, recepciones ni stock. Un identificador inválido o inexistente devuelve 404. Todo dato textual se escapa antes de mostrarlo. El botón de impresión utiliza JavaScript externo, compatible con la política CSP existente.

La hoja usa formato A4 con márgenes, encabezados de tabla repetibles y controles ocultos al imprimir. No depende de librerías externas ni servicios de PDF. El resultado se genera a partir del registro actual del sistema; no incorpora una imagen o archivo digitalizado del documento original del proveedor.

### Archivos de esta actualización

- Modificado: `public/suppliers/detail.php` — acceso a la vista previa.
- Nuevos: `public/suppliers/remit.php`, `public/suppliers/remit-print.css` y `public/suppliers/remit-print.js`.
- Modificado: `README.md` — esta sección y versión de entrega.

**El paso histórico de 2.1 a 2.1.1 no requiere SQL ni cambios de `.env`.** La actualización actual a 2.2 sí requiere `update_013.sql`, como indica la sección 4. Para aplicar únicamente la antigua 2.1.1 sobre 2.1, bastaban los cuatro archivos de Proveedores anteriores y el README. El ZIP completo conserva las correcciones y los scripts de la versión anterior; no volver a ejecutar el instalador sobre una base existente.

### Verificación de la actualización 2.1.1

Comprobación sintáctica de los 101 archivos PHP y del JavaScript nuevo: correcta. Con PHP 8.3.6 y MariaDB 10.11.14 se verificaron por HTTP el acceso desde el detalle, contenido y total del remito, carga de CSS/JavaScript, política CSP, redirección sin sesión, rechazo 403 sin `suppliers.view`, identificadores inválidos/inexistentes con 404, marca de cancelación y escape de texto del proveedor. La consulta no ejecuta escrituras.

No se pudo ejecutar la comprobación visual en navegador ni abrir el diálogo de impresión en este entorno. Verificar en XAMPP: abrir la vista previa, elegir «Imprimir / guardar PDF» y confirmar que los controles se ocultan y el contenido se distribuye correctamente en A4. Para remitos extensos, revisar la repetición del encabezado y el salto entre páginas.


## 15. Actualización 2.2 — Despachos pendientes y operaciones simultáneas

### Cancelación de despachos

En **Logística → Abrir despacho**, un usuario con `logistics.manage` dispone de «Cancelar despacho» únicamente en `EN_ESPERA` o `EN_PREPARACION`. Debe completar un motivo de hasta 250 caracteres y confirmar la acción. El despacho pasa a `CANCELADO`, conserva su remito e historial y puede consultarse con el filtro «Cancelado».

Las reservas se calculan sobre despachos en espera/preparación. Al cancelar, la cantidad vuelve a estar disponible para otro despacho del mismo pedido, sin crear un movimiento de stock ni aumentar `cantidadPendiente`, porque todavía no había ocurrido una salida. No se cancela un despacho en ruta, entregado o devuelto. No se elimina el despacho ni se cambia el estado comercial de su pedido.

### Reprogramación

En esa misma pantalla, «Reprogramar despacho» permite modificar fecha/hora y vehículo antes de la salida, con motivo obligatorio. El retiro en corralón sigue sin vehículo; la entrega a domicilio requiere uno activo y disponible. Se comprueba que no exista otro despacho del vehículo en la misma fecha/hora, excluyendo el despacho editado. Se rechazan fechas imposibles, pasadas o solicitudes sin cambios.

Se conservan depósito, destino, modalidad, artículos, cantidades, estado de preparación y reserva. El historial registra fecha anterior/nueva, identificador del vehículo anterior/nuevo, motivo, usuario y momento. No se modifica el remito para crear otro ni se mueve stock.

### Protección frente a reenvíos

Cada formulario de movimiento, transferencia, recepción, programación, transición, cancelación o reprogramación lleva una `operation_key` aleatoria propia. `OperationGuard` la registra en `module_operation` dentro de la misma transacción que los cambios. Su clave primaria serializa los reenvíos, incluso desde conexiones diferentes.

- Un reenvío con la misma clave, usuario, tipo de operación y datos devuelve el resultado ya confirmado sin repetir efectos.
- Una clave utilizada con otros datos, usuario o acción se rechaza. Para una operación nueva, abrir/recargar el formulario y obtener otra clave.
- Las operaciones rechazadas se revierten con su clave: después de corregir el problema se puede reintentar, siempre que el formulario y los permisos sigan vigentes.
- La clave no reemplaza la sesión, permisos ni CSRF. Si el token CSRF anterior ya rotó, el controlador puede informar formulario vencido; la operación confirmada no se duplica.
- Las validaciones previas siguen aplicándose: por ejemplo, una fecha que ya pasó puede impedir reenviar una programación antigua, sin volver a crearla.
- No borrar `module_operation` mientras deban protegerse reenvíos históricos. Crece una fila por operación confirmada; cualquier política futura de retención deberá definir qué reenvíos se dejan de reconocer.

`despacho.operationVersion` aumenta al reprogramar, cancelar o cambiar de estado. Un formulario abierto antes de que otro usuario cambie el despacho se rechaza para evitar sobrescribir su cambio. El reenvío idéntico de una operación ya confirmada recupera su resultado antes de comprobar la versión histórica del formulario.

Se mantienen bloqueos transaccionales de stock, pedido, remito de proveedor y vehículo. Las consultas de conflicto de vehículo ahora son lecturas con bloqueo para ver la última programación confirmada aun después de esperar a otro usuario. Los interbloqueos y tiempos de espera de bloqueo de las operaciones protegidas se reintentan hasta tres veces, con reversión completa; si persisten se informa que se reintente. No se repiten llamadas a servicios externos.

### Cambios técnicos de esta entrega

| Archivo | Cambio respecto de 2.1.1 |
|---|---|
| `README.md` | Versión, instrucciones de instalación/actualización, historial y verificaciones. |
| `database/install.sql` | Instalación limpia actualizada a 39 tablas y versión 013. |
| `database/update_013.sql` | Nuevo: estado CANCELADO, revisión del despacho, registro de operaciones y migración 013. |
| `public/inventory/movement.php` | Clave independiente para movimientos y transferencias. |
| `public/logistics/action.php` | Permisos/controladores para cancelar y reprogramar; revisión de transiciones. |
| `public/logistics/detail.php` | Claves, revisión e inclusión de acciones de despachos pendientes. |
| `public/logistics/dispatch.php` | Clave de operación en la programación. |
| `public/logistics/index.php` | Filtro de despachos cancelados. |
| `public/logistics/pending-actions.php` | Nuevo: formularios de cancelación y reprogramación con permiso logistics.manage. |
| `public/suppliers/detail.php` | Clave de operación en los formularios de recepción. |
| `src/Inventory/InventoryRepository.php` | Acceso a la conexión para transacciones protegidas. |
| `src/Inventory/InventoryService.php` | Movimientos y transferencias protegidos por clave de operación. |
| `src/Inventory/OperationGuard.php` | Nuevo: clave duradera, comprobación de revisión, transacción y reintentos de bloqueo. |
| `src/Logistics/LogisticsRepository.php` | Reprogramación, aumento de revisión y consulta de conflicto con bloqueo. |
| `src/Logistics/LogisticsService.php` | Cancelación, reprogramación, revisión e idempotencia de programación y transiciones. |
| `src/Suppliers/SupplierRepository.php` | Acceso a la conexión para transacciones protegidas. |
| `src/Suppliers/SupplierService.php` | Recepciones protegidas por clave de operación. |

No se modifica código de Autenticación, Usuarios, Personal, Clientes, Ventas, Pagos, Perfil ni Dashboard. Los estilos compartidos y la impresión de remitos de proveedor de 2.1.1 se conservan. La utilidad común se encuentra en `src/Inventory/OperationGuard.php` y solo se utiliza desde los tres módulos autorizados.

### Validación de 2.2

Se completaron **44 comprobaciones** con PHP 8.3.6 y MariaDB 10.11.14. Las pruebas de concurrencia lanzaron entre dos y cuatro procesos PHP independientes, con conexiones PDO separadas y una barrera de inicio común; no dependieron de la serialización de una sesión del navegador.

- Sintaxis de los 103 archivos PHP de la entrega: correcta.
- Instalación limpia: 39 tablas. Importación de la base actual, migraciones 012+013 y repetición de 013: correctas; comparación de usuarios, pedidos, pagos y existencias sin diferencias.
- Ingresos iguales/distintos simultáneos, egresos sobre saldo limitado, transferencias repetidas y transferencias en sentidos opuestos: sin duplicación, pérdida de actualizaciones ni stock negativo.
- Recepciones repetidas y recepciones distintas compitiendo por el mismo pendiente: un solo comprobante por clave y cantidades recibidas dentro de lo solicitado.
- Programaciones repetidas y programaciones distintas del mismo pedido: un despacho por clave y reservas dentro del pendiente.
- Cancelación repetida: una entrada de historial, liberación de reserva y cero cambios de existencias. Cancelación en preparación permitida; cancelación/reprogramación después de iniciar salida rechazadas.
- Reprogramaciones simultáneas, formularios antiguos y transición posterior a una reprogramación: control de revisión correcto.
- Cancelación contra salida simultánea: solo una confirma; stock y pendiente coherentes con el estado final.
- Pedidos distintos disputando el mismo vehículo/horario y cambio de vehículo al reprogramar: agenda sin duplicación en el horario exacto.
- Dos salidas sobre el mismo stock limitado y reenvíos de una misma transición: sin descuento duplicado; historial y pendiente coherentes.
- Verificación HTTP: formularios, reenvío con CSRF nuevo, cancelación desde el controlador, filtro Cancelado, impresión de remito de proveedor conservada y rechazo 403 a cancelar/reprogramar sin logistics.manage.
- Sin claves de operación incompletas después de errores de negocio. Comparación de archivos contra 2.1.1: código de los demás módulos idéntico.

Los archivos de comprobación son temporales y no se incluyen en la aplicación. Estos escenarios validan los casos descritos; no constituyen una prueba exhaustiva de todas las combinaciones o una prueba de carga.

### Límites que continúan vigentes

La agenda detecta coincidencias de fecha/hora exactas, no duración o solapamiento de viajes. No se agregan reservas físicas globales de stock ni devoluciones parciales. La protección descrita cubre las operaciones de estos tres módulos; el código de los demás módulos conserva sus reglas y no se garantiza sincronización con integraciones externas que escriban directamente en las tablas sin respetar estos bloqueos. No se hicieron pruebas visuales en navegador, carga masiva ni ejecución sobre MySQL 8.4 o XAMPP/Windows en esta fase.


## 16. Preparación del repositorio antes de la integración

Se agrega la documentación del entorno Laragon y de la validación funcional confirmada por el usuario. Se amplía `.gitignore` para excluir variantes privadas de `.env`, registros de ejecución y fotos de perfiles, conservando `.env.example`, `storage/logs/.gitkeep` y la protección `.htaccess` de perfiles.

Se conserva la configuración privada de cada instalación fuera del repositorio. La base operativa y los usuarios creados localmente no se incluyen en este commit. Los 29 archivos sustituidos o excluidos se retiran de la versión actual del repositorio; su historial anterior permanece disponible en Git.
