# Integración técnica de subsistemas SEDEMA

## Resultado

La rama Clientes/Usuarios v1.9.1 se utilizó como base porque contiene la autenticación más nueva, la gestión de perfiles, clientes, ventas, pagos y el volcado de datos más reciente. Sobre ella se incorporó el módulo operativo de Despacho/Logística, conservando su trazabilidad, flota, entregas parciales, remitos y hojas de ruta.

## Compatibilidad comprobada

| Área | Situación encontrada | Resolución aplicada |
| --- | --- | --- |
| Autenticación | Logística no contemplaba primer acceso, perfil, baja lógica ni roles nuevos | Se conservó `AuthService` de Clientes/Usuarios y se agregó el control de primer acceso a Logística |
| Permisos | Cada rama conocía solo sus propios permisos | Se unificó `Authorization` con permisos de clientes, ventas, pagos, inventario y logística |
| Panel | La tarjeta de Logística no tenía ruta activa | Se vinculó a `public/logistics/index.php` y se añadieron alias de permisos en español/inglés |
| Productos de pedido | Ventas usa `idInventoryProduct`; Logística histórica usaba `idProducto` | Los repositorios logísticos usan `COALESCE(idInventoryProduct,idProducto)` y la migración normaliza registros históricos |
| Despacho | La rama Clientes no guardaba depósito, creador ni trazabilidad completa | Se agregaron `idWarehouse`, auditoría temporal, creador e historial de estados |
| Remitos de Ventas | Se creaba remito sin renglones logísticos | Ahora se crean `itemdespacho` y el evento inicial para que Logística pueda continuar el flujo |
| Cantidades | Existían precisiones `DECIMAL(12,2)` y `DECIMAL(14,3)` | Se adoptó `DECIMAL(14,3)` para materiales y unidades fraccionables |
| Claves | Las exportaciones mezclan identificadores `SIGNED` y `UNSIGNED` | El esquema consolidado respeta los tipos de la exportación Clientes/Usuarios y usa tipos exactos en las nuevas claves |
| Estilos | Cada rama agregó CSS al mismo archivo | Se preservaron Clientes/Usuarios/Pagos y se incorporó Logística con reglas de impresión acotadas a `.print-page` |

## Base de datos consolidada

`sedema_db_unificada.sql` parte del volcado Clientes/Usuarios del 17-09-2026, que es posterior y contiene más módulos y operaciones. No se duplicó el usuario administrador ni los datos de prueba de la otra exportación, porque comparten claves primarias y representan ambientes divergentes. Sí se integraron toda la estructura y las reglas de Logística mediante `010_integration_merge`.

La migración:

- conserva pedidos, clientes, usuarios, pagos y despachos del volcado elegido;
- relaciona despachos con depósitos;
- normaliza la referencia al catálogo canónico de inventario;
- crea historial de estados para despachos existentes;
- agrega claves e índices de integración;
- deja constancia en `schema_migration`.

## Cobertura de requerimientos

- RF1.1-RF1.4: autenticación, usuarios/permisos, legajos y haberes.
- RF2.1-RF2.5: clientes, pedidos, ajustes, remitos y facturas.
- RF3.1-RF3.3: pagos, cuenta corriente, pasarela simulada y ajustes de cobranza.
- RF-1.1-RF-1.8: inventario, mínimos, unidades, movimientos y auditoría.
- RF-3-RF-3.9: flota, programación, entregas parciales, impresión y ciclo completo del despacho.

## Riesgos y límites de validación

La revisión del paquete fue estática porque el entorno de integración no incluye PHP ni MariaDB ejecutables. Se comprobaron rutas, dependencias entre clases, consultas y columnas requeridas, balance estructural de archivos PHP, presencia de recursos y composición del SQL. La prueba final debe realizarse en XAMPP importando la base en una instancia de respaldo antes de reemplazar el sistema en uso.
