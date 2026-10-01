# SEDEMA v1.8 - Perfil personal y menú de usuario

## Cambios
- Menú desplegable al hacer clic en el usuario/avatar del sidebar y topbar.
- Disponible para Administrador y empleados.
- Nueva pantalla `public/profile.php`.
- Edición de nombre, apellido, username, correo y teléfono propios.
- Cambio de foto de perfil (JPG/PNG/WebP, máximo 2 MB).
- Cambio de contraseña con verificación de contraseña actual.
- DNI, rol y permisos permanecen de solo lectura para el propio usuario.
- La foto actualizada se refleja en la sesión y los menús del sistema.

## Instalación
Fusionar el parche con `C:\xampp\htdocs\sedema-auth\` y reemplazar solo los archivos incluidos.
No requiere migración SQL y no se debe reemplazar `.env`.
Reiniciar Apache y realizar `Ctrl + F5`.
