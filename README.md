# README 

Sistema de gestión para un corralón de materiales de construcción, desarrollado en PHP y MySQL/MariaDB. La versión integrada v2.0 reúne autenticación, usuarios y accesos, clientes, ventas y pedidos, pagos y cobranzas, inventario, personal/haberes y despacho/logística.

## Índice

1. [Requisitos](#1-requisitos)
2. [Instalación limpia](#2-instalación-limpia)
3. [Actualización de una instalación existente](#3-actualización-de-una-instalación-existente)
4. [Migraciones de base de datos](#4-migraciones-de-base-de-datos)
5. [Configuración](#5-configuración)
6. [Módulos y funcionalidades](#6-módulos-y-funcionalidades)
7. [Integración entre ventas, inventario y logística](#7-integración-entre-ventas-inventario-y-logística)
8. [Archivos principales](#8-archivos-principales)
9. [Seguridad](#9-seguridad)
10. [Verificación funcional](#10-verificación-funcional)
11. [Historial de actualizaciones](#11-historial-de-actualizaciones)
12. [Documentos de origen](#12-documentos-de-origen)

## 1. Requisitos

- XAMPP con PHP **8.1 o superior**, con las extensiones `pdo_mysql` y `mbstring`.
- MariaDB **10.4** o MySQL **8.0**.
- Apache con `mod_rewrite` habilitado.
- phpMyAdmin o MySQL Workbench para importar y ejecutar los scripts SQL.

Ruta local utilizada en las instrucciones:

```text
C:\xampp\htdocs\sedema-auth\
```

Base de datos: `sedema_db`.

## 2. Instalación limpia

1. Hacer una copia de seguridad de cualquier instalación anterior y de su base de datos.
2. Copiar la carpeta del proyecto integrado como `C:\xampp\htdocs\sedema-auth`.
3. Copiar `.env.example` como `.env` y completar `APP_KEY`, los datos de conexión a la base y la configuración de correo.
4. Importar `database/sedema_db_unificada.sql` desde MySQL Workbench o phpMyAdmin.
5. Confirmar que `.env` tenga `DB_DATABASE=sedema_db`.
6. Reiniciar Apache y MySQL desde XAMPP.
7. Abrir `http://localhost/sedema-auth/public/`.

El SQL conserva los datos de la exportación más reciente de Clientes/Usuarios e incorpora la estructura requerida por Logística. Antes de usar datos reales, revisar los usuarios, correos y registros de prueba que contiene la exportación.

La instalación limpia y las migraciones sobre una base existente son procedimientos diferentes: no importar nuevamente toda la base sobre una instalación en funcionamiento como parte de un parche.

## 3. Actualización de una instalación existente

### 3.1. Reglas comunes para aplicar parches

1. Respaldar el proyecto y `sedema_db` antes de modificar archivos o ejecutar SQL.
2. Fusionar los archivos del parche con la carpeta existente, manteniendo su estructura.
3. Reemplazar únicamente los archivos incluidos en el parche; no borrar carpetas completas.
4. Conservar el `.env` actual. No sustituirlo por `.env.example`.
5. Ejecutar una sola vez la migración que corresponda a la actualización, cuando exista.
6. Reiniciar Apache. Cuando corresponda, actualizar el navegador con `Ctrl + F5`.

### 3.2. Pasar de Clientes/Usuarios v1.9.1 al proyecto integrado v2.0

Si la base de la rama Clientes/Usuarios v1.9.1 ya está instalada y funciona:

1. Hacer un respaldo completo.
2. Ejecutar una sola vez `database/010_integration_merge.sql`.
3. Reemplazar el código por el proyecto integrado, conservando el `.env` local y `public/uploads/`.
4. Reiniciar Apache.

## 4. Migraciones de base de datos

| Versión | Script | Aplicación documentada |
| --- | --- | --- |
| v1.2 | `database/004_user_access_schema.sql` | Usuarios y Accesos sobre Personal v1.1. |
| v1.5 | `database/005_user_account_management.sql` | Administración y eliminación de cuentas. |
| v1.6 | `database/006_clients_schema.sql` | Módulo Clientes. |
| v1.7 | `database/007_sales_orders_schema.sql` | Pedidos, ajustes/financiación y comprobantes. |
| v1.9 | `database/008_payments_schema.sql` | Pagos y Cobranzas. |
| v1.9.1 | `database/009_payments_diagram_alignment.sql` | Alineación de Pagos con diagramas y RF3.1–RF3.3. |
| v2.0 | `database/010_integration_merge.sql` | Integración sobre una base Clientes/Usuarios v1.9.1 existente. |
| v2.0, instalación limpia | `database/sedema_db_unificada.sql` | Base completa consolidada; alternativa a la actualización por migraciones. |

Las versiones v1.3, v1.4, v1.7.1, v1.8 y v1.8.1 indican que **no requieren cambios de base de datos**. En particular, el paso de v1.2 a v1.3 no requiere migración.

Para una actualización histórica progresiva, respetar la secuencia de versiones y ejecutar únicamente los scripts pendientes que correspondan a la base de partida. Los README no documentan una migración universal desde cualquier versión ni garantizan que los scripts puedan repetirse. No ejecutar toda esta tabla indiscriminadamente sobre una base unificada.

## 5. Configuración

### 5.1. Variables generales

En una instalación limpia se debe completar `.env` a partir de `.env.example`. En una actualización se conserva el archivo existente.

```dotenv
DB_DATABASE=sedema_db
```

Completar también la conexión a la base y `APP_KEY` según la plantilla del proyecto. Para producción, los README indican `APP_ENV=production` y una `APP_KEY` de al menos 32 caracteres.

### 5.2. Correo simulado para pruebas

```dotenv
MAIL_TRANSPORT=log
```

Este modo no envía correos reales. El mensaje simulado, que contiene la contraseña temporal, se guarda en:

```text
storage/logs/mail.log
```

### 5.3. SMTP de Gmail

Configuración documentada desde v1.3:

```dotenv
MAIL_TRANSPORT=smtp
MAIL_FROM=TU_GMAIL@gmail.com
MAIL_SMTP_HOST=smtp.gmail.com
MAIL_SMTP_PORT=587
MAIL_SMTP_ENCRYPTION=tls
MAIL_SMTP_USERNAME=TU_GMAIL@gmail.com
MAIL_SMTP_PASSWORD=CONTRASENA_DE_APLICACION_DE_GOOGLE
```

También se documenta la alternativa SSL con puerto `465` y cifrado `ssl`.

Usar una **contraseña de aplicación de Google**, no la contraseña habitual de Gmail. Según los README, su generación requiere la verificación en dos pasos activa en la cuenta de Google.

### 5.4. Pasarela de pagos en modo de prueba

```dotenv
PAYMENT_GATEWAY_MODE=mock
```

El modo `mock` permite probar tarjeta y transferencia sin utilizar dinero real. Una integración real requiere elegir un proveedor, configurar sus credenciales y adaptar su API específica. El adaptador está en `src/Payments/PaymentGateway.php`; los README no acreditan una pasarela real ya configurada.

## 6. Módulos y funcionalidades

### 6.1. Autenticación, Usuarios y Accesos

- Crear cuentas de empleados y asignar roles y permisos.
- En el alta inicial, el empleado accede con correo o DNI y contraseña temporal.
- La contraseña temporal tiene exactamente **8 caracteres** y se almacena como hash en la base; el correo simulado contiene la contraseña necesaria para el primer acceso.
- Antes de entrar al dashboard, el sistema exige definir un usuario definitivo y una nueva contraseña.
- Desde v1.3 se elimina el campo **Sector** de la interfaz y del listado, y se mantiene **Rol de acceso**. Esta indicación reemplaza la referencia a sector/rol/permisos de las instrucciones v1.2.
- Generar y reenviar una contraseña temporal para cuentas existentes.
- Mostrar u ocultar la nueva contraseña y su repetición en `first-access.php` mediante el botón de ojo.
- Modificar el correo desde **Administrar cuenta**.
- Eliminar una cuenta o varias cuentas seleccionadas desde el listado.
- Impedir que el administrador conectado elimine su propia cuenta.
- Conservar el legajo y las referencias históricas al eliminar una cuenta; la cuenta deja de aparecer en el listado y no puede iniciar sesión.
- Si se crea nuevamente una cuenta para el mismo DNI, reutilizar de forma segura el registro histórico.

Acceso administrativo documentado: **Administrador → Personal y usuarios → Usuarios y accesos**.

### 6.2. Perfil personal y presentación del usuario

- Menú desplegable al hacer clic en el usuario o avatar del menú lateral y de la barra superior, tanto para administradores como para empleados.
- Pantalla `public/profile.php` para editar nombre, apellido, nombre de usuario, correo y teléfono propios.
- Cambio de foto en formato JPG, PNG o WebP, con un máximo de **2 MB**.
- Cambio de contraseña con verificación de la contraseña actual.
- DNI, rol y permisos de solo lectura para el propio usuario.
- Foto actualizada reflejada en la sesión y en los menús del sistema.
- Desde v1.4, la foto cargada durante el primer acceso se muestra en el menú lateral, la barra superior y al navegar por Personal e Inventario.
- Si no existe una foto válida, se conserva la inicial del usuario como avatar.

### 6.3. Clientes

- Alta, consulta, modificación y clasificación de clientes.
- Baja lógica y reactivación.
- Tipos: `PARTICULAR`, `ALBAÑIL`, `EMPRESA`, `MAYORISTA` y `CONTRATISTA`.
- Desde v1.8.1, los roles `CAJA` y `VENDEDOR` pueden registrar y modificar clientes.
- **Nuevo pedido** incluye el acceso **Registrar nuevo cliente**; después de guardarlo, el sistema vuelve al pedido con el cliente preseleccionado.

El módulo v1.6 todavía no implementaba pedidos, ajustes, remitos ni facturación. Esas funciones se incorporan en v1.7.

### 6.4. Ventas y Pedidos

La actualización v1.7 amplía Clientes para cubrir: padrón de clientes, pedidos, ajustes/financiación, remitos de salida y factura para pedidos pagados.

- Acceso documentado desde **Ventas y pedidos** o **Clientes** en el dashboard.
- Consulta de productos y existencias mediante `inventory_product` e `inventory_stock`.
- Desde v1.7.1 se eliminan las etiquetas RF2.2, RF2.3, RF2.4 y RF2.5 de la interfaz, conservando los títulos y las descripciones funcionales.

### 6.5. Pagos y Cobranzas

La versión v1.9 incorpora el módulo; v1.9.1 lo ajusta a los diagramas y a **RF3.1–RF3.3**:

- Medios de pago: efectivo, tarjeta, cheque y transferencia.
- Cuenta corriente separada como imputación contable.
- Efectivo: importe recibido y cálculo del vuelto.
- Cheque: banco, número, titular, fechas y validación o rechazo.
- Tarjeta y transferencia: solicitud de pasarela, QR/referencia y verificación antes de aprobar.
- Descuento o recargo directo en cobranza.
- Consulta detallada de los movimientos de cuenta corriente.
- Comprobante de cobro completo.

## 7. Integración entre ventas, inventario y logística

### 7.1. Productos y creación del despacho

- Ventas crea pedidos utilizando `detallepedido.idInventoryProduct`.
- Logística resuelve productos nuevos e históricos con `COALESCE(idInventoryProduct, idProducto)`.
- Al emitir un remito desde Ventas se crea el despacho, sus renglones, el depósito de origen y el primer evento del historial.
- Al programar desde Logística se reserva la cantidad elegida y se permite dividir el pedido en entregas parciales.

### 7.2. Movimiento de stock y pendiente

En v1.7 se establece que cargar el pedido o emitir el remito **no descuenta stock** y que la salida física corresponde a la confirmación del despacho, según RF-1.6 de Inventario.

### 7.3. Estados disponibles

```text
EN_ESPERA
EN_PREPARACION
EN_RUTA
ENTREGADO
DEVUELTO
```

## 8. Archivos principales

| Ruta | Función documentada |
| --- | --- |
| `database/sedema_db_unificada.sql` | Instalación completa con la exportación consolidada. |
| `database/010_integration_merge.sql` | Migración desde una base Clientes/Usuarios v1.9.1. |
| `docs/INTEGRACION.md` | Análisis técnico, conflictos resueltos y pruebas recomendadas. |
| `public/dashboard.php` | Acceso unificado a los módulos. |
| `public/profile.php` | Perfil personal. |
| `first-access.php` | Primer acceso; los README no indican su ruta completa. |
| `src/Authorization.php` | Permisos por rol y permisos granulares. |
| `src/Logistics/` | Lógica transaccional de despacho. |
| `src/Clients/` | Módulo de clientes. |
| `src/Sales/` | Módulo de ventas y pedidos. |
| `src/Payments/` | Módulo de pagos y cobranzas. |
| `src/Payments/PaymentGateway.php` | Adaptador de pasarela de pagos. |
| `src/Access/` | Módulo de usuarios y accesos. |
| `public/uploads/` | Archivos cargados que deben conservarse durante la integración v2.0. |
| `storage/logs/mail.log` | Correos simulados con `MAIL_TRANSPORT=log`. |

## 9. Seguridad

- El paquete integrado documentado no incluye `.env`.
- No publicar credenciales, respaldos SQL ni `storage/logs/` en un servidor web.
- En producción, servir únicamente el directorio `public/` y habilitar HTTPS.
- Configurar `APP_ENV=production` y una `APP_KEY` de al menos 32 caracteres.
- Cambiar las contraseñas de prueba y revisar usuarios, correos y datos de prueba incluidos en la exportación.
- Conservar la configuración local durante las actualizaciones.

## 10. Verificación funcional

### 10.1. Prueba mínima de la integración v2.0

1. Iniciar sesión como administrador y comprobar el acceso a Clientes, Ventas, Pagos y Logística.
2. Crear un cliente y un pedido con depósito y stock disponible.
3. Programar un despacho parcial y verificar el remito y la hoja de ruta.
4. Avanzar a preparación y luego a ruta; comprobar el egreso de inventario y la reducción del pendiente.
5. Marcar el despacho como entregado y comprobar la liberación del vehículo.
6. Probar una devolución y confirmar la restitución del stock y del pendiente.
7. Iniciar sesión con los roles Vendedor, Depósito y Logística para validar visibilidad y permisos.

### 10.2. Comprobaciones adicionales derivadas de las actualizaciones

- Crear una cuenta y probar el acceso con correo o DNI y contraseña temporal, seguido del cambio obligatorio de usuario y contraseña.
- Comprobar el correo simulado en `storage/logs/mail.log` cuando esté activo el modo `log`.
- Verificar edición del perfil, foto y contraseña; comprobar que DNI, rol y permisos permanezcan de solo lectura.
- Verificar que el administrador no pueda eliminar su propia cuenta y que las cuentas eliminadas no puedan acceder.
- Registrar un cliente desde un pedido con un rol autorizado y comprobar que quede preseleccionado al regresar.
- Probar las operaciones de tarjeta/transferencia con `PAYMENT_GATEWAY_MODE=mock`.

## 11. Historial de actualizaciones

| Versión | Cambios consolidados |
| --- | --- |
| v1.2 | Usuarios y Accesos sobre Personal v1.1; cuentas, roles/permisos, contraseña temporal y primer acceso obligatorio. |
| v1.3 | Eliminación de Sector en la interfaz, Rol de acceso en el listado, SMTP TLS/587 o SSL/465 y reenvío de contraseña temporal. |
| v1.4 | Mostrar/ocultar contraseña en el primer acceso y foto de perfil en menús, Personal e Inventario; inicial como alternativa. |
| v1.5 | Edición de correo, eliminación individual o múltiple de cuentas, protección de la cuenta propia del administrador y conservación del historial. |
| v1.6 | Clientes: alta, consulta, modificación, clasificación, baja lógica y reactivación. |
| v1.7 | Ampliación RF2.1–RF2.5: pedidos, ajustes/financiación, remitos y factura para pedidos pagados; integración con inventario. |
| v1.7.1 | Eliminación de etiquetas de requerimientos de la interfaz, conservando títulos y descripciones. |
| v1.8 | Menú de usuario y perfil personal editable, foto y cambio de contraseña. |
| v1.8.1 | Registro/edición de clientes por CAJA y VENDEDOR y alta de cliente desde el pedido. |
| v1.9 | Pagos y Cobranzas, migración de base y modo de pasarela `mock`. |
| v1.9.1 | Alineación con RF3.1–RF3.3: medios de pago, cuenta corriente, vuelto, cheques, verificación de pasarela, descuentos/recargos y comprobante. |
| v2.0 | Proyecto integrado, base unificada, migración desde Clientes/Usuarios v1.9.1 e integración de Ventas con Logística. |
