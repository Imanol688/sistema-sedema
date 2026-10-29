# Integración de módulos y bases

El código combina Inventario, Logística y Proveedores v2.2 con Ventas/Pedidos-Clientes, Personal/Usuarios, Pagos y Mercado Pago QR. Mantiene cancelación y reprogramación de despachos, impresión de remitos de proveedor, control de versiones de formularios e idempotencia.

## Esquema

La instalación nueva contiene 41 tablas operativas y dos de procedencia: `integration_source_record` y `integration_batch`. Se armonizan identificadores, cantidades, depósitos de recepción y relaciones de compra con `inventory_product`. Las tres tablas heredadas vacías no se trasladan; una tabla adicional con registros bloquea la conversión para revisión.

La factura se vincula al remito por su número histórico. Los remitos parciales imprimen los artículos de su despacho. Si un despacho histórico no tiene artículos, se conserva y se muestra una advertencia, sin reconstruir salidas ni modificar stock por suposición.

## Convertir un origen

Crear un destino nuevo con `database/install.sql`, detener las escrituras del origen y configurar la conexión del equipo en `.env`:

```bat
php database\migrate_to_integrated.php --source=base_origen --target=sedema_integrada --check
php database\migrate_to_integrated.php --source=base_origen --target=sedema_integrada
```

El origen se lee únicamente. El destino no puede contener operaciones. La prevalidación comprueba campos, referencias y duplicados básicos; las colaciones y restricciones SQL se verifican al insertar. Los datos se copian en una transacción; los contadores de identificadores se preparan antes, fuera de esa transacción.

Las recepciones sin depósito requieren asignación confirmada. No se debe utilizar `--receipt-warehouse` si distintas recepciones pertenecen a depósitos diferentes. Los campos de fecha de legajo ausentes reciben el momento de copia, sin atribuirles una fecha histórica desconocida.

## Reproducir la fusión preparada

Esta herramienta aplica políticas específicas; no es una fusión universal de cualquier par de bases. Requiere una instalación v2.2 sin operaciones comerciales locales y un segundo origen comercial, ambos separados del destino:

```bat
php database\merge_groups.php --installed=base_instalada --commercial=base_comercial --target=sedema_integrada --report=equivalencias.json
php database\merge_groups.php --installed=base_instalada --commercial=base_comercial --target=sedema_integrada --report=equivalencias.json --apply
```

Políticas de esa fusión:

- Conservar catálogo, existencias y credenciales administrativas del origen instalado cuando las identidades coinciden.
- Conservar los precios, importes, fechas y referencias de las operaciones comerciales históricas.
- No sumar dos fotografías del mismo stock ni duplicar sus aperturas. Conservar los registros anteriores en la procedencia.
- Reconocer entidades por claves naturales y documentar las equivalencias, reasignando IDs cuando colisionan eventos distintos.
- Conservar el administrador instalado y deshabilitar un segundo acceso para el mismo legajo, sin eliminar su usuario ni sus eventos históricos.

El script se detiene ante operaciones comerciales locales, tablas adicionales con registros, referencias sin equivalencia o un destino poblado. Es necesario revisar estas políticas antes de usarlo con otros conjuntos de datos. Las tablas de procedencia contienen datos privados originales; los dumps y los informes de equivalencias deben mantenerse fuera de Git y de las rutas públicas.

## Corrección del lector de entorno

`src/bootstrap.php` convierte los comentarios completos `#` al formato que admite el lector INI, después de retirar un posible BOM UTF-8. Esto corrige el error de sintaxis que aparecía al encontrar paréntesis en comentarios de la plantilla. Se conserva la prioridad de las variables MP del proyecto. No requiere cambios SQL ni reemplazar el `.env` del equipo.
