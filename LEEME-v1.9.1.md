# SEDEMA v1.9.1

Corrección del Módulo 3: Pagos y Cobranzas para alinearlo con los diagramas y RF3.1-RF3.3.

Cambios principales:
- Medios nuevos: Efectivo, Tarjeta, Cheque y Transferencia.
- Cuenta Corriente separada como imputación contable.
- Efectivo: importe recibido y vuelto.
- Cheque: banco, número, titular, fechas y validación/rechazo.
- Tarjeta/Transferencia: solicitud de pasarela, QR/referencia y verificación antes de aprobación.
- Descuento/recargo directo en cobranza.
- Consulta detallada de movimientos de cuenta corriente.
- Comprobante de cobro completo.

Instalación: aplicar el parche y ejecutar `database/009_payments_diagram_alignment.sql` una sola vez.
