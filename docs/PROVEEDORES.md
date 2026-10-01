# SEDEMA · módulo de proveedores y compras

## Cobertura funcional

| Requisito | Implementación |
| --- | --- |
| RF-2 seguimiento de compras | Padrón de proveedores; remitos con proveedor, fecha, número, productos, cantidades, precios y estados; listado y detalle consultable. |
| RF-2.1 recepción y datos del remito | Recepciones parciales o totales vinculadas al remito y depósito, con fecha, observaciones y cantidades por producto. |
| RF-1.6 ingreso automático | Al confirmar una recepción se suma la cantidad a `inventory_stock`. |
| RF-1.8 trazabilidad | `inventory_movement` registra producto, depósito, cantidad previa y final, usuario, fecha y referencia única de recepción. |

El remito del proveedor representa el documento de compra y su detalle pendiente. No se emiten órdenes de compra ni se procesan cuentas por pagar: esos procesos no están definidos en los requerimientos entregados.

## Instalación sobre la última base entregada

1. Respaldar la carpeta de proyecto y exportar `sedema_db` desde phpMyAdmin o MySQL Workbench.
2. Usar como referencia **sedema_db(2).sql** y **sedema_er(1).sql** adjuntos. No importar nuevamente estos dumps encima de una base con datos.
3. Seleccionar la base existente `sedema_db` y ejecutar **database/011_suppliers_schema.sql**. Las cinco tablas ya existen en el dump recibido: el script verifica y crea solo las que falten. No sustituye el dump ni modifica filas previas.
4. Reemplazar `C:\xampp\htdocs\sedema-auth` con el contenido de la carpeta `sedema-auth` del paquete conservando el archivo `.env` en uso (el paquete trae solo `.env.example` y `.env.txt`).
5. Abrir `http://localhost/sedema-auth/public/dashboard.php` con ADMINISTRADOR o PROVEEDOR. DEPOSITO/ALMACEN pueden registrar recepciones; permisos personalizados admitidos: `suppliers.view`, `suppliers.manage`, `suppliers.receive`.
6. Crear un proveedor, cargar un remito y su detalle, confirmar una recepción; verificar `inventory_stock`, `inventory_movement` e historial de recepciones. Hacer la prueba primero sobre una copia de la base.

La exportación `database/sedema_db_unificada.sql` incluida en el proyecto tiene identificadores `SIGNED` en algunas tablas de proveedores y no representa el mismo esquema que **sedema_db(2).sql** (`UNSIGNED`). Esta actualización toma el dump adjunto como referencia y no fusiona ambas exportaciones.

## Reglas operativas

- CUIT único de 11 dígitos; número de remito único por proveedor; productos activos y sin duplicados en cada remito.
- Para recibir, cada producto debe tener una posición activa `inventory_stock` en el depósito elegido. Inventario crea esas posiciones al dar de alta el producto. Si falta una, la operación se revierte y se informa.
- El remito pasa a `RECEPCION_PARCIAL` mientras haya cantidades pendientes y a `RECEPCION_TOTAL` al completarlas. Se puede cancelar solo sin recepciones.
- Las transacciones incluyen recepción, sus renglones, stock, movimiento de auditoría y estado. El bloqueo del remito y la posición de stock impide exceder cantidades pendientes o perder actualizaciones concurrentes.
- No se editan ni borran recepciones ya confirmadas; las correcciones posteriores requieren un ajuste de inventario con observación y usuario.

## Archivos principales

- `src/Suppliers/`: autenticación de contexto, repositorio, reglas y presentación.
- `public/suppliers/`: padrón, ficha, carga del remito, detalle y recepción, acciones con CSRF.
- `src/Authorization.php` y `public/dashboard.php`: permisos y acceso directo.
- `database/011_suppliers_schema.sql`: creación condicional de las cinco tablas de proveedores.

## Verificación y límite

Se verificaron estáticamente referencias, tablas, columnas, dependencias y el empaquetado. Este entorno no incluye PHP ni MySQL/MariaDB: no se ejecutó una prueba de integración. Validar en XAMPP con una copia de la base antes de usarlo en producción.
