# Módulo Personal y Haberes

Primera versión funcional basada en RF1.3 y RF1.4.

## Alcance
- Alta y edición de legajos de empleados.
- Baja lógica y reactivación, conservando el historial.
- Consulta y búsqueda de personal activo/inactivo.
- Visualización del usuario vinculado sin mezclar la administración de credenciales con el legajo.
- Liquidación mensual a partir del sueldo base del legajo.
- Haberes adicionales y descuentos por conceptos flexibles.
- Historial de liquidaciones y recibo imprimible.

## Permisos
- `personal.view`: acceso al módulo.
- `personal.manage`: altas, modificaciones y bajas de legajos.
- `personal.payroll`: procesamiento y consulta de haberes.
- `ADMINISTRADOR` recibe todos los permisos por defecto.

## Instalación
1. Importar `database/003_personnel_schema.sql` sobre `sedema_db`.
2. Ingresar nuevamente al sistema como administrador.
3. Abrir **Personal y usuarios** desde el panel principal.

## Decisiones de arquitectura
La baja de un empleado no elimina filas. Si el legajo tiene un usuario vinculado, se incrementa `authVersion` al darlo de baja para invalidar sesiones existentes. El inicio de sesión existente ya rechaza empleados con `activo = 0`.

Las credenciales y roles siguen bajo el subsistema de autenticación. Esta versión no duplica usuarios dentro de Personal; deja preparado el permiso `personal.*` para integrar una pantalla administrativa de usuarios posteriormente.
