# Módulo de Inventario

## Instalación

Ejecutar `database/002_inventory_schema.sql` en MySQL Workbench sobre la misma base indicada por `DB_DATABASE` en `.env`. La migración no selecciona una base por nombre para funcionar tanto con `sedema` como con `sedema_db`.

## Alcance implementado

- Catálogo de productos, categorías y unidades configurables.
- Múltiples depósitos con existencias y stock mínimo independientes.
- Alta, modificación y baja lógica de productos.
- Ingresos, egresos, ajustes positivos y negativos.
- Transferencias atómicas entre depósitos.
- Historial con usuario, fecha, cantidades anterior/resultante y observaciones.
- Alertas derivadas de stock mínimo, sin procesos duplicados ni tablas de notificaciones.
- Atributos flexibles por producto almacenados como JSON.

## Contrato para otros módulos

Ventas, Compras y Logística no deben actualizar `inventory_stock` directamente. Toda modificación debe pasar por `InventoryService::recordMovement()` o por un adaptador que aplique la misma transacción.

Campos de integración de `inventory_movement`:

- `sourceModule`: nombre estable del módulo, por ejemplo `VENTAS`, `COMPRAS` o `LOGISTICA`.
- `sourceReference`: identificador único de la operación o renglón externo.
- `correlationId`: agrupa varios movimientos de una misma operación, utilizado inicialmente por transferencias.
- `actorUserId`: referencia lógica al usuario que originó el movimiento. No posee clave foránea para evitar acoplar Inventario al diseño futuro de Personal y Usuarios.

La restricción única sobre origen, referencia, tipo, producto y depósito permite que los integradores eviten descontar o ingresar dos veces el mismo renglón. Para integraciones automáticas, `sourceReference` debe ser estable y no nulo.

## Permisos

- `inventory.view`: consulta de productos, existencias e historial.
- `inventory.manage`: alta, modificación y baja de productos.
- `inventory.adjust`: movimientos y transferencias.
- `inventory.catalogs`: categorías, unidades y depósitos.

El rol `DEPOSITO` recibe los cuatro permisos por defecto. Los demás perfiles pueden recibirlos mediante el campo JSON `usuario.permisos`.

## Integridad

- El stock se bloquea con `SELECT ... FOR UPDATE` durante cada movimiento.
- No se permite existencia negativa.
- El historial se inserta en la misma transacción que actualiza la existencia.
- Una transferencia bloquea ambos depósitos en orden fijo para reducir interbloqueos.
- Los productos se dan de baja lógicamente para conservar movimientos históricos.
