# Despacho y logística

## Alcance implementado

- Agenda y filtro de despachos.
- Programación parcial o total sobre renglones pendientes de un pedido.
- Reserva lógica para impedir que dos despachos pendientes asignen la misma cantidad.
- Modalidad de retiro en corralón o entrega a domicilio.
- Asignación de depósito de origen y vehículo.
- Flota con matrícula, modelo, capacidad, estado y baja lógica.
- Hoja de ruta y remito imprimibles.
- Historial de estados con fecha, usuario y observación.

## Flujo e impacto en inventario

| Transición | Validación/efecto |
| --- | --- |
| Programación → En espera | Reserva la cantidad; no modifica stock ni el pendiente físico. |
| En espera → En preparación | Verifica stock suficiente en el depósito. |
| En preparación → En ruta | Descuenta stock, reduce `cantidadPendiente`, registra egresos y ocupa el vehículo. |
| En ruta → Entregado | Libera el vehículo. |
| En ruta/Entregado → Devuelto | Repone stock y pendiente, registra ingresos y libera el vehículo. |

Cada egreso o devolución usa `sourceModule = 'LOGISTICA'` y una referencia única por ítem. El cambio de stock y el estado se confirman en una única transacción.

La capacidad del vehículo queda registrada, pero todavía no se valida automáticamente porque los productos no poseen peso o volumen normalizado. Esa regla deberá incorporarse cuando el catálogo defina una magnitud logística común.

## Permisos mínimos

- `logistics.view`: consulta.
- `logistics.manage`: programación y flota.
- `logistics.dispatch`: cambios de estado con impacto operativo.

No se modificaron la recuperación de contraseñas, la limitación de intentos ni el tratamiento del archivo `.env`. Tampoco se agregó una suite de pruebas automatizadas.
