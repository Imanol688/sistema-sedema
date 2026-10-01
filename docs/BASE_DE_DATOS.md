# Base de datos canónica de SEDEMA

## Decisión de unificación

El sistema adopta `sedema_db` como único esquema y el conjunto `inventory_*` como modelo canónico de productos, depósitos, existencias y movimientos.

| Concepto | Tabla canónica | Tabla anterior eliminada |
| --- | --- | --- |
| Producto | `inventory_product` | `producto` |
| Depósito | `inventory_warehouse` | `almacen` |
| Existencia por depósito | `inventory_stock` | columnas de `producto` |
| Historial de stock | `inventory_movement` | `movimientostock` |

La clave `inventory_product.idProduct` será compartida por inventario, ventas, compras, despacho y logística. Esto evita sincronizaciones entre catálogos paralelos y mantiene una única fuente de verdad.

## Relaciones preparadas para integración

- `detallepedido.idProducto` apunta a `inventory_product.idProduct`.
- `detalleremito.idProducto` apunta a `inventory_product.idProduct`.
- `detallerecepcion.idProducto` apunta a `inventory_product.idProduct`.
- `despacho.idWarehouse` apunta al depósito desde el que se preparará y descontará mercadería.
- `recepcioncompra.idWarehouse` apunta al depósito que recibirá la mercadería.
- `itemdespacho` conserva su relación con `detallepedido`, por lo que el producto se obtiene sin duplicarlo.

El remito mantiene `sucursalOrigen` como texto histórico imprimible. La relación operativa se obtiene desde `despacho.idWarehouse`.

## Archivos de base de datos

### Instalación nueva

Ejecutar `database/sedema_db_schema.sql`. El archivo no contiene empleados, usuarios, contraseñas ni datos operativos. Después se crea el administrador con `database/create_admin.php`.

### Actualización de la base existente

1. Realizar un respaldo completo de `sedema_db`.
2. Ejecutar `database/004_unify_inventory_schema.sql` desde MySQL Workbench.
3. Confirmar la fila `004_unify_inventory` en `schema_migration`.
4. Confirmar que ya no existen `producto`, `almacen` ni `movimientostock`.
5. Revisar que las relaciones de productos apunten a `inventory_product`.
6. Si ya existen recepciones o despachos, asignar manualmente su depósito antes de habilitar esos módulos. En instalaciones nuevas, ambas relaciones son obligatorias; durante la migración se mantienen anulables para no inventar datos históricos.

La migración copia categorías, unidades, depósitos, productos, saldos e historial del modelo anterior. Los productos se identifican mediante `codigo`, considerado la clave comercial única.

Si un mismo código y depósito posee saldos distintos y mayores que cero en ambos modelos, la migración se detiene antes de eliminar las tablas anteriores. Los casos detectados quedan en `migration_004_stock_conflict` para revisión manual.

## Reglas para los próximos módulos

1. Ningún módulo actualizará directamente `inventory_stock`.
2. Cada ingreso o egreso generará un registro en `inventory_movement` dentro de la misma transacción.
3. Compras usará `sourceModule = 'COMPRAS'` y una referencia estable a la recepción.
4. Despacho usará `sourceModule = 'LOGISTICA'` y una referencia estable al renglón despachado.
5. La restricción única de origen evitará procesar dos veces la misma recepción o entrega.
6. El estado `ENTREGADO` deberá definirse con precisión antes de decidir si el egreso ocurre al preparar, despachar o confirmar la entrega.

## Alcance postergado

En esta etapa no se modificaron permisos, visibilidad del panel, recuperación de contraseña, limitación de intentos, archivo `.env` ni pruebas automatizadas.

La protección de baja de productos queda propuesta en `docs/INVENTARIO.md` y se implementará después de definir las reservas y estados del módulo de despacho.
