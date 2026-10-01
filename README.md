# SEDEMA v2.0 - Proyecto integrado

Sistema PHP/MySQL unificado con autenticación, usuarios y accesos, clientes, ventas y pedidos, pagos y cobranzas, inventario, personal/haberes y despacho/logística.

## Requisitos

- XAMPP con PHP 8.1 o superior (`pdo_mysql` y `mbstring`).
- MariaDB 10.4 o MySQL 8.0.
- Apache con `mod_rewrite` habilitado.

## Instalación limpia recomendada

1. Hacer una copia de seguridad de cualquier instalación anterior.
2. Copiar esta carpeta como `C:\xampp\htdocs\sedema-auth`.
3. Copiar `.env.example` como `.env` y completar `APP_KEY`, base de datos y correo.
4. Abrir MySQL Workbench o phpMyAdmin e importar `database/sedema_db_unificada.sql`.
5. Confirmar que `.env` contenga `DB_DATABASE=sedema_db`.
6. Reiniciar Apache y MySQL desde XAMPP.
7. Abrir `http://localhost/sedema-auth/public/`.

El SQL unificado conserva los datos de la exportación más reciente de Clientes/Usuarios y agrega la estructura requerida por Logística. Antes de usar datos reales, revisar usuarios, correos y registros de prueba incluidos en la exportación.

## Actualización de la rama Clientes/Usuarios v1.9.1

Si esa base ya está instalada y funciona:

1. Hacer un respaldo completo.
2. Ejecutar una sola vez `database/010_integration_merge.sql`.
3. Reemplazar el código por este proyecto, conservando el `.env` local y `public/uploads/`.
4. Reiniciar Apache.

Para una base proveniente únicamente de la rama Logística, se recomienda la instalación limpia con `sedema_db_unificada.sql`, porque las ramas históricas usan firmas distintas (`SIGNED/UNSIGNED`) en varias claves. La importación limpia evita claves foráneas incompatibles.

## Integración entre Ventas y Logística

- Ventas crea pedidos usando `detallepedido.idInventoryProduct`.
- Logística resuelve productos nuevos e históricos mediante `COALESCE(idInventoryProduct, idProducto)`.
- Al emitir un remito desde Ventas se crea el despacho, sus renglones, el depósito de origen y el primer evento de historial.
- Al programar desde Logística se reserva la cantidad elegida y se permite dividir un pedido en entregas parciales.
- El stock y el pendiente del pedido se descuentan al pasar el despacho a `EN_RUTA` y se restituyen ante una devolución.
- Los estados disponibles son `EN_ESPERA`, `EN_PREPARACION`, `EN_RUTA`, `ENTREGADO` y `DEVUELTO`.

## Archivos principales

- `database/sedema_db_unificada.sql`: instalación completa con la exportación consolidada.
- `database/010_integration_merge.sql`: migración para una base Clientes/Usuarios v1.9.1 existente.
- `docs/INTEGRACION.md`: análisis técnico, conflictos resueltos y pruebas recomendadas.
- `public/dashboard.php`: acceso unificado a todos los módulos.
- `src/Authorization.php`: permisos por rol y permisos granulares.
- `src/Logistics/`: lógica transaccional del módulo de despacho.
- `src/Clients/`, `src/Sales/`, `src/Payments/`, `src/Access/`: módulos de la rama Clientes/Usuarios.

## Seguridad

El paquete no incluye `.env`. No publicar credenciales, respaldos SQL ni `storage/logs/` en un servidor web. En producción, servir únicamente `public/`, habilitar HTTPS, usar `APP_ENV=production`, configurar una `APP_KEY` de al menos 32 caracteres y cambiar cualquier contraseña de prueba.

## Verificación funcional mínima

1. Iniciar sesión con un administrador y comprobar el acceso a Clientes, Ventas, Pagos y Logística.
2. Crear un cliente y un pedido con depósito y stock disponible.
3. Programar un despacho parcial y verificar remito y hoja de ruta.
4. Avanzar a preparación y ruta; comprobar el egreso de inventario y la reducción del pendiente.
5. Marcar entregado y comprobar la liberación del vehículo.
6. Probar una devolución y confirmar la restitución del stock y del pendiente.
7. Iniciar sesión con roles Vendedor, Depósito y Logística para validar visibilidad y permisos.
