# SEDEMA · Sistema de gestión para corralones

**Versión estable 3.1**

SEDEMA es un sistema web de uso interno para un corralón de materiales de construcción. Centraliza las compras a proveedores, el control de existencias, la toma de pedidos, los cobros y la entrega de mercadería. El personal accede desde el navegador a las funciones que le corresponden según su rol.

## Módulos

| Módulo | Funciones principales |
| --- | --- |
| Personal y usuarios | Administra legajos, perfiles, cuentas de acceso y liquidaciones de sueldo. Incluye primer ingreso con contraseña temporal y recuperación de acceso. |
| Ventas, pedidos y clientes | Mantiene el padrón de clientes, registra pedidos por depósito y modalidad de entrega, aplica descuentos o recargos y permite retomar pedidos pendientes. |
| Pagos y cobranzas | Registra cobros y movimientos de cuenta corriente. Integra Mercado Pago QR de mostrador y permite emitir remitos y facturas internas una vez completado el pago. |
| Inventario y depósito | Gestiona artículos, categorías, unidades y existencias por depósito. Registra entradas, salidas y transferencias, y calcula el disponible considerando las reservas de pedidos activos. |
| Abastecimiento y proveedores | Registra proveedores, compras y remitos. Permite previsualizar e imprimir remitos de proveedor y registrar recepciones parciales o totales que actualizan el stock. |
| Despacho y logística | Programa entregas, asigna vehículos y transportistas, y registra salidas, entregas y devoluciones. Permite reprogramar o cancelar despachos según su estado. |

## Características

- **Operación integrada.** El pedido reserva mercadería, el cobro habilita la emisión de comprobantes y Logística coordina el despacho.
- **Control de acceso.** El administrador habilita las cuentas del personal. El sistema aplica roles y permisos, almacena las contraseñas mediante hash y limita los intentos fallidos de ingreso.
- **Trazabilidad.** Quedan registrados los movimientos de stock, los intentos de cobro y los cambios de estado del despacho.
- **Tecnologías.** La aplicación utiliza PHP, HTML, CSS y JavaScript, y accede a MySQL o MariaDB mediante PDO. El despliegue de demostración se realizó en alwaysdata.

## Evolución del desarrollo

| Etapa | Cambios principales |
| --- | --- |
| Desarrollo inicial | Se construyeron el inicio de sesión, el panel principal y la gestión de personal, usuarios y roles. |
| 2.2 | Se estabilizaron Inventario, Abastecimiento y Logística, con mejoras en recepciones, reprogramación y cancelación de despachos. |
| 3.0 integrada | Se unificaron los módulos comerciales y logísticos en una sola aplicación y base de datos. Se incorporó Mercado Pago QR de mostrador. |
| 3.1 estable | Se incorporaron el guardado de pedidos pendientes y la reserva por depósito. Se mejoró la gestión de proveedores y de pedidos impagos con intentos de cobro cerrados. Se desplegó la versión en alwaysdata. |

## Requisitos para operar

| Entorno o función | Requisitos |
| --- | --- |
| Puesto de trabajo | Navegador web actualizado, acceso a la URL del sistema y una cuenta habilitada con los permisos del puesto. |
| Servidor web | PHP 8.1 o superior con `pdo_mysql`, `mbstring` y `fileinfo`. Para instalaciones con Apache, soporte de `.htaccess`. En hosting, raíz web en `public/` y acceso por HTTPS. |
| Base de datos | MySQL 8.0.16 o superior, o MariaDB 10.4 o superior, con el esquema de SEDEMA y una cuenta con permisos sobre esa base. |
| Configuración | Archivo `.env` con la URL, la conexión a la base y una `APP_KEY` propia. El proceso de PHP requiere permisos de escritura en `storage/logs/` y `public/uploads/profiles/`. |
| Correo | Transporte de correo configurado para enviar credenciales temporales y mensajes de recuperación. El envío directo por SMTP requiere OpenSSL. |
| Cobros con Mercado Pago QR | Extensión cURL de PHP, credenciales de Mercado Pago, una caja configurada y un endpoint HTTPS para recibir notificaciones. |
