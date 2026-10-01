# SEDEMA v1.7 — Módulo 2 completo

Esta versión amplía Clientes v1.6 para cubrir RF2.1–RF2.5: padrón, pedidos, ajustes/financiación, remitos de salida y factura para pedidos pagados.

## Instalación
1. Fusionar archivos con la instalación existente; no borrar carpetas y conservar `.env`.
2. En phpMyAdmin, seleccionar `sedema_db` e importar una sola vez `database/007_sales_orders_schema.sql`.
3. Reiniciar Apache.
4. Entrar a `Ventas y pedidos` o `Clientes` desde el dashboard.

## Nota de integración
Los pedidos consultan `inventory_product` + `inventory_stock`. El stock no se descuenta al cargar el pedido ni al emitir el remito; la salida física queda para la confirmación del despacho, de acuerdo con RF-1.6 de Inventario.
