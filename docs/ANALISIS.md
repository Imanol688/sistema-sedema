# Análisis del módulo de acceso — SEDEMA

## Correspondencia con la documentación

- **RF1.1:** inicio de sesión interno con `username` y `passwordHash`, más control de cuenta habilitada.
- **RF1.2:** la sesión conserva rol y permisos granulares para habilitar módulos posteriormente.
- **RNF01:** interfaz directa, adaptable a escritorio y celular, con mensajes accionables.
- **RNF02:** contraseñas con `password_hash()`/`password_verify()`; no se almacenan ni registran en texto plano.
- **RNF05:** autenticación aislada en servicios para mantenerla desacoplada de ventas, pagos, inventario y logística.
- **Modelo ER/UML:** se conservan `usuario`, `empleado`, `roles`, `permisos` y `habilitado`. Se agrega el rol `VENDEDOR`, presente conceptualmente en el UML y necesario para RF2.

## Decisiones de seguridad

1. Consultas parametrizadas con PDO y emulación desactivada.
2. Token CSRF en todos los formularios que modifican estado.
3. Regeneración del identificador de sesión al autenticar.
4. Cookies `HttpOnly`, `SameSite=Lax` y `Secure` cuando hay HTTPS.
5. Mensajes genéricos para evitar descubrir si un usuario o correo existe.
6. Límite de cinco intentos en 15 minutos por identidad o IP anonimizada, más bloqueo temporal de la cuenta.
7. Hash de IP con HMAC para auditoría sin guardar la dirección en claro.
8. Recuperación con token aleatorio de 256 bits; solo el hash se guarda, vence en 30 minutos y se usa una vez.
9. Al cambiar la contraseña aumenta `authVersion`, invalidando sesiones anteriores.
10. Errores técnicos van a un registro fuera de `public`; la interfaz solo muestra un código de incidente.

## Pendientes para la siguiente etapa

- Pantalla administrativa de alta/baja de usuarios y asignación de permisos.
- Política concreta de permisos por módulo y acción.
- Integración SMTP transaccional en producción.
- Tarea programada para depurar intentos y tokens vencidos.
- Forzar HTTPS y configurar cabeceras también en Apache/Nginx.
- Adaptar la migración si la tabla `usuario` ya fue creada con otra estructura.

