# Módulo Clientes — SEDEMA v1.6

## Alcance
Implementa el padrón de clientes definido para la primera etapa comercial:

- registrar clientes;
- clasificar por tipo: Particular, Albañil, Empresa, Mayorista o Contratista;
- consultar y buscar por nombre, DNI/CUIT, razón social, teléfono o localidad;
- modificar la ficha;
- baja/reactivación lógica sin perder referencias históricas.

## Arquitectura

`public/clients` → `ClientService` → `ClientRepository` → tabla `cliente`.

El módulo no implementa todavía Pedidos, Ajustes, Remitos ni Facturación. Se mantiene `cliente.idCliente` como punto de integración para esos desarrollos posteriores.

## Permisos

- `clientes.view`: consultar el padrón.
- `clientes.manage`: altas, modificaciones y bajas lógicas.
- Administrador: acceso total.
- Vendedor: consulta y gestión.
- Un permiso granular `clientes.*` asignado desde Usuarios y accesos habilita ambas operaciones.

## Instalación

Ejecutar una sola vez `database/006_clients_schema.sql` sobre `sedema_db` y copiar/fusionar los archivos de la versión sin borrar `.env`.
