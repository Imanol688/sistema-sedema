# Módulo 3 - Pagos y Cobranzas (v1.9.1)

## Flujo funcional

1. El empleado/cajero selecciona un pedido pendiente.
2. Consulta total, pagos acumulados, saldo pendiente y saldo de cuenta corriente.
3. Elige **Pago** o **Cuenta Corriente**.
4. Si elige Pago, selecciona un medio: **EFECTIVO, TARJETA, CHEQUE o TRANSFERENCIA**.
5. Puede aplicar un **descuento o recargo** de último momento.
6. Efectivo se aprueba directamente y calcula vuelto.
7. Cheque registra banco, número, titular, emisión y vencimiento, y queda pendiente hasta validación.
8. Tarjeta y Transferencia generan una solicitud mediante `PaymentGateway`; quedan pendientes hasta la aprobación de la pasarela.
9. Cuenta Corriente genera una imputación contable y un débito al cliente, sin pasar por la pasarela.
10. Al aprobarse la operación se emite un comprobante y el pedido refleja el importe aprobado.

## Correspondencia con los requerimientos

- RF3.1: cobros y selección de medio / cuenta corriente.
- RF3.2: `PaymentGateway` encapsula generación de solicitud/QR y verificación de estado para tarjeta y transferencia.
- RF3.3: `payment_adjustment` registra descuento o recargo aplicado en cobranza y actualiza el total comercial del pedido al aprobarse.

## Modo de pasarela

Para desarrollo local:

```env
PAYMENT_GATEWAY_MODE=mock
```

Para una integración real:

```env
PAYMENT_GATEWAY_MODE=api
PAYMENT_API_BASE_URL=https://...
PAYMENT_API_KEY=...
PAYMENT_POINT_OF_SALE_ID=...
```

La API real debe devolver un identificador externo, estado y opcionalmente `qrPayload` o `qrImageUrl`.

## Migración

Ejecutar una sola vez, luego de `008_payments_schema.sql`:

`database/009_payments_diagram_alignment.sql`

La migración incorpora detalle de efectivo, cheque y separación de `CUENTA_CORRIENTE` respecto de `MedioPago`. `CONTROLADOR` se conserva únicamente como valor histórico de base para no destruir registros anteriores, pero ya no se ofrece para operaciones nuevas.
