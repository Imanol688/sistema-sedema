# SEDEMA v1.9 — Pagos y Cobranzas

1. Fusionar el proyecto con la instalación actual conservando `.env`.
2. Importar una sola vez `database/008_payments_schema.sql` en `sedema_db`.
3. Para pruebas locales agregar `PAYMENT_GATEWAY_MODE=mock` al `.env`.
4. Reiniciar Apache.

El modo `mock` no usa dinero real; sirve para validar el flujo de tarjeta/transferencia. Para un proveedor real hace falta adaptar/configurar su API específica.
