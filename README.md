# SEDEMA · Gestión integral para corralón

Versión integrada 3.0. PHP y MySQL/MariaDB para Personal y Usuarios, Ventas/Pedidos-Clientes, Pagos y Cobranzas, Inventario, Proveedores y Logística.

La integración conserva las correcciones v2.2 de inventario, recepción de compras y despachos, incorpora los cambios comerciales y Mercado Pago QR del otro grupo y corrige la lectura de comentarios `#` y BOM UTF-8 en `.env`.

## Requisitos

PHP 8.1 o superior con `pdo_mysql`, `mbstring` y `fileinfo`; cURL para las pasarelas y OpenSSL para SMTP. MySQL 8.0.16+ o MariaDB 10.4+. Apache con soporte para los archivos `.htaccess`. Escritura en `storage/logs` y `public/uploads/profiles`.

## Instalación nueva sin datos operativos

1. Copiar el proyecto a `C:\laragon\www\sedema`.
2. Copiar `.env.example` como `.env` y configurar conexión, URL y una `APP_KEY` propia. No reemplazar un `.env` existente.
3. Ejecutar `database/install.sql` sobre una base vacía. El script crea `sedema_integrada` con 41 tablas operativas y dos tablas para documentar una eventual fusión de datos. No crea usuarios ni operaciones personales.
4. Crear el administrador desde la terminal de Laragon:

```bat
cd /d C:\laragon\www\sedema
php database\create_admin.php admin admin@empresa.com "CLAVE-PROPIA-DE-12-O-MAS-CARACTERES" Nombre Apellido
```

5. Abrir `http://localhost/sedema/public/` y completar los datos del administrador.

La plantilla usa `MAIL_TRANSPORT=log` para pruebas sin envío de correo. SMTP y Mercado Pago requieren configuración propia en `.env`. El repositorio no contiene credenciales, fotos ni copias de bases operativas.

## Actualizar una instalación v2.2

Respaldar carpeta y base, detener las escrituras y probar primero sobre una copia. Seleccionar la base de esa copia y ejecutar `database/update_020.sql`. Requiere la migración `013_dispatch_operations_20261022`; puede repetirse y no importa registros de otro ambiente. Mantener el `.env`, las fotos y los registros actuales al actualizar los archivos.

El instalador limpio no sirve para actualizar una base poblada. Ante un error SQL, detenerse y revisar; no usar `--force`. El DDL confirma cambios implícitamente.

## Conversión y fusión de bases

- `database/migrate_to_integrated.php`: convierte un único origen a un destino integrado nuevo; `--check` prevalida sin escribir.
- `database/merge_groups.php`: reproduce la fusión preparada para una instalación v2.2 sin ventas locales y un origen comercial. Sin `--apply` prepara el plan; con `--apply` copia a un destino nuevo y conserva la procedencia.
- `docs/INTEGRACION.md`: políticas, alcance y límites de estas herramientas.

Las copias operativas entregadas por separado no forman parte de Git. Antes de fusionar otras bases hay que comprobar identidades, claves y existencias; compartir un ID no demuestra que dos registros sean la misma entidad.

## Flujos integrados

- Ventas usa el catálogo, unidades y depósitos de Inventario. Los precios históricos del pedido se conservan; una venta nueva usa el catálogo vigente.
- El pago efectivo completo habilita remito y factura. La programación desde Pagos utiliza las validaciones y reservas de Logística.
- La salida a ruta descuenta stock y pendiente dentro de una transacción. Cancelar una programación libera su reserva; devolver un despacho repone las cantidades.
- Los formularios de cobro y las operaciones protegidas reconocen reenvíos mediante claves duraderas. Los despachos rechazan formularios desactualizados.
- Una imputación a cuenta corriente representa deuda. El cobro aprobado acredita la cuenta y una devolución QR verificada revierte el crédito correspondiente.
- Los pedidos con operaciones asociadas no se corrigen, eliminan ni cancelan mediante el circuito de pedidos sin operaciones. La auditoría de eliminación se conserva.
- El Administrador gestiona accesos y genera liquidaciones. Las fotos y el primer acceso mantienen sus flujos existentes.

## Configuración de pagos

Tarjeta y transferencia conservan el adaptador genérico y su modo `mock`. Mercado Pago QR utiliza un cliente separado. Configurar las variables `MP_` de `.env.example` para la cuenta y caja propias. No hay aprobación por escanear únicamente: el sistema debe verificar la acreditación.

## Validación y límites

Se verificó la sintaxis PHP/JavaScript y el circuito de servicios con PHP 8.3.6 y MariaDB 10.11.14. La integración incluyó comprobaciones de conservación de datos, claves foráneas, acceso HTTP y reenvíos concurrentes. El parche del lector de entorno se comprobó con la plantilla completa, comentarios con paréntesis, BOM, CRLF, valores entre comillas y prioridad de las variables MP del proyecto. No se incorpora una suite automatizada al repositorio.

Falta el recorrido final en cada ambiente de instalación; no se hicieron cobros externos ni envíos SMTP durante estas comprobaciones. Los datos históricos incompletos se señalan sin inventar entregas. La agenda detecta coincidencias exactas de horario, sin duración de viajes; no se agregaron reservas físicas globales ni devoluciones parciales de despachos. Los comprobantes son internos: no se incorpora facturación fiscal electrónica.
