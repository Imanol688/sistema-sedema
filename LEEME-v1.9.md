# SEDEMA v1.9 — Parche Pagos y Cobranzas

- Fusionar este contenido con `C:\xampp\htdocs\sedema-auth\`.
- Conservar el `.env` actual.
- Importar una sola vez `database/008_payments_schema.sql` en `sedema_db`.
- Agregar `PAYMENT_GATEWAY_MODE=mock` al `.env` para probar tarjeta/transferencia sin dinero real.
- Reiniciar Apache y hacer Ctrl+F5.

Una pasarela real necesita el proveedor y sus credenciales; el adaptador está en `src/Payments/PaymentGateway.php`.
