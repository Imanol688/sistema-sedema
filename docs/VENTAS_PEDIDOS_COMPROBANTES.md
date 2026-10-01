# Módulo 2 — Clientes, Pedidos y Comprobantes (v1.7)

- RF2.1: padrón y clasificación de clientes en `cliente`.
- RF2.2: pedidos de venta con cliente, depósito, artículos de `inventory_product`, consulta y validación de `inventory_stock`, y modalidad EN_CORRALON / ENTREGA_DOMICILIO / ACOPIO.
- RF2.3: ajustes persistidos en `ajuste`: descuento comercial, descuento por volumen y recargo por tarjeta.
- RF2.4: programación de `despacho` y emisión imprimible de `remitodespacho`, con destino, fecha/hora, transportista y vehículo.
- RF2.5: factura A/B/C sólo si la suma de pagos APROBADOS alcanza el total del pedido. Persiste en `factura` e incluye descuentos/recargos y firma/aclaración.

La disminución física de stock no se realiza al cargar el pedido ni al emitir el remito; queda reservada para la confirmación del despacho, respetando RF-1.6 de Inventario.
